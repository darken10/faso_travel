<?php

namespace App\Models\Ticket;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutrePersonne extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
      'first_name',
      'last_name',
      'name',
      'email',
      'sexe',
      'numero',
      'numero_identifiant',
      'lien_relation',
      'note',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(callback: function (AutrePersonne $autrePersonne) {
            $autrePersonne->name = Str::upper($autrePersonne->first_name) .' '. $autrePersonne->last_name;
            if (auth()->check()) {
                $autrePersonne->user_id = auth()->id();
            }
        });
    }

    function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    function tickets()
    {
        return $this->morphMany(Ticket::class,'autre_personne');
    }

}
