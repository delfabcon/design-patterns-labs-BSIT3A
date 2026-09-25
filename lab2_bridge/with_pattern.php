<?php
// Lab 2 - WITH Bridge (GOOD starter, has gaps to complete)
// Times formatter (abstraction) x DataSource + Compressor (implementors) - compression Bridge.
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
    ) {}

    // TODO: implement setCompressor($compressor) so a demo can swap compression at runtime.

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

// TODO: add TimesheetJsonFormatter extends TimesFormatter using json_encode($records, JSON_PRETTY_PRINT).

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Bridge (GOOD - complete TODOs):\n";
    $report = new AttendanceTimesFormatter(new ApiDataSource(), new NoneCompressor());
    echo $report->generate("TIMES");
    echo "\n\n  TODO: implement setCompressor() + TimesheetJsonFormatter, demo 2 sources x 2 compressors.\n";
}