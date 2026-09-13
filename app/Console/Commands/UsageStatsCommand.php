<?php

namespace App\Console\Commands;

use App\Models\ToolInvocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UsageStatsCommand extends Command
{
    protected $signature = 'x:usage {--days=7 : How many days back to summarize}';

    protected $description = 'Show tool usage stats: top tools, failures, and per-user counts.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $since = now()->subDays($days);

        $total = ToolInvocation::where('created_at', '>=', $since)->count();

        if ($total === 0) {
            $this->info("No tool invocations in the last {$days} days.");

            return self::SUCCESS;
        }

        $this->info("Usage over the last {$days} days ({$total} total calls)");
        $this->newLine();

        $this->line('TOP TOOLS:');
        $topTools = ToolInvocation::where('created_at', '>=', $since)
            ->select('tool', DB::raw('count(*) as calls'), DB::raw('sum(case when success then 1 else 0 end) as ok'), DB::raw('round(avg(duration_ms)) as avg_ms'))
            ->groupBy('tool')
            ->orderByDesc('calls')
            ->get();
        $this->table(['tool', 'calls', 'succeeded', 'avg ms'], $topTools->map(fn ($r) => [$r->tool, $r->calls, $r->ok, $r->avg_ms])->all());

        $failures = ToolInvocation::where('created_at', '>=', $since)
            ->where('success', false)
            ->select('tool', 'error', DB::raw('count(*) as count'))
            ->groupBy('tool', 'error')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        if ($failures->isNotEmpty()) {
            $this->line('TOP FAILURES:');
            $this->table(['tool', 'error', 'count'], $failures->map(fn ($r) => [$r->tool, mb_substr($r->error ?? '', 0, 80), $r->count])->all());
        }

        $this->line('BUSIEST USERS:');
        $topUsers = ToolInvocation::where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('count(*) as calls'))
            ->groupBy('user_id')
            ->orderByDesc('calls')
            ->limit(10)
            ->get();
        $this->table(['user_id', 'calls'], $topUsers->map(fn ($r) => [$r->user_id, $r->calls])->all());

        return self::SUCCESS;
    }
}
