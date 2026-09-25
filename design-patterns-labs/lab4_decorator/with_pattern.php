<?php

// Lab 4 - WITH Decorator (GOOD, live API pipeline, all TODOs completed)
//
// Component:          HttpClient
// ConcreteComponent:  BaseHttpClient (live) / OfflineHttpClient (fallback when no internet)
// Decorator:          HttpDecorator (has-a HttpClient)
// ConcreteDecorators: Logging, Caching, Retry, TokenCounter
// Extensibility demo: TimingDecorator (1 new class, nothing else edited)
//
// Run: php with_pattern.php

declare(strict_types=1);

/* ==========================================================================
 * COMPONENT + CONCRETE COMPONENTS
 * ========================================================================== */

interface HttpClient
{
    public function get(string $url): string;
}

class BaseHttpClient implements HttpClient
{
    public function get(string $url): string
    {
        // Live call to the real endpoint.
        $r = @file_get_contents($url);

        // Throw instead of returning false, so RetryDecorator has something to catch.
        if ($r === false) {
            throw new RuntimeException("Request failed: $url");
        }

        return $r;
    }
}

/**
 * Used only when the computer has no internet, so the demo still runs.
 * It is just another HttpClient, so every decorator works on it the same way.
 */
class OfflineHttpClient implements HttpClient
{
    public function get(string $url): string
    {
        return '{"userId": 1, "id": 1, "title": "sunt aut facere repellat provident occaecati", '
            . '"body": "quia et suscipit suscipit recusandae consequuntur expedita et cum"}';
    }
}

/**
 * Test helper: fails the first N calls, then works. Used to prove RetryDecorator.
 */
class FlakyHttpClient implements HttpClient
{
    private int $calls = 0;

    public function __construct(
        private HttpClient $real,
        private int $failuresBeforeSuccess
    ) {
    }

    public function get(string $url): string
    {
        $this->calls++;

        if ($this->calls <= $this->failuresBeforeSuccess) {
            throw new RuntimeException("Simulated timeout #{$this->calls}");
        }

        return $this->real->get($url);
    }
}

/* ==========================================================================
 * DECORATOR BASE
 * ========================================================================== */

abstract class HttpDecorator implements HttpClient
{
    public function __construct(protected HttpClient $wrapped)
    {
    }
}

/* ==========================================================================
 * CONCRETE DECORATORS
 * ========================================================================== */

class LoggingDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        echo "[Log] GET $url\n";
        $r = $this->wrapped->get($url);
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}

class CachingDecorator extends HttpDecorator
{
    private array $cache = [];

    public function get(string $url): string
    {
        if (isset($this->cache[$url])) {
            echo "[Cache] Hit $url\n";
            return $this->cache[$url];
        }

        $r = $this->wrapped->get($url);
        $this->cache[$url] = $r;
        echo "[Cache] Stored $url\n";
        return $r;
    }
}

// MISSING 1 (done): try up to 3 times, rethrow on the last failure.
class RetryDecorator extends HttpDecorator
{
    private const MAX_TRIES = 3;

    public function get(string $url): string
    {
        for ($i = 0; $i < self::MAX_TRIES; $i++) {
            try {
                return $this->wrapped->get($url);
            } catch (Exception $e) {
                $attempt = $i + 1;
                echo "[Retry] Attempt $attempt failed: {$e->getMessage()}\n";

                if ($i === self::MAX_TRIES - 1) {
                    throw $e;
                }
            }
        }

        // Never reached, but keeps static analysers happy.
        throw new LogicException('Retry loop ended unexpectedly');
    }
}

// MISSING 2 (done): call the wrapped client, then print the word count.
class TokenCounterDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        $r = $this->wrapped->get($url);
        echo "[Tokens] " . str_word_count($r) . "\n";
        return $r;
    }
}

/* ==========================================================================
 * EXTENSIBILITY DEMO - 1 new class, no other class was edited
 * ========================================================================== */

class TimingDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        $start = microtime(true);
        $r = $this->wrapped->get($url);
        $ms = round((microtime(true) - $start) * 1000, 2);
        echo "[Timing] {$ms} ms\n";
        return $r;
    }
}

/* ==========================================================================
 * DEMO
 * ========================================================================== */

/**
 * Wrap a client with the given decorator class names, innermost first.
 * Example: ['LoggingDecorator', 'CachingDecorator'] => Caching(Logging(base))
 *
 * @param class-string<HttpDecorator>[] $decorators
 */
