<?php

namespace App\Mcp\Tools;

use App\Exceptions\XApiException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Upload an image from a public HTTPS URL to X and return a media_id for x-create-post.')]
class XUploadMediaTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'image_url' => ['required', 'url:https'],
        ]);

        return $this->respond($request, function ($x) use ($request, $validated) {
            $download = Http::timeout(20)->get($validated['image_url']);

            if ($download->failed()) {
                throw new XApiException('Could not download the image URL.');
            }

            $user = $request->user();
            $response = Http::baseUrl(config('x.api_base'))
                ->withToken((string) $user->x_access_token)
                ->attach('media', $download->body(), 'upload.jpg')
                ->post('/media/upload', [
                    'media_category' => 'tweet_image',
                ]);

            if ($response->failed()) {
                throw new XApiException('X media upload failed: '.$response->body());
            }

            return $response->json() ?? ['raw' => $response->body()];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'image_url' => $schema->string()->description('Public HTTPS image URL to upload.')->required(),
        ];
    }
}
