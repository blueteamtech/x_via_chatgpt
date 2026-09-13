<?php

use App\Mcp\Servers\XConnectorServer;
use App\Mcp\Tools\XUserLookupTool;
use Illuminate\Support\Facades\Http;

it('looks up a user by username and strips a leading @', function () {
    Http::fake(['*users/by/username/naval*' => Http::response(['data' => ['id' => '745273', 'username' => 'naval', 'name' => 'Naval']])]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XUserLookupTool::class, ['username' => '@naval'])
        ->assertHasNoErrors()
        ->assertSee('"username":"naval"');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/users/by/username/naval'));
});

it('rejects usernames with invalid characters', function () {
    Http::fake();

    XConnectorServer::actingAs(connectedUser())
        ->tool(XUserLookupTool::class, ['username' => 'has spaces'])
        ->assertHasErrors();

    Http::assertNothingSent();
});
