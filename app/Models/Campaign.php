<?php

namespace App\Models;

use App\Services\Newsletter\CollectionRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;
    protected $fillable = [
        'entry_id', 'collection', 'name', 'subject',
        'from_name', 'from_email', 'reply_to',
        'status', 'scheduled_at', 'sent_at',
        'last_stats_sync_requested_at', 'last_stats_sync_completed_at',
        'last_stats_sync_status', 'last_stats_sync_total',
        'last_stats_sync_processed', 'last_stats_sync_error',
        'total_recipients', 'created_by',
    ];

    protected $casts = [
        'scheduled_at'                  => 'datetime',
        'sent_at'                       => 'datetime',
        'last_stats_sync_requested_at'  => 'datetime',
        'last_stats_sync_completed_at'  => 'datetime',
    ];

    public function audiences(): HasMany
    {
        return $this->hasMany(CampaignAudience::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Resolve the from address and name for this campaign
     * based on its collection, falling back to config defaults.
     */
    public function sender(): array
    {
        $collection = $this->collection ?? 'fallback';

        $sender = config(
            "newsletter.collections.{$collection}",
            config('newsletter.fallback')
        );

        // Per-campaign overrides stored on the model take precedence
        return [
            'from_email' => $this->from_email ?: $sender['from_email'],
            'from_name'  => $this->from_name  ?: $sender['from_name'],
            'reply_to'   => $this->reply_to   ?: $sender['reply_to'],
        ];
    }

    public function collectionLabel(): string
    {
        return app(CollectionRegistry::class)->label($this->collection);
    }

    public function collectionShortLabel(): string
    {
        return app(CollectionRegistry::class)->shortLabel($this->collection);
    }

    public function stats(): array
    {
        return $this->sends()
            ->selectRaw('
                COUNT(*) as total_recipients,
                SUM(status IN ("sent","delivered","opened","clicked")) as total_sent,
                SUM(status = "queued") as total_queued,
                SUM(status IN ("delivered","opened","clicked")) as total_delivered,
                SUM(status IN ("failed","bounced","complained")) as total_failed,
                SUM(opened_at IS NOT NULL) as total_opened,
                SUM(status IN ("delivered","opened","clicked") AND opened_at IS NULL) as total_unread
            ')
            ->first()
            ->toArray();
    }

    public function statsSyncProgress(): int
    {
        $total = (int) ($this->last_stats_sync_total ?? 0);
        $processed = (int) ($this->last_stats_sync_processed ?? 0);

        if ($total <= 0) {
            return 0;
        }

        return (int) min(100, round(($processed / $total) * 100));
    }
}
