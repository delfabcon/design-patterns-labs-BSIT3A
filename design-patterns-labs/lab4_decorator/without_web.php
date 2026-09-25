<?php

// Lab 4 - WITHOUT Decorator (BAD, live pipeline explosion)
// Copied from the lab handout. I only added an offline fallback so it still
// runs when jsonplaceholder.typicode.com cannot be reached.

declare(strict_types=1);

class BaseHttpClient
{
    public function get(string $url): string
    {
        $r = @file_get_contents($url);

        if ($r === false) {
            echo "(offline: using saved sample data)\n";
            $r = '{"userId": 1, "id": 1, "title": "sunt aut facere repellat provident occaecati", '
                . '"body": "quia et suscipit suscipit recusandae consequuntur expedita et cum"}';
        }

        return $r;
    }
}

class LoggingHttpClient extends BaseHttpClient
{
    public function get(string $url): string
    {
        echo "[Log] GET $url\n";
        $r = parent::get($url);
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}

class CachingHttpClient extends BaseHttpClient
{
    private array $cache = [];

    public function get(string $url): string
    {
        if (isset($this->cache[$url])) {
            echo "[Cache] Hit $url\n";
            return $this->cache[$url];
        }

        $r = parent::get($url);
        $this->cache[$url] = $r;
        echo "[Cache] Stored $url\n";
        return $r;
    }
}

// SMELL: to get Logging + Caching together I must write a new class
// and copy the caching code again. Every new combo = another class.
class LoggingCachingHttpClient extends LoggingHttpClient
{
    private array $cache = [];

    public function get(string $url): string
    {
        echo "[Log] GET $url\n";

        if (isset($this->cache[$url])) {
            echo "[Cache] Hit $url\n";
            return $this->cache[$url];
        }

        $r = parent::get($url);
        $this->cache[$url] = $r;
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITHOUT Decorator (BAD, live):\n";
    $c = new LoggingHttpClient();
    echo substr($c->get('https://jsonplaceholder.typicode.com/posts/1'), 0, 60) . "...\n";
    echo "3 decorators => 8 classes. Adding Retry => 16.\n";
}
