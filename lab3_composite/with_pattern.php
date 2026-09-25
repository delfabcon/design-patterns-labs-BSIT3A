<?php
// Lab 3 - WITH Composite (GOOD starter, live API, has TODOs)
interface ForumComponent {
    public function display(int $depth = 0): void;
}
class Post implements ForumComponent {
    public function __construct(private string $author, private string $message) {}
    public function display(int $depth = 0): void {
        $indent = str_repeat("  ", $depth);
        echo $indent . "- Post by {$this->author}: {$this->message}\n";
    }
}
class Thread implements ForumComponent {
    /** @var ForumComponent[] */
    private array $children = [];
    public function __construct(private string $title) {}
    public function add(ForumComponent $c): void { $this->children[] = $c; }
    public function display(int $depth = 0): void {
        // TODO: loop over $children and display with $depth+1
        // foreach ($this->children as $child) { $child->display($depth + 1); }
        echo str_repeat("  ", $depth) . "+ Thread: {$this->title} [TODO]\n";
    }
    public static function fromApi(int $postId): self {
        $postJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/$postId");
        $post = json_decode($postJson, true);
        $thread = new self($post["title"]);
        $thread->add(new Post("Author {$post['userId']}", substr($post["body"],0,40)."..."));
        $commentsJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/$postId/comments");
        $comments = json_decode($commentsJson, true);
        $replies = new Thread("Replies");
        foreach (array_slice($comments,0,2) as $c) $replies->add(new Post($c["email"], substr($c["body"],0,30)."..."));
        $thread->add($replies);
        return $thread;
    }
}
// TODO: Create RAG variant: Document/Section/Chunk with getText() and embed()
if (basename(__FILE__)===basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Composite (GOOD - complete TODOs):\n";
    $thread = Thread::fromApi(1); // live to jsonplaceholder.typicode.com
    $thread->display();
    echo "  TODO: Fix Thread::display loop, create Document/Section/Chunk RAG.\n";
}
