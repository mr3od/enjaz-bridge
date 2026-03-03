<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Extraction extends Model
{
    /** @use HasFactory<\Database\Factories\ExtractionFactory> */
    use BelongsToTenant, HasFactory, HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'applicant_id',
        'agency_id',
        'user_id',
        'model_used',
        'raw_response',
        'extracted_data',
        'corrections',
        'processing_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'extracted_data' => 'array',
            'corrections' => 'array',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
