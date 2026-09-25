<?php
// Lab 3 - WITHOUT Composite (BAD, live forum tree with instanceof)
class PostNaive { public function __construct(public string $author, public string $message) {} }
class ThreadNaive {
    public array $children = [];
    public function __construct(public string $title) {}
    public function add(mixed $c): void { $this->children[] = $c; }
}
function renderNaive(mixed $el, int $depth=0): string {
    $indent = str_repeat("  ", $depth);
    if ($el instanceof PostNaive) return $indent . "- Post by {$el->author}: {$el->message}\n";
    if ($el instanceof ThreadNaive) {
        $html = $indent . "+ Thread: {$el->title}\n";
        foreach ($el->children as $c) $html .= renderNaive($c, $depth+1);
        return $html;
    }
    return '';
}
if (basename(__FILE__)===basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITHOUT Composite (BAD, live API tree):\n";
    // Live fetch for demo
    $postJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/1");
    $post = json_decode($postJson, true);
    $thread = new ThreadNaive($post['title']);
    $thread->add(new PostNaive("User {$post['userId']}", substr($post['body'],0,30)."..."));
    $commentsJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/1/comments");
    $comments = json_decode($commentsJson, true);
    $replies = new ThreadNaive("Replies");
    foreach (array_slice($comments,0,2) as $c) $replies->add(new PostNaive($c['email'], substr($c['body'],0,25)."..."));
    $thread->add($replies);
    echo renderNaive($thread);
    echo "  instanceof everywhere; adding Poll => edit renderNaive.\n";
}
