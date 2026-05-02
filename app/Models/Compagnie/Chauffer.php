<?php

namespace App\Models\Compagnie;

use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chauffer extends Model
{
    use HasUuids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'matricule',
        'telephone',
        'photo',
        'date_naissance',
        'genre',
        'compagnie_id',
        'statut',
    ];

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    function voyageInstances():HasMany
    {
        return $this->hasMany(VoyageInstance::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            "date_naissance" => "date",
        ];
    }

    function fullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
