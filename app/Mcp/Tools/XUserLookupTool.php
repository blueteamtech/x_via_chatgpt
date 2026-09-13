<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsOpenWorld]
#[Description('Look up an X user profile by @username. Returns id, name, bio, follower / following / post counts, verified status, and account creation date.')]
class XUserLookupTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:15', 'regex:/^@?[A-Za-z0-9_]+$/'],
        ]);

        $username = ltrim($validated['username'], '@');

        return $this->respond($request, fn ($x) => $x->get('/users/by/username/'.$username, [
            'user.fields' => 'id,name,username,description,created_at,verified,verified_type,protected,location,url,profile_image_url,public_metrics',
        ]));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'username' => $schema->string()->description('The X @username to look up (with or without the leading @).')->required(),
        ];
    }
}
