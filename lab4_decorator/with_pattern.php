<?php
// Lab 4 - WITH Decorator (GOOD starter, live API, has TODOs)
interface HttpClient {
    public function get(string $url): string;
}
class BaseHttpClient implements HttpClient {
    public function get(string $url): string {
        // Live to real endpoint
        return file_get_contents($url);
    }
}
abstract class HttpDecorator implements HttpClient {
    public function __construct(protected HttpClient $wrapped) {}
}
class LoggingDecorator extends HttpDecorator {
    public function get(string $url): string {
        echo "[Log] GET $url\n";
        $r = $this->wrapped->get($url);
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}
class CachingDecorator extends HttpDecorator {
    private array $cache = [];
    public function get(string $url): string {
        if (isset($this->cache[$url])) { echo "[Cache] Hit $url\n"; return $this->cache[$url]; }
        $r = $this->wrapped->get($url);
        $this->cache[$url] = $r;
        echo "[Cache] Stored $url\n";
        return $r;
    }
}
// TODO: Implement RetryDecorator (3 tries, catch Exception)
class RetryDecorator extends HttpDecorator {
    public function get(string $url): string {
        // TODO: for ($i=0; $i<3; $i++) try { return $this->wrapped->get($url); } catch (Exception $e) { if ($i==2) throw $e; }
        return "[TODO] Retry not implemented";
    }
}
// TODO: Implement TokenCounterDecorator
class TokenCounterDecorator extends HttpDecorator {
    public function get(string $url): string {
        // TODO: $r = $this->wrapped->get($url); echo "[Tokens] " . str_word_count($r) . "\n"; return $r;
        return "[TODO] TokenCounter not implemented";
    }
}
if (basename(__FILE__)===basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Decorator (GOOD - complete TODOs):\n";
    $client = new BaseHttpClient();
    $client = new LoggingDecorator($client);
    $client = new CachingDecorator($client);
    $url = "https://jsonplaceholder.typicode.com/posts/1";
    echo substr($client->get($url),0,60) . "...\n"; // live
    echo substr($client->get($url),0,60) . "...\n"; // cached
    echo "  TODO: Implement Retry + TokenCounter, test with $url\n";
}
