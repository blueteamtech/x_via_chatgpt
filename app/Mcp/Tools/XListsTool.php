<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Create, update, delete, or list X Lists, and add or remove members, as the connected user.')]
class XListsTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'action' => ['required', 'in:list,create,update,delete,add_member,remove_member'],
            'list_id' => ['required_if:action,update,delete,add_member,remove_member', 'nullable', 'string'],
            'name' => ['required_if:action,create', 'nullable', 'string', 'max:25'],
            'description' => ['nullable', 'string', 'max:100'],
            'private' => ['nullable', 'boolean'],
            'member_user_id' => ['required_if:action,add_member,remove_member', 'nullable', 'string'],
        ]);

        return $this->respond($request, function ($x) use ($request, $validated) {
            $userId = $this->currentUserId($request);

            return match ($validated['action']) {
                'list' => $x->get("/users/{$userId}/owned_lists", [
                    'list.fields' => 'created_at,description,member_count,private,follower_count',
                ]),
                'create' => $x->post('/lists', Arr::whereNotNull([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'private' => $validated['private'] ?? false,
                ])),
                'update' => $x->put('/lists/'.$validated['list_id'], Arr::whereNotNull([
                    'name' => $validated['name'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'private' => $validated['private'] ?? null,
                ])),
                'delete' => $x->delete('/lists/'.$validated['list_id']),
                'add_member' => $x->post('/lists/'.$validated['list_id'].'/members', [
                    'user_id' => $validated['member_user_id'],
                ]),
                'remove_member' => $x->delete('/lists/'.$validated['list_id'].'/members/'.$validated['member_user_id']),
            };
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['list', 'create', 'update', 'delete', 'add_member', 'remove_member'])->required(),
            'list_id' => $schema->string()->description('Required except for list and create.'),
            'name' => $schema->string()->description('List name for create/update.'),
            'description' => $schema->string(),
            'private' => $schema->boolean(),
            'member_user_id' => $schema->string()->description('Required for add_member and remove_member.'),
        ];
    }
}
