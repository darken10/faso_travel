<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRappel extends Model
{
    protected $fillable = [
        'document_id',
        'delai_valeur',
        'delai_unite',
        'canaux',
    ];

    protected $casts = [
        'canaux' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->delai_valeur . ' ' . $this->delai_unite . ' avant';
    }
}
