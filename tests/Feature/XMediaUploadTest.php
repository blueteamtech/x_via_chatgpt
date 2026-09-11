<?php

use App\Mcp\Servers\XConnectorServer;
use App\Mcp\Tools\XUploadMediaTool;
use Illuminate\Support\Facades\Http;

// An IP literal keeps the private-address rule from needing a DNS lookup.
const IMAGE_URL = 'https://93.184.216.34/photo.png';

it('refuses a URL that is not an image', function () {
    Http::fake(['93.184.216.34/*' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html'])]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XUploadMediaTool::class, ['image_url' => IMAGE_URL])
        ->assertHasErrors()
        ->assertSee('not a JPEG');
});

it('refuses an image larger than X accepts', function () {
    Http::fake(['93.184.216.34/*' => Http::response(str_repeat('a', 6 * 1024 * 1024), 200, ['Content-Type' => 'image/png'])]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XUploadMediaTool::class, ['image_url' => IMAGE_URL])
        ->assertHasErrors()
        ->assertSee('larger than the 5 MB limit');
});

it('uploads an accepted image and names it for its real type', function () {
    Http::fake([
        '93.184.216.34/*' => Http::response('fake-png-bytes', 200, ['Content-Type' => 'image/png']),
        'api.x.com/*' => Http::response(['data' => ['id' => '999']]),
    ]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XUploadMediaTool::class, ['image_url' => IMAGE_URL])
        ->assertHasNoErrors();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/media/upload')
        && str_contains((string) $request->body(), 'upload.png')
        && str_contains((string) $request->body(), 'fake-png-bytes'));
});

// The URL is checked against private addresses before the download, so a
// redirect is refused outright rather than followed somewhere unchecked.
it('treats a redirect as a failed download', function () {
    Http::fake(['93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/'])]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XUploadMediaTool::class, ['image_url' => IMAGE_URL])
        ->assertHasErrors()
        ->assertSee('Could not download');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '169.254.169.254'));
});

it('reports X errors through the shared client instead of leaking the raw body', function () {
    Http::fake([
        '93.184.216.34/*' => Http::response('fake-png-bytes', 200, ['Content-Type' => 'image/png']),
        'api.x.com/*' => Http::response(['detail' => 'Media type not supported.'], 400),
    ]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XUploadMediaTool::class, ['image_url' => IMAGE_URL])
        ->assertHasErrors()
        ->assertSee('X API error 400: Media type not supported.');
});
