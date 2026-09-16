<?php

namespace App\Mcp\Tools;

use App\Services\Credits\CreditManager;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get the connected X account: id, name, username, granted OAuth scopes, current subscription tier, and monthly credit usage.')]
class XMeTool extends XTool
{
    protected function skipSubscriptionCheck(): bool
    {
        // Always available so users can check their subscription state
        // even after their access is revoked.
        return true;
    }

    public function handle(Request $request): Response
    {
        $user = $request->user();

        return $this->respond($request, function ($x) use ($user) {
            $me = $x->get('/users/me', [
                'user.fields' => 'id,name,username,description,profile_image_url,public_metrics,verified,created_at',
            ]);

            $me['connected_scopes'] = $user->x_token_scopes ?? [];
            $me['subscription'] = app(CreditManager::class)->status($user);

            return $me;
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
