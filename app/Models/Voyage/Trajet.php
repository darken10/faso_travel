<?php

namespace App\Models\Voyage;

use App\Models\User;
use App\Models\Ville\Ville;
use App\Models\Voyage\Voyage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trajet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'depart_id',
        'arriver_id',
        'distance',
        'temps',
        'etat',
    ];


    protected $with = [
        'depart',
        "arriver"

    ];


    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

    function depart():BelongsTo{
        return $this->belongsTo(Ville::class,'depart_id');
    }

    function arriver():BelongsTo{
        return $this->belongsTo(Ville::class,'arriver_id');
    }

    function voyages():HasMany{
        return $this->hasMany(Voyage::class);
    }
}
