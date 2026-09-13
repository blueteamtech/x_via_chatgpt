<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\XArticlesTool;
use App\Mcp\Tools\XCreatePostTool;
use App\Mcp\Tools\XCreateThreadTool;
use App\Mcp\Tools\XDeletePostTool;
use App\Mcp\Tools\XDirectMessagesTool;
use App\Mcp\Tools\XEngageTool;
use App\Mcp\Tools\XGetTopPostsTool;
use App\Mcp\Tools\XListsTool;
use App\Mcp\Tools\XMeTool;
use App\Mcp\Tools\XReadFeedTool;
use App\Mcp\Tools\XSchedulePostTool;
use App\Mcp\Tools\XSocialTool;
use App\Mcp\Tools\XUploadMediaTool;
use App\Mcp\Tools\XUserLookupTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('X via ChatGPT')]
#[Version('0.1.0')]
#[Instructions('Manage the signed-in X account: read the timeline, post and delete, like and repost, follow and block, lists, DMs, and media. Confirm destructive actions. Use X user ids, not @handles, for social and DM tools unless a lookup is available.')]
class XConnectorServer extends Server
{
    protected array $tools = [
        XMeTool::class,
        XUserLookupTool::class,
        XReadFeedTool::class,
        XGetTopPostsTool::class,
        XCreatePostTool::class,
        XCreateThreadTool::class,
        XDeletePostTool::class,
        XEngageTool::class,
        XSocialTool::class,
        XListsTool::class,
        XDirectMessagesTool::class,
        XUploadMediaTool::class,
        XSchedulePostTool::class,
        XArticlesTool::class,
    ];
}
