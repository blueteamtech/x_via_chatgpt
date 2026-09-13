<?php

use App\Mcp\Servers\XConnectorServer;
use App\Mcp\Tools\XArticlesTool;
use App\Models\Article;
use Illuminate\Support\Facades\Http;

it('creates a draft on X and stores it locally', function () {
    Http::fake(['*articles/draft' => Http::response(['data' => ['id' => 'x-art-1']])]);

    $user = connectedUser();

    XConnectorServer::actingAs($user)
        ->tool(XArticlesTool::class, [
            'action' => 'draft',
            'title' => 'My Article',
            'body' => "First paragraph.\n\nSecond paragraph.",
        ])
        ->assertHasNoErrors()
        ->assertSee(['"x_article_id":"x-art-1"', '"status":"draft"']);

    $article = Article::where('user_id', $user->id)->first();

    expect($article)->not->toBeNull()
        ->and($article->x_article_id)->toBe('x-art-1')
        ->and($article->title)->toBe('My Article');

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $body['title'] === 'My Article'
            && count($body['content_state']['blocks']) === 2
            && $body['content_state']['blocks'][0]['text'] === 'First paragraph.';
    });
});

it('lists a user\'s own articles only', function () {
    $mine = connectedUser();
    $other = connectedUser();

    Article::create(['user_id' => $mine->id, 'title' => 'Mine', 'body' => 'body']);
    Article::create(['user_id' => $other->id, 'title' => 'Theirs', 'body' => 'body']);

    XConnectorServer::actingAs($mine)
        ->tool(XArticlesTool::class, ['action' => 'list'])
        ->assertHasNoErrors()
        ->assertSee('"title":"Mine"')
        ->assertDontSee('"title":"Theirs"');
});

it('publishes a draft by article id', function () {
    Http::fake(['*articles/*/publish' => Http::response(['data' => ['id' => 'x-art-99']])]);

    $user = connectedUser();
    $article = Article::create([
        'user_id' => $user->id,
        'title' => 'To Publish',
        'body' => 'body',
        'x_article_id' => 'x-art-99',
    ]);

    XConnectorServer::actingAs($user)
        ->tool(XArticlesTool::class, ['action' => 'publish', 'article_id' => $article->id])
        ->assertHasNoErrors()
        ->assertSee('"status":"published"');

    expect($article->fresh()->published_at)->not->toBeNull();
    Http::assertSent(fn ($request) => str_contains($request->url(), '/articles/x-art-99/publish'));
});

it('refuses to publish another user\'s article', function () {
    $me = connectedUser();
    $other = connectedUser();
    $article = Article::create(['user_id' => $other->id, 'title' => 'Not mine', 'body' => 'body', 'x_article_id' => 'x-art-x']);

    Http::fake();

    XConnectorServer::actingAs($me)
        ->tool(XArticlesTool::class, ['action' => 'publish', 'article_id' => $article->id])
        ->assertHasErrors()
        ->assertSee('Article not found');

    Http::assertNothingSent();
});
