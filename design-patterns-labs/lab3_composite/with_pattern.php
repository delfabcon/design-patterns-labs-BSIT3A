<?php

// Lab 3 - WITH Composite (GOOD, live API, all TODOs completed)
//
// Part A: Forum  -> ForumComponent (component), Post (leaf), Thread (composite)
// Part B: RAG    -> TextComponent (component), Chunk (leaf), Section + Document (composites)
// Part C: Bundle -> NEW leaf added with 1 class, the client code did not change
//
// Run: php with_pattern.php

declare(strict_types=1);

/* ==========================================================================
 * PART A - FORUM COMPOSITE
 * ========================================================================== */

/**
 * Component: the one interface that leaves and composites both follow,
 * so the client can call display() without checking the type.
 */
interface ForumComponent
{
    public function display(int $depth = 0): void;
}

/**
 * Leaf: a single post. It has no children.
 */
class Post implements ForumComponent
{
    private string $message;

    public function __construct(
        private string $author,
        string $message
    ) {
        // API text has line breaks; keep each post on one line.
        $this->message = str_replace("\n", ' ', $message);
    }

    public function display(int $depth = 0): void
    {
        $indent = str_repeat('  ', $depth);
        echo $indent . "- Post by {$this->author}: {$this->message}\n";
    }
}

/**
 * Composite: a thread that holds other ForumComponents (posts, threads, bundles...).
 */
class Thread implements ForumComponent
{
    /** @var ForumComponent[] */
    private array $children = [];

    public function __construct(private string $title)
    {
    }

    public function add(ForumComponent $c): void
    {
        $this->children[] = $c;
    }

    // MISSING 1 (done): print myself, then let every child print itself one level deeper.
    public function display(int $depth = 0): void
    {
        echo str_repeat('  ', $depth) . "+ Thread: {$this->title}\n";

        foreach ($this->children as $child) {
            $child->display($depth + 1);
        }
    }

    public static function fromApi(int $postId): self
    {
        $post = fetchJson(
            "https://jsonplaceholder.typicode.com/posts/$postId",
            OfflineData::post()
        );

        $thread = new self($post['title']);
        $thread->add(new Post("Author {$post['userId']}", substr($post['body'], 0, 40) . '...'));

        $comments = fetchJson(
            "https://jsonplaceholder.typicode.com/posts/$postId/comments",
            OfflineData::comments()
        );

        $replies = new Thread('Replies');
        foreach (array_slice($comments, 0, 2) as $c) {
            $replies->add(new Post($c['email'], substr($c['body'], 0, 30) . '...'));
        }
        $thread->add($replies);

        return $thread;
    }
}

/* ==========================================================================
 * PART C - EXTENSIBILITY DEMO: Bundle (1 new class, no client changes)
 * ========================================================================== */

/**
 * NEW Leaf: a pre-set combo of items that is shown as one block
 * (for example, a "Welcome Pack" pinned at the top of a thread).
 * Thread and the client code were NOT edited to support this.
 */
class Bundle implements ForumComponent
{
    /** @param string[] $items */
    public function __construct(
        private string $name,
        private array $items
    ) {
    }

    public function display(int $depth = 0): void
    {
        $indent = str_repeat('  ', $depth);
        echo $indent . "* Bundle: {$this->name} (" . count($this->items) . " items)\n";

        foreach ($this->items as $item) {
            echo $indent . "    > $item\n";
        }
    }
}

/* ==========================================================================
 * PART B - RAG DOCUMENT COMPOSITE (MISSING 2)
 * Same recursive shape as Thread, just renamed: Document -> Section -> Chunk
 * ========================================================================== */

/**
 * Component: anything in the document tree can give its text and its embeddings.
 */
interface TextComponent
{
    public function getText(): string;

    /** @return array<int, float[]> one vector per chunk */
    public function embed(): array;
}

/**
 * Leaf: the smallest piece of text that gets embedded.
 */
class Chunk implements TextComponent
{
    public function __construct(private string $text)
    {
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function embed(): array
    {
        // A real system would call an embedding API here.
        // For the lab I use a tiny fake 3-number vector so the output can be checked by hand:
        // [word count, character count, simple hash between 0 and 1]
        $vector = [
            (float) str_word_count($this->text),
            (float) strlen($this->text),
            round((crc32($this->text) % 1000) / 1000, 3),
        ];

        return [$vector];
    }
}

/**
 * Shared composite logic for Section and Document (so I don't copy-paste the loop twice).
 */
abstract class TextComposite implements TextComponent
{
    /** @var TextComponent[] */
    protected array $children = [];

