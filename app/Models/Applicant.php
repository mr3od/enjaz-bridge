<?php

namespace App\Models;

use App\Enums\ApplicantStatus;
use App\Enums\EnjazStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Applicant extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use BelongsToTenant, HasFactory, HasUlids, InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'created_by',
        'passport_number',
        'country_code',
        'mrz_line_1',
        'mrz_line_2',
        'surname_ar',
        'given_names_ar',
        'surname_en',
        'given_names_en',
        'date_of_birth',
        'place_of_birth_ar',
        'place_of_birth_en',
        'sex',
        'date_of_issue',
        'date_of_expiry',
        'profession_ar',
        'profession_en',
        'issuing_authority_ar',
        'issuing_authority_en',
        'status',
        'enjaz_status',
        'extraction_requested_at',
        'extraction_started_at',
        'extraction_finished_at',
        'extraction_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicantStatus::class,
            'enjaz_status' => EnjazStatus::class,
            'date_of_birth' => 'date',
            'date_of_issue' => 'date',
            'date_of_expiry' => 'date',
            'extraction_requested_at' => 'datetime',
            'extraction_started_at' => 'datetime',
            'extraction_finished_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function extractions(): HasMany
    {
        return $this->hasMany(Extraction::class);
    }
}