function buildPipeline(HttpClient $base, array $decorators): HttpClient
{
    $client = $base;

    foreach ($decorators as $decoratorClass) {
        $client = new $decoratorClass($client);
    }

    return $client;
}

function pickBaseClient(string $url): HttpClient
{
    $live = new BaseHttpClient();

    try {
        $live->get($url);
        echo "(online: using live jsonplaceholder API)\n";
        return $live;
    } catch (RuntimeException) {
        echo "(offline: using OfflineHttpClient sample data)\n";
        return new OfflineHttpClient();
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Decorator (GOOD - TODOs completed):\n";

    $url = 'https://jsonplaceholder.typicode.com/posts/1';
    $base = pickBaseClient($url);

    // ---------------------------------------------------------------------
    echo "\n=== 1. Starter demo: Caching(Logging(Base)) ===\n";
    $client = $base; // BaseHttpClient when online
    $client = new LoggingDecorator($client);
    $client = new CachingDecorator($client);
    echo substr($client->get($url), 0, 60) . "...\n"; // live
    echo substr($client->get($url), 0, 60) . "...\n"; // cached

    // ---------------------------------------------------------------------
    echo "\n=== 2. RetryDecorator: base fails 2 times, then works ===\n";
    $client = new RetryDecorator(new FlakyHttpClient($base, 2));
    echo substr($client->get($url), 0, 60) . "...\n";

    echo "\n--- RetryDecorator: base fails 5 times (gives up after 3) ---\n";
    $client = new RetryDecorator(new FlakyHttpClient($base, 5));
    try {
        $client->get($url);
    } catch (RuntimeException $e) {
        echo "Final error rethrown to caller: {$e->getMessage()}\n";
    }

    // ---------------------------------------------------------------------
    echo "\n=== 3. TokenCounterDecorator ===\n";
    $client = new TokenCounterDecorator($base);
    echo substr($client->get($url), 0, 60) . "...\n";

    // ---------------------------------------------------------------------
    // MISSING 3 (done): the order of wrapping changes where the log line appears.
    echo "\n=== 4. ORDER MATTERS ===\n";

    echo "\n--- A) Logging(Caching(Base)): Logging is OUTSIDE ---\n";
    $a = new LoggingDecorator(new CachingDecorator($base));
    echo "call 1:\n";
    $a->get($url);
    echo "call 2:\n";
    $a->get($url);
    echo "=> [Log] prints on BOTH calls, even the cache hit (every request is logged).\n";

    echo "\n--- B) Caching(Logging(Base)): Caching is OUTSIDE ---\n";
    $b = new CachingDecorator(new LoggingDecorator($base));
    echo "call 1:\n";
    $b->get($url);
    echo "call 2:\n";
    $b->get($url);
    echo "=> [Log] prints only on call 1. The cache answers call 2 before Logging is reached\n";
    echo "   (only real network calls are logged).\n";

    // ---------------------------------------------------------------------
    echo "\n=== 5. Full chain from the UML: Retry(Caching(Logging(Base))) ===\n";
    $full = new RetryDecorator(new CachingDecorator(new LoggingDecorator($base)));
    $full->get($url);
    $full->get($url);

    // ---------------------------------------------------------------------
    echo "\n=== 6. 4 decorators => 16 combos, still only 4 decorator classes + 1 base ===\n";
    $all = [
        LoggingDecorator::class,
        CachingDecorator::class,
        RetryDecorator::class,
        TokenCounterDecorator::class,
    ];

    $comboCount = 0;
    for ($mask = 0; $mask < 2 ** count($all); $mask++) {
        $chosen = [];
        foreach ($all as $bit => $class) {
            if ($mask & (1 << $bit)) {
                $chosen[] = $class;
            }
        }

        $client = buildPipeline($base, $chosen);

        ob_start();                 // hide the decorator echo lines for this table
        $body = $client->get($url);
        ob_end_clean();

        $comboCount++;
        $names = $chosen === []
            ? 'BaseHttpClient only'
            : implode(' + ', array_map(fn ($c) => str_replace('Decorator', '', $c), $chosen));

        printf("  %2d. %-40s -> %d bytes OK\n", $comboCount, $names, strlen($body));
    }
    echo "  Total: $comboCount working combinations, 0 new classes needed.\n";
    echo "  (With inheritance this would need 2^4 = 16 separate classes.)\n";

    // ---------------------------------------------------------------------
    echo "\n=== 7. Extensibility: new TimingDecorator (1 class, nothing else edited) ===\n";
    $client = new TimingDecorator(new TokenCounterDecorator(new LoggingDecorator($base)));
    echo substr($client->get($url), 0, 60) . "...\n";
}
