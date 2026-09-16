<?php

namespace App\Mcp\Tools;

use App\Services\X\DmGuard;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('List recent Direct Messages or send a DM to an X user as the connected account. Daily cap of 20 sends per account (X anti-spam). Identical DMs to multiple recipients are blocked to avoid platform bans.')]
class XDirectMessagesTool extends XTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'action' => ['required', 'in:list,send'],
            'participant_id' => ['required_if:action,send', 'nullable', 'string'],
            'text' => ['required_if:action,send', 'nullable', 'string', 'max:10000'],
            'max_results' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->respond($request, function ($x) use ($request, $validated) {
            if ($validated['action'] === 'list') {
                return $x->get('/dm_events', [
                    'max_results' => $validated['max_results'] ?? 20,
                    'dm_event.fields' => 'id,text,created_at,sender_id,dm_conversation_id',
                    'event_types' => 'MessageCreate',
                ]);
            }

            $user = $request->user();
            $guard = app(DmGuard::class);

            $guard->ensureCanSend($user, $validated['participant_id'], $validated['text']);

            $result = $x->post(
                '/dm_conversations/with/'.$validated['participant_id'].'/messages',
                ['text' => $validated['text']],
            );

            $guard->record($user, $validated['participant_id'], $validated['text']);

            return $result;
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->enum(['list', 'send'])->required(),
            'participant_id' => $schema->string()->description('X user id to DM. Required when action is send.'),
            'text' => $schema->string()->description('Message text. Required when action is send.'),
            'max_results' => $schema->integer()->description('How many events to return when listing.'),
        ];
    }
}
