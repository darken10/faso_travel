<?php

namespace App\Models\Post;

use App\Models\User;
use App\Models\Post\Tag;
use App\Models\Post\Like;
use Illuminate\Support\Str;
use App\Models\Post\Comment;
use App\Models\Post\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'images_uri',
        'nb_views',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(callback: function (Post $post) {
            $post->user()->associate(Auth::user());
        });
    }

    /*
     * Pas de global scope de cloisonnement ici : un article est visible par
     * tous les voyageurs dans le fil d'actualité. L'isolation par compagnie
     * est appliquée explicitement côté panel (PostManager, PostForm), qui
     * restreint aux articles rédigés par les membres de la compagnie.
     *
     * Un scope conditionné à `request()->is('compagnie/post*')` existait ici ;
     * ce chemin n'existe plus depuis le passage aux sous-domaines, le scope ne
     * s'exécutait donc jamais et laissait croire à tort à un cloisonnement.
     */

    protected function casts(): array
    {
        return [
            'images_uri' => 'array',
        ];
    }




    /**********Les Relations*************** */

    function category():BelongsTo{
        return $this->belongsTo(Category::class);
    }

    /** Restreint aux articles rédigés par un membre d'une compagnie donnée. */
    public function scopeOfCompagnie(Builder $query, int $compagnieId): Builder
    {
        return $query->whereHas('user', fn (Builder $q) => $q->where('compagnie_id', $compagnieId));
    }

    function user():BelongsTo{
        return $this->belongsTo(User::class);
    }

    function tags():BelongsToMany{
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    function likes():HasMany{
        return $this->hasMany(Like::class);
    }

    /*************Les Autres Fonctions***************** */

    function count_likes():int{
        $nb = count($this->likes);
        return $nb;
    }

    function is_like():bool{
        return count($this->likes()->where('user_id',auth()->user()->id)->get())>0;
    }

    function getImageUrl():string
    {
        $path = is_array($this->images_uri) ? ($this->images_uri[0] ?? '') : ($this->images_uri ?? '');
        return url(Storage::url($path));
    }

    function getSummary():string{
        return Str::limit($this->content, 100);
    }
}
