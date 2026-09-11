<?php

namespace App\Mcp\Tools;

use App\Exceptions\XApiException;
use App\Rules\NoPrivateIpUrl;
use App\Services\X\XApiClient;
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
    /**
     * X rejects images above 5 MB, so there is no reason to hold a larger one
     * in memory. The download stops as soon as this is exceeded.
     */
    protected const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    /**
     * Content types X accepts, mapped to the extension it expects to see.
     *
     * @var array<string, string>
     */
    protected const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'image_url' => ['required', 'url:https', new NoPrivateIpUrl],
        ]);

        return $this->respond($request, function (XApiClient $x) use ($validated) {
            [$contents, $filename] = $this->download($validated['image_url']);

            return $x->upload('/media/upload', $contents, $filename, [
                'media_category' => 'tweet_image',
            ]);
        });
    }

    /**
     * Fetch the image, refusing anything too large or not an image.
     *
     * Redirects are not followed: the URL was checked against private and
     * reserved addresses before we got here, and a redirect would let a public
     * host bounce the request onto the internal network afterwards.
     *
     * @return array{0: string, 1: string}
     */
    protected function download(string $url): array
    {
        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->withOptions(['stream' => true, 'allow_redirects' => false])
            ->get($url);

        if (! $response->successful()) {
            throw new XApiException('Could not download that image URL.');
        }

        $type = strtolower(strtok((string) $response->header('Content-Type'), ';') ?: '');

        if (! array_key_exists($type, self::ALLOWED_TYPES)) {
            throw new XApiException('That URL is not a JPEG, PNG, GIF, or WebP image.');
        }

        $body = $response->toPsrResponse()->getBody();
        $contents = '';

        while (! $body->eof() && strlen($contents) <= self::MAX_IMAGE_BYTES) {
            $contents .= $body->read(8192);
        }

        if (strlen($contents) > self::MAX_IMAGE_BYTES) {
            throw new XApiException('That image is larger than the 5 MB limit X accepts.');
        }

        return [$contents, 'upload.'.self::ALLOWED_TYPES[$type]];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'image_url' => $schema->string()->description('Public HTTPS image URL to upload.')->required(),
        ];
    }
}