    public function __construct(protected string $title)
    {
    }

    public function add(TextComponent $c): static
    {
        $this->children[] = $c;
        return $this;
    }

    // Recursively join the text of all children.
    public function getText(): string
    {
        $parts = [$this->heading()];

        foreach ($this->children as $child) {
            $parts[] = $child->getText();
        }

        return implode("\n", $parts);
    }

    // Recursively collect every chunk's vector into one flat list.
    public function embed(): array
    {
        $vectors = [];

        foreach ($this->children as $child) {
            $vectors = array_merge($vectors, $child->embed());
        }

        return $vectors;
    }

    abstract protected function heading(): string;
}

/**
 * Composite: a section inside a document (can hold chunks or sub-sections).
 */
class Section extends TextComposite
{
    protected function heading(): string
    {
        return "## {$this->title}";
    }
}

/**
 * Composite: the root of the tree.
 */
class Document extends TextComposite
{
    protected function heading(): string
    {
        return "# {$this->title}";
    }
}

/* ==========================================================================
 * HELPERS - live fetch with offline fallback
 * ========================================================================== */

function fetchJson(string $url, array $fallback): array
{
    $json = @file_get_contents($url);

    if ($json === false) {
        echo "(offline: using saved sample data for $url)\n";
        return $fallback;
    }

    return json_decode($json, true);
}

final class OfflineData
{
    // Same values jsonplaceholder returns for post 1 and its first 2 comments.
    public static function post(): array
    {
        return [
            'userId' => 1,
            'id'     => 1,
            'title'  => 'sunt aut facere repellat provident occaecati excepturi optio reprehenderit',
            'body'   => "quia et suscipit\nsuscipit recusandae consequuntur expedita et cum\n"
                . 'reprehenderit molestiae ut ut quas totam',
        ];
    }

    public static function comments(): array
    {
        return [
            [
                'email' => 'Eliseo@gardner.biz',
                'body'  => "laudantium enim quasi est quidem magnam voluptate ipsam eos\ntempora quo necessitatibus",
            ],
            [
                'email' => 'Jayne_Kuhic@sydney.com',
                'body'  => "est natus enim nihil est dolore omnis voluptatem numquam\net omnis occaecati quod",
            ],
        ];
    }
}

/* ==========================================================================
 * CLIENT / DEMO
 * ========================================================================== */

/**
 * The client only knows the interface. It never uses instanceof.
 */
function renderForum(ForumComponent $root): void
{
    $root->display();
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Composite (GOOD - TODOs completed):\n\n";

    // 1) Forum tree from the live API
    echo "=== 1. Forum tree (Thread::display loop fixed) ===\n";
    $thread = Thread::fromApi(1);
    renderForum($thread);

    // 2) Extensibility: add a Bundle leaf. Only 1 new class, renderForum() untouched.
    echo "\n=== 2. Extensibility: new Bundle leaf (1 class, no client change) ===\n";
    $thread->add(new Bundle('Welcome Pack', ['Forum rules', 'FAQ link', 'Intro template']));
    renderForum($thread);

    // 3) RAG variant: Document -> Section -> Chunk
    echo "\n=== 3. RAG variant: Document -> Section -> Chunk ===\n";
    $doc = new Document('IPT Lab Notes');

    $doc->add(
        (new Section('Composite'))
            ->add(new Chunk('Compose objects into tree structures.'))
            ->add(new Chunk('Leaf and composite share one interface.'))
    );

    $doc->add(
        (new Section('Decorator'))
            ->add(new Chunk('Attach responsibilities dynamically.'))
            ->add(
                (new Section('Example'))
                    ->add(new Chunk('Logging wraps Caching wraps Base client.'))
            )
    );

    echo "--- getText() on the whole document ---\n";
    echo $doc->getText() . "\n";

    echo "--- embed() on the whole document ---\n";
    foreach ($doc->embed() as $i => $vector) {
        echo "  chunk $i => [" . implode(', ', $vector) . "]\n";
    }
    echo '  total vectors: ' . count($doc->embed()) . "\n";

    echo "\nDone: one display()/getText()/embed() call works on any node, no instanceof.\n";
}
