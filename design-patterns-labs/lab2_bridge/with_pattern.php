<?php

// Lab 2 - WITH Bridge (GOOD, TODOs completed)
// Times formatter (abstraction) x DataSource + Compressor (implementors) - Bridge pattern.
// Swap the source or the compressor freely without touching the report logic.

// ---- Implementor A: where records come from ----
interface DataSource
{
    public function fetch(): array;
}

class ApiDataSource implements DataSource
{
    public function fetch(): array
    {
        $json = @file_get_contents("https://jsonplaceholder.typicode.com/posts?_limit=4");
        if ($json === false || trim($json) === "") {
            throw new RuntimeException("Live API unreachable.");
        }
        return json_decode($json, true);
    }
}

class FileDataSource implements DataSource
{
    public function fetch(): array
    {
        $json = @file_get_contents(__DIR__ . "/data.json");
        if ($json === false) {
            throw new RuntimeException("data.json not found.");
        }
        return json_decode($json, true);
    }
}

// ---- Implementor B: how output is compressed ----
interface Compressor
{
    public function compress(string $content): string;
    public function isCompressed(): bool;
}

class GzipCompressor implements Compressor
{
    public function compress(string $content): string
    {
        return gzencode($content, 6);
    }

    public function isCompressed(): bool
    {
        return true;
    }
}

class NoneCompressor implements Compressor
{
    public function compress(string $content): string
    {
        return $content;
    }

    public function isCompressed(): bool
    {
        return false;
    }
}

// ---- Abstraction: the report, delegates both varying dimensions ----
abstract class TimesFormatter
{
    public function __construct(
        protected DataSource $source,
        protected Compressor $compressor
    ) {
    }

    // Missing 1 (completed): swap the compression strategy at runtime without
    // touching the source or the concrete formatter subclass.
    public function setCompressor(Compressor $compressor): void
    {
        $this->compressor = $compressor;
    }

    abstract protected function toText(array $records): string;

    public function generate(string $title): string
    {
        $records = $this->source->fetch();
        $body = $this->toText($records);
        return $this->compressor->compress($title . "\n" . $body);
    }
}

class AttendanceTimesFormatter extends TimesFormatter
{
    protected function toText(array $records): string
    {
        $lines = [];
        foreach ($records as $r) {
            $lines[] = "{$r['userId']}  {$r['id']}  " . substr($r['title'] ?? '', 0, 18);
        }
        return implode("\n", $lines);
    }
}

// Missing 2 (completed): a second concrete Abstraction. It reuses the SAME
// two Implementor hierarchies (DataSource, Compressor) without modifying them.
class TimesheetJsonFormatter extends TimesFormatter
{
    protected function toText(array $records): string
    {
        return json_encode($records, JSON_PRETTY_PRINT);
    }
}

// ---- Demo runner ----
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Bridge (GOOD - TODOs completed):\n\n";

    // Missing 3 (completed): 2 formats x 2 sources x 2 compressors = 8 combos,
    // built from only 6 classes total (2 formatters + 2 sources + 2 compressors).
    // Adding a 3rd format, source, or compressor only ever adds ONE new class.
    $sources = [
        'FileDataSource' => new FileDataSource(),
        // 'ApiDataSource' => new ApiDataSource(), // swap in when live network is available
    ];

    $compressors = [
        'NoneCompressor' => new NoneCompressor(),
        'GzipCompressor' => new GzipCompressor(),
    ];

    $formatterClasses = [
        'AttendanceTimesFormatter' => AttendanceTimesFormatter::class,
        'TimesheetJsonFormatter'   => TimesheetJsonFormatter::class,
    ];

    // Note: ApiDataSource is included below only to show the class is wired
    // correctly; it is skipped in the combo loop if the live call fails so the
    // demo still runs offline. Uncomment it above to include it in the matrix.
    try {
        $sources['ApiDataSource'] = new ApiDataSource();
    } catch (Throwable $e) {
        // ignore at setup time; fetch() failures are caught per-combo below
    }

    $combo = 0;
    foreach ($formatterClasses as $formatterName => $formatterClass) {
        foreach ($sources as $sourceName => $source) {
            foreach ($compressors as $compressorName => $compressor) {
                $combo++;
                echo "--- Combo {$combo}: {$formatterName} + {$sourceName} + {$compressorName} ---\n";

                /** @var TimesFormatter $report */
                $report = new $formatterClass($source, $compressor);

                try {
                    $output = $report->generate('TIMES');
                } catch (Throwable $e) {
                    echo "  skipped (source unavailable: {$e->getMessage()})\n\n";
                    continue;
                }

                $bytes = strlen($output);
                echo "  compressed? " . ($compressor->isCompressed() ? 'yes' : 'no') . " | bytes: {$bytes}\n";

                if (!$compressor->isCompressed()) {
                    echo "  preview: " . substr($output, 0, 60) . "...\n";
                }
                echo "\n";
            }
        }
    }

    // Prove gzip output is smaller than plain output for the same content.
    $plainDemo = new AttendanceTimesFormatter(new FileDataSource(), new NoneCompressor());
    $plainOutput = $plainDemo->generate('TIMES');

    $plainDemo->setCompressor(new GzipCompressor()); // runtime swap via setCompressor()
    $gzipOutput = $plainDemo->generate('TIMES');

    echo "setCompressor() runtime swap check (AttendanceTimesFormatter + FileDataSource):\n";
    echo "  plain bytes: " . strlen($plainOutput) . "\n";
    echo "  gzip  bytes: " . strlen($gzipOutput) . "\n";
    echo "  gzip smaller than plain? " . (strlen($gzipOutput) < strlen($plainOutput) ? 'YES' : 'NO') . "\n\n";

    echo "Only 6 classes total (2 formatters x 2 sources x 2 compressors) cover 8 combos.\n";
    echo "Add CsvFormatter -> +1 class (not +4). Add ZipCompressor -> +1 class (not +4).\n";
}
