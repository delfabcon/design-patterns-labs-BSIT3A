<?php

// Lab 3 - WITHOUT Composite (BAD, live forum tree with instanceof)
// Copied from the lab handout. I only added an offline fallback so it still
// runs when jsonplaceholder.typicode.com cannot be reached.

declare(strict_types=1);

class PostNaive
{
    public function __construct(public string $author, public string $message)
    {
        $this->message = str_replace("\n", ' ', $message);
    }
}

class ThreadNaive
{
    public array $children = [];

    public function __construct(public string $title)
    {
    }

    public function add(mixed $c): void
    {
        $this->children[] = $c;
    }
}

// SMELL: the render function has to ask "what type are you?" for every node.
function renderNaive(mixed $el, int $depth = 0): string
{
    $indent = str_repeat('  ', $depth);

    if ($el instanceof PostNaive) {
        return $indent . "- Post by {$el->author}: {$el->message}\n";
    }

    if ($el instanceof ThreadNaive) {
        $html = $indent . "+ Thread: {$el->title}\n";
        foreach ($el->children as $c) {
            $html .= renderNaive($c, $depth + 1);
        }
        return $html;
    }

    return '';
}

function fetchJson(string $url, array $fallback): array
{
    $json = @file_get_contents($url);
    return $json === false ? $fallback : json_decode($json, true);
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITHOUT Composite (BAD, live API tree):\n";

    $post = fetchJson('https://jsonplaceholder.typicode.com/posts/1', [
        'userId' => 1,
        'title'  => 'sunt aut facere repellat provident occaecati excepturi optio reprehenderit',
        'body'   => "quia et suscipit\nsuscipit recusandae consequuntur expedita et cum",
    ]);

    $thread = new ThreadNaive($post['title']);
    $thread->add(new PostNaive("User {$post['userId']}", substr($post['body'], 0, 30) . '...'));

    $comments = fetchJson('https://jsonplaceholder.typicode.com/posts/1/comments', [
        ['email' => 'Eliseo@gardner.biz', 'body' => 'laudantium enim quasi est quidem magnam voluptate'],
        ['email' => 'Jayne_Kuhic@sydney.com', 'body' => 'est natus enim nihil est dolore omnis voluptatem'],
    ]);

    $replies = new ThreadNaive('Replies');
    foreach (array_slice($comments, 0, 2) as $c) {
        $replies->add(new PostNaive($c['email'], substr($c['body'], 0, 25) . '...'));
    }
    $thread->add($replies);

    echo renderNaive($thread);
    echo "instanceof everywhere; adding Poll => edit renderNaive.\n";
}
