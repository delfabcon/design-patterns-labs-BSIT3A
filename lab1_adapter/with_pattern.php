<?php
// Lab 1 - WITH Adapter (GOOD starter, has gaps to complete)
// Target: FileWriter - Adaptees: CsvLibrary/JsonLibrary/TextLibrary - Adapters translate.

// ---------- Target ----------
interface FileWriter
{
    /** @return bool true if the file was written */
    public function write(string $filename, array $rows): bool;
}

// ---------- Adaptees (legacy libs, incompatible, cannot change) ----------
class CsvLibrary
{
    public function writeCsv(string $path, array $rows): bool
    {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            return false;
        }
        fputcsv($handle, array_keys($rows[0] ?? []), ',', '"', "\\");
        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', "\\");
        }
        fclose($handle);
        return true;
    }
}

class JsonLibrary
{
    public function saveJson(string $path, mixed $payload): bool
    {
        return file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT)) !== false;
    }
}

class TextLibrary
{
    public function appendLine(string $path, string $line): bool
    {
        return file_put_contents($path, $line . PHP_EOL, FILE_APPEND) !== false;
    }
}

// ---------- Adapters (translate Adaptee -> Target) ----------
class CsvFileAdapter implements FileWriter
{
    public function __construct(private CsvLibrary $csv) {}

    public function write(string $filename, array $rows): bool
    {
        return $this->csv->writeCsv($filename, $rows);
    }
}

class JsonFileAdapter implements FileWriter
{
    public function __construct(private JsonLibrary $json) {}

    public function write(string $filename, array $rows): bool
    {
        return $this->json->saveJson($filename, $rows);
    }
}

class TextFileAdapter implements FileWriter
{
    public function __construct(private TextLibrary $text) {}

    public function write(string $filename, array $rows): bool
    {
        // TODO: write one line per row via appendLine, then return true.
        // foreach ($rows as $row) {
        //     $line = implode(' | ', $row);
        //     if (!$this->text->appendLine($filename, $line)) return false;
        // }
        // return true;
        return false;
    }
}

// ---------- Client (depends only on Target) ----------
class ReportExporter
{
    public function __construct(private FileWriter $writer) {}

    public function export(string $filename, array $rows): void
    {
        // TODO: delegate to $this->writer->write($filename, $rows) and report.
        // $ok = $this->writer->write($filename, $rows);
        // printf("  %s wrote=%s\n", basename($filename), $ok ? 'OK' : 'FAIL');
    }
}

// ---------- Contract test (all adapters interchangeable) ----------
function reportTo(string $format, string $filename, array $rows): void
{
    $writers = [
        'csv'  => new CsvFileAdapter(new CsvLibrary()),
        'json' => new JsonFileAdapter(new JsonLibrary()),
        'text' => new TextFileAdapter(new TextLibrary()),
    ];
    $exporter = new ReportExporter($writers[$format]);
    $exporter->export($filename, $rows);
    printf("  %s exists=%s size=%d\n", $format, file_exists($filename) ? 'yes' : 'no', file_exists($filename) ? filesize($filename) : 0);
}

$rows = [
    ['id' => 1, 'name' => 'Juan',  'log_type' => 'IN',  'time' => '08:01 AM'],
    ['id' => 2, 'name' => 'Maria', 'log_type' => 'OUT', 'time' => '12:00 PM'],
    ['id' => 3, 'name' => 'Pedro', 'log_type' => 'IN',  'time' => '01:02 PM'],
];

foreach (['csv', 'json', 'text'] as $format) {
    $file = sys_get_temp_dir() . '/lab1_with_' . $format . '.out';
    @unlink($file);
    reportTo($format, $file, $rows);
}

echo "  Added a 4th format? Only 1 new Adapter class - client never changes.\n";