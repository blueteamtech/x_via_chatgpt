<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get the connected X account: id, name, username, and granted OAuth scopes.')]
class XMeTool extends XTool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        return $this->respond($request, function ($x) use ($user) {
            $me = $x->get('/users/me', [
                'user.fields' => 'id,name,username,description,profile_image_url,public_metrics,verified,created_at',
            ]);

            $me['connected_scopes'] = $user->x_token_scopes ?? [];

            return $me;
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
