<?php
// Lab 4 - WITHOUT Decorator (BAD, live pipeline explosion)
class BaseHttpClient {
    public function get(string $url): string { return file_get_contents($url); }
}
class LoggingHttpClient extends BaseHttpClient {
    public function get(string $url): string {
        echo "[Log] GET $url\n";
        $r = parent::get($url);
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}
class CachingHttpClient extends BaseHttpClient {
    private array $cache = [];
    public function get(string $url): string {
        if (isset($this->cache[$url])) { echo "[Cache] Hit $url\n"; return $this->cache[$url]; }
        $r = parent::get($url);
        $this->cache[$url] = $r;
        echo "[Cache] Stored $url\n";
        return $r;
    }
}
class LoggingCachingHttpClient extends LoggingHttpClient {
    private array $cache = [];
    public function get(string $url): string {
        echo "[Log] GET $url\n";
        if (isset($this->cache[$url])) { echo "[Cache] Hit $url\n"; return $this->cache[$url]; }
        $r = parent::get($url);
        $this->cache[$url] = $r;
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}
if (basename(__FILE__)===basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITHOUT Decorator (BAD, live):\n";
    $c = new LoggingHttpClient();
    echo substr($c->get("https://jsonplaceholder.typicode.com/posts/1"),0,60) . "...\n";
    echo "  3 decorators => 8 classes. Adding Retry => 16.\n";
}
