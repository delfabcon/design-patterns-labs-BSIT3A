<?php
// Lab 2 - WITHOUT Bridge (BAD, data + compression duplicated in every class)
class AttendanceJsonGzipReport
{
    public function generate(): string
    {
        // Same fetch duplicated in every class
        $json = file_get_contents("https://jsonplaceholder.typicode.com/posts?_limit=4");
        $data = json_decode($json, true);
        $body = "TIMES\n" . json_encode($data);
        return gzencode($body); // same compression logic duplicated
    }
}

class AttendanceJsonPlainReport
{
    public function generate(): string
    {
        $json = file_get_contents("https://jsonplaceholder.typicode.com/posts?_limit=4");
        $data = json_decode($json, true);
        return "TIMES\n" . json_encode($data);
    }
}

class AttendanceTextGzipReport
{
    public function generate(): string
    {
        $json = file_get_contents("https://jsonplaceholder.typicode.com/posts?_limit=4");
        $data = json_decode($json, true);
        $lines = [];
        foreach ($data as $r) {
            $lines[] = $r["title"];
        }
        return gzencode("TIMES\n" . implode("\n", $lines));
    }
}

// 2 formats x 2 compressions = 4 classes; add CSV or zip -> +3 each. Explosion.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITHOUT Bridge:\n";
    echo "  AttendanceJsonGzipReport / AttendanceJsonPlainReport / AttendanceTextGzipReport ...\n";
    echo "  2 formats x 2 compressions = 4 classes. Add CSV -> +2, add zip -> +3. Explosion.\n";
}