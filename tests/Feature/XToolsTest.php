<?php

use App\Mcp\Servers\XConnectorServer;
use App\Mcp\Tools\XCreatePostTool;
use App\Mcp\Tools\XDirectMessagesTool;
use App\Mcp\Tools\XListsTool;
use App\Mcp\Tools\XMeTool;
use App\Mcp\Tools\XReadFeedTool;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function connectedUser(): User
{
    return User::factory()->create([
        'x_access_token' => 'test-access-token',
        'x_token_expires_at' => now()->addHour(),
    ]);
}

it('asks for the missing argument instead of calling X with an empty id', function (string $tool, array $arguments, string $missing) {
    Http::fake();

    XConnectorServer::actingAs(connectedUser())
        ->tool($tool, $arguments)
        ->assertHasErrors();

    Http::assertNothingSent();
})->with([
    'read a post without an id' => [XReadFeedTool::class, ['view' => 'post'], 'post_id'],
    'search without a query' => [XReadFeedTool::class, ['view' => 'search'], 'query'],
    'delete a list without an id' => [XListsTool::class, ['action' => 'delete'], 'list_id'],
    'add a member without a list' => [XListsTool::class, ['action' => 'add_member', 'member_user_id' => '1'], 'list_id'],
    'create a list without a name' => [XListsTool::class, ['action' => 'create'], 'name'],
    'send a DM without a recipient' => [XDirectMessagesTool::class, ['action' => 'send', 'text' => 'hi'], 'participant_id'],
    'send a DM without text' => [XDirectMessagesTool::class, ['action' => 'send', 'participant_id' => '1'], 'text'],
]);

it('still calls X when the conditional arguments are present', function () {
    Http::fake(['*' => Http::response(['data' => ['id' => '123']])]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XReadFeedTool::class, ['view' => 'post', 'post_id' => '123'])
        ->assertHasNoErrors();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/tweets/123'));
});

it('tells the model when X rate limited the request', function () {
    Http::fake(['*' => Http::response(['title' => 'Too Many Requests'], 429)]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XMeTool::class)
        ->assertHasErrors()
        ->assertSee('rate limit');
});

it('reports the X status code so the model can tell failures apart', function () {
    Http::fake(['*' => Http::response(['detail' => 'Post not found.'], 404)]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XReadFeedTool::class, ['view' => 'post', 'post_id' => '123'])
        ->assertHasErrors()
        ->assertSee('X API error 404');
});

it('retries a dropped read but never replays a write', function () {
    $attempts = ['GET' => 0, 'POST' => 0];

    Http::fake(function ($request) use (&$attempts) {
        $attempts[$request->method()]++;

        throw new ConnectionException('Connection reset.');
    });

    $user = connectedUser();

    XConnectorServer::actingAs($user)->tool(XReadFeedTool::class, ['view' => 'home']);
    XConnectorServer::actingAs($user)->tool(XCreatePostTool::class, ['text' => 'hello']);

    expect($attempts['GET'])->toBe(2)
        ->and($attempts['POST'])->toBe(1);
});
