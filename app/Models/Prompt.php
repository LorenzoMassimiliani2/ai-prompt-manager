<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Prompt extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'slug', 'visibility', 'content', 'meta'];
    protected $casts = ['meta' => 'array'];

    // relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    public function favoriters()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }

    // scopes
    public function scopePublic($q)
    {
        return $q->where('visibility', 'public');
    }
    public function scopeSearch($q, $term)
    {
        if (!$term)
            return $q;
        return $q->where(function ($s) use ($term) {
            $s->where('title', 'like', "%$term%")->orWhere('content', 'like', "%$term%");
        });
    }

    public function scopeWithAnyTags($q, array $tagIds)
    {
        if (empty($tagIds))
            return $q;
        return $q->whereHas('tags', fn($t) => $t->whereIn('tags.id', $tagIds));
    }

    public function scopeVisibleTo($q, User $user)
    {
        if (!$user) {
            // ospiti: solo public
            return $q->where('visibility', 'public');
        }

        // autenticato: public, unlisted, oppure propri
        return $q->where(function ($qq) use ($user) {
            $qq->whereIn('visibility', ['public','unlisted'])
            ->orWhere('user_id', $user->id);
        });

        // Se in futuro hai condivisioni, aggiungi qui:
        // ->orWhereExists(function($s) use ($user) {
        //     $s->selectRaw(1)->from('prompt_shares')
        //       ->whereColumn('prompt_shares.prompt_id', 'prompts.id')
        //       ->where('prompt_shares.user_id', $user->id);
        // })
    }

    protected static function booted()
    {
        static::creating(function ($prompt) {
            $prompt->slug = $prompt->slug ?: Str::slug(Str::limit($prompt->title, 80)) . '-' . Str::random(6);
        });
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (!$user)
            return false;
        return $this->favoriters()->where('user_id', $user->id)->exists();
    }
}
