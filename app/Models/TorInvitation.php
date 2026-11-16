<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorInvitation extends Model
{
    protected $fillable = ['tor_flow_id', 'email', 'expected_name', 'state', 'position', 'phone_number', 'token', 'status', 'elastic_email_transaction_id', 'sent_at', 'delivered_at', 'clicked_at', 'signed_at', 'signer_name', 'signer_field_values', 'signature_method', 'signature_data', 'verification_code', 'document_hash', 'signed_pdf_path', 'ip_address', 'revoked_reason'];

    protected $hidden = ['token', 'signature_data'];

    protected $casts = ['signer_field_values' => 'array', 'sent_at' => 'datetime', 'delivered_at' => 'datetime', 'clicked_at' => 'datetime', 'signed_at' => 'datetime'];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(TorFlow::class, 'tor_flow_id');
    }

    public function isComplete(): bool
    {
        return $this->status === 'signed';
    }
}
