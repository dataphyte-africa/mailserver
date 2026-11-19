<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TorFlow extends Model
{
    protected $fillable = ['organisation_id', 'title', 'content_html', 'content_bard', 'internal_recipients', 'email_copy', 'signer_fields', 'status', 'activated_at'];

    protected $casts = ['content_bard' => 'array', 'internal_recipients' => 'array', 'email_copy' => 'array', 'signer_fields' => 'array', 'activated_at' => 'datetime'];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TorInvitation::class);
    }

    /** @return array<int, array{type: string, heading: string, instructions: string, fields: array<int, array<string, mixed>>}> */
    public function signerSections(): array
    {
        $schema = $this->signer_fields ?? [];

        // Keep early draft flows usable while their flat schema is phased out.
        if (collect($schema)->contains(fn ($item) => ($item['type'] ?? null) === 'section')) {
            return $schema;
        }

        return $schema === [] ? [] : [[
            'type' => 'section',
            'heading' => 'Additional information',
            'instructions' => '',
            'fields' => $schema,
        ]];
    }

    /** @return array<int, array<string, mixed>> */
    public function signerInputFields(): array
    {
        return collect($this->signerSections())->flatMap(fn (array $section) => $section['fields'] ?? [])->values()->all();
    }
}
