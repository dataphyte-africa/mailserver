<?php

namespace App\Console\Commands\Newsletter;

use App\Services\Newsletter\HistoricalOwnershipAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AuditHistoricalOwnership extends Command
{
    protected $signature = 'newsletter:audit-historical-ownership
        {--json : Emit the full report as JSON}
        {--output= : Optional file path to write the JSON report to}';

    protected $description = 'Read-only audit of historical newsletter ownership, audience integrity, and dry-run cleanup blockers.';

    public function handle(HistoricalOwnershipAuditService $audit): int
    {
        $report = $audit->generate();
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to encode the historical ownership audit report.');

            return self::FAILURE;
        }

        if (is_string($this->option('output')) && $this->option('output') !== '') {
            $path = $this->normalizeOutputPath($this->option('output'));

            File::ensureDirectoryExists(dirname($path));
            File::put($path, $json.PHP_EOL);

            $this->line("Wrote report to {$path}");
        }

        if ($this->option('json')) {
            $this->line($json);

            return self::SUCCESS;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Organisations', (string) $report['summary']['organisation_count']],
                ['Products', (string) $report['summary']['product_count']],
                ['Subscriber groups', (string) $report['summary']['subscriber_group_count']],
                ['Subscriber sub-groups', (string) $report['summary']['subscriber_sub_group_count']],
                ['Subscribers', (string) $report['summary']['subscriber_count']],
                ['Campaigns', (string) $report['summary']['campaign_count']],
                ['Campaign audiences', (string) $report['summary']['campaign_audience_count']],
                ['Email templates', (string) $report['summary']['email_template_count']],
            ]
        );

        $this->table(
            ['Finding', 'Value'],
            [
                ['Affected subscriber groups', (string) $report['records']['subscriber_groups']['affected_count']],
                ['Affected subscriber sub-groups', (string) $report['records']['subscriber_sub_groups']['affected_count']],
                ['Affected campaigns', (string) $report['records']['campaigns']['affected_count']],
                ['Missing campaign audience targets', (string) $report['records']['campaign_audiences']['missing_targets_count']],
                ['Ownership-blocked campaign audiences', (string) $report['records']['campaign_audiences']['ownership_blocked_count']],
                ['Subscribers without any membership', (string) $report['records']['subscribers']['without_any_membership_count']],
                ['Subscribers without active membership', (string) $report['records']['subscribers']['without_active_membership_count']],
                ['Database unchanged during dry-run', $report['database']['unchanged'] ? 'yes' : 'no'],
            ]
        );

        if (! empty($report['blockers'])) {
            $this->warn('Blockers:');

            foreach ($report['blockers'] as $blocker) {
                $this->line("- {$blocker}");
            }
        } else {
            $this->info('No blockers detected.');
        }

        return self::SUCCESS;
    }

    private function normalizeOutputPath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}
