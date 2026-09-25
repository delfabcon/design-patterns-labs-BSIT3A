<?php
// Lab 1 - WITHOUT Adapter (BAD): format branching duplicated in one class
// An attendance app exports time-log rows to CSV, JSON, and plain-text files.
// Different formats need different writers - so the class knows every format.

class ReportExporterWithoutAdapter
{
    public function __construct(
        private string $format
    ) {}

    public function export(string $filename, array $rows): void
    {
        if ($this->format === 'csv') {
            $handle = fopen($filename, 'w');
            fputcsv($handle, ['id', 'name', 'log_type', 'time'], ',', '"', "\\");
            foreach ($rows as $r) {
                fputcsv($handle, [$r['id'], $r['name'], $r['log_type'], $r['time']], ',', '"', "\\");
            }
            fclose($handle);
        } elseif ($this->format === 'json') {
            file_put_contents($filename, json_encode($rows, JSON_PRETTY_PRINT));
        } elseif ($this->format === 'text') {
            $handle = fopen($filename, 'w');
            foreach ($rows as $r) {
                fwrite($handle, implode(' | ', $r) . PHP_EOL);
            }
            fclose($handle);
        } else {
            throw new RuntimeException('Unknown format: ' . $this->format);
        }

        printf(
            "  [%s] file written: %s (%d bytes)\n",
            $this->format,
            basename($filename),
            filesize($filename)
        );
    }
}

$rows = [
    ['id' => 1, 'name' => 'Juan',  'log_type' => 'IN',  'time' => '08:01 AM'],
    ['id' => 2, 'name' => 'Maria', 'log_type' => 'OUT', 'time' => '12:00 PM'],
    ['id' => 3, 'name' => 'Pedro', 'log_type' => 'IN',  'time' => '01:02 PM'],
];

foreach (['csv', 'json', 'text'] as $format) {
    $file = sys_get_temp_dir() . '/lab1_bad_' . $format . '.out';
    (new ReportExporterWithoutAdapter($format))->export($file, $rows);
    unlink($file);
}

echo "  Pain: adding Excel/XML => edit this class again (OCP violation).\n";