<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsDestructive]
#[IsOpenWorld]
#[Description('Permanently delete one of the connected user\'s posts by id.')]
class XDeletePostTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'post_id' => ['required', 'string'],
        ]);

        return $this->respond($request, fn ($x) => $x->delete('/tweets/'.$validated['post_id']));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->string()->description('The post id to delete.')->required(),
        ];
    }
}
