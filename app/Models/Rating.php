<?php

namespace App\Models;

use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'user_id',
        'compagnie_id',
        'ticket_id',
        'stars',
        'comment',
    ];

    protected $casts = [
        'stars' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function compagnie(): BelongsTo
    {
        return $this->belongsTo(Compagnie::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
