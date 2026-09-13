<?php

namespace App\Mcp\Tools;

use App\Exceptions\XApiException;
use App\Models\Article;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsOpenWorld]
#[Description('Manage X long-form Articles (requires X Premium). Actions: draft (save + create X draft), list (your drafts and published articles), publish (publish a saved draft), delete (remove one you haven\'t published yet), update (edit a draft that isn\'t published).')]
class XArticlesTool extends XTool
{
    public function handle(Request $request): Response
    {
        $action = $request->validate(['action' => ['required', 'string', 'in:draft,list,publish,delete,update']])['action'];

        return match ($action) {
            'draft' => $this->draft($request),
            'list' => $this->list($request),
            'publish' => $this->publish($request),
            'delete' => $this->delete($request),
            'update' => $this->update($request),
        };
    }

    private function draft(Request $request): Response
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:25000'],
        ]);

        $user = $request->user();

        return $this->respond($request, function ($x) use ($user, $validated) {
            $article = Article::create([
                'user_id' => $user->getKey(),
                'title' => $validated['title'],
                'body' => $validated['body'],
            ]);

            $response = $x->post('/articles/draft', [
                'title' => $validated['title'],
                'content_state' => $this->toContentState($validated['body']),
            ]);

            $xArticleId = $response['data']['id'] ?? null;

            if ($xArticleId === null) {
                $article->update(['failed_at' => now(), 'error' => 'X did not return an article id.']);

                throw new XApiException('X accepted the draft but returned no id.');
            }

            $article->update(['x_article_id' => $xArticleId]);

            return [
                'article_id' => $article->id,
                'x_article_id' => $xArticleId,
                'title' => $article->title,
                'status' => 'draft',
            ];
        });
    }

    private function list(Request $request): Response
    {
        $user = $request->user();

        return $this->respond($request, function () use ($user) {
            $articles = Article::where('user_id', $user->getKey())
                ->orderByDesc('id')
                ->limit(50)
                ->get(['id', 'title', 'body', 'x_article_id', 'published_at', 'failed_at', 'error', 'created_at']);

            return [
                'articles' => $articles->map(fn (Article $a) => [
                    'article_id' => $a->id,
                    'title' => $a->title,
                    'body_preview' => mb_substr($a->body, 0, 200),
                    'status' => $this->status($a),
                    'x_article_id' => $a->x_article_id,
                    'published_at' => $a->published_at?->toIso8601String(),
                    'failed_at' => $a->failed_at?->toIso8601String(),
                    'error' => $a->error,
                    'created_at' => $a->created_at->toIso8601String(),
                ]),
            ];
        });
    }

    private function publish(Request $request): Response
    {
        $validated = $request->validate([
            'article_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $article = $this->findOwnedArticle($user, $validated['article_id']);

        if ($article === null) {
            return Response::error('Article not found.');
        }

        if (! $article->isDraft()) {
            return Response::error('Article was already published.');
        }

        if ($article->x_article_id === null) {
            return Response::error('Article has no X draft id — recreate the draft.');
        }

        return $this->respond($request, function ($x) use ($article) {
            $x->post("/articles/{$article->x_article_id}/publish");
            $article->update(['published_at' => now(), 'failed_at' => null, 'error' => null]);

            return [
                'article_id' => $article->id,
                'x_article_id' => $article->x_article_id,
                'title' => $article->title,
                'status' => 'published',
            ];
        });
    }

    private function delete(Request $request): Response
    {
        $validated = $request->validate([
            'article_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $article = $this->findOwnedArticle($user, $validated['article_id']);

        if ($article === null) {
            return Response::error('Article not found.');
        }

        if (! $article->isDraft()) {
            return Response::error('Cannot delete a published article — X does not expose a delete endpoint for articles.');
        }

        $article->delete();

        return Response::json(['deleted' => true, 'article_id' => $validated['article_id']]);
    }

    private function update(Request $request): Response
    {
        $validated = $request->validate([
            'article_id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'string', 'max:25000'],
        ]);

        if (empty($validated['title']) && empty($validated['body'])) {
            return Response::error('Pass at least one of title or body.');
        }

        $user = $request->user();
        $article = $this->findOwnedArticle($user, $validated['article_id']);

        if ($article === null) {
            return Response::error('Article not found.');
        }

        if (! $article->isDraft()) {
            return Response::error('Cannot edit a published article.');
        }

        // X has no update endpoint, so re-create the draft on their side and swap the id.
        $title = $validated['title'] ?? $article->title;
        $body = $validated['body'] ?? $article->body;

        return $this->respond($request, function ($x) use ($article, $title, $body) {
            $response = $x->post('/articles/draft', [
                'title' => $title,
                'content_state' => $this->toContentState($body),
            ]);

            $newXId = $response['data']['id'] ?? null;

            if ($newXId === null) {
                throw new XApiException('X accepted the updated draft but returned no id.');
            }

            $article->update([
                'title' => $title,
                'body' => $body,
                'x_article_id' => $newXId,
            ]);

            return [
                'article_id' => $article->id,
                'x_article_id' => $newXId,
                'title' => $article->title,
                'status' => 'draft',
                'note' => 'X does not support in-place edits, so a new draft was created and the old draft id was replaced.',
            ];
        });
    }

    /**
     * Convert plain/markdown-ish body into the minimal DraftJS structure X wants.
     * Paragraph breaks (blank lines) become separate blocks. Rich formatting like
     * headings, lists, and links would need entity mapping we do not implement yet.
     *
     * @return array{blocks: array<int, array<string, string>>, entities: array<int, mixed>}
     */
    private function toContentState(string $body): array
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($body)) ?: [$body];

        $blocks = array_values(array_map(
            fn (string $paragraph): array => ['text' => trim($paragraph), 'type' => 'unstyled'],
            array_filter($paragraphs, fn (string $p): bool => trim($p) !== ''),
        ));

        return ['blocks' => $blocks, 'entities' => []];
    }

    private function findOwnedArticle($user, int $articleId): ?Article
    {
        return Article::where('user_id', $user->getKey())->find($articleId);
    }

    private function status(Article $article): string
    {
        return match (true) {
            $article->published_at !== null => 'published',
            $article->failed_at !== null => 'failed',
            default => 'draft',
        };
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->description('draft | list | publish | delete | update')->required(),
            'title' => $schema->string()->description('Article title (draft or update).'),
            'body' => $schema->string()->description('Article body, paragraphs separated by blank lines (draft or update).'),
            'article_id' => $schema->integer()->description('Article id from list/draft (publish, delete, update).'),
        ];
    }
}
