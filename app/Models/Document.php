<?php

namespace App\Models;

use App\Models\Compagnie\Compagnie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'compagnie_id',
        'user_id',
        'titre',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'has_expiration',
        'date_expiration',
    ];

    protected $casts = [
        'has_expiration'  => 'boolean',
        'date_expiration' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $doc) {
            $doc->user_id = Auth::id();
            $doc->compagnie_id ??= Auth::user()?->compagnie_id;
        });
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function rappels(): HasMany
    {
        return $this->hasMany(DocumentRappel::class);
    }

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Accesseurs ───────────────────────────────────────────────────────────

    public function getStatutAttribute(): string
    {
        if (!$this->has_expiration || !$this->date_expiration) {
            return 'valide';
        }
        $today = now()->startOfDay();
        if ($this->date_expiration->lt($today)) {
            return 'expire';
        }
        if ($this->date_expiration->lte((clone $today)->addDays(7))) {
            return 'expire_bientot';
        }
        return 'valide';
    }

    public function getJoursRestantsAttribute(): ?int
    {
        if (!$this->has_expiration || !$this->date_expiration) return null;
        return max(0, now()->startOfDay()->diffInDays($this->date_expiration, false));
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) return '';
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $size  = $this->file_size;
        $i     = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }

    public function getFileIconAttribute(): string
    {
        $mime = $this->mime_type ?? '';
        if (str_starts_with($mime, 'image/')) return 'image';
        if ($mime === 'application/pdf')       return 'pdf';
        if (str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') || str_contains($mime, 'csv')) return 'spreadsheet';
        if (str_contains($mime, 'word') || str_contains($mime, 'document')) return 'word';
        return 'file';
    }
}
