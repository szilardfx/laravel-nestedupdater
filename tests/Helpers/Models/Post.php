<?php
namespace Czim\NestedModelUpdater\Test\Helpers\Models;

use Czim\NestedModelUpdater\Traits\NestedUpdatable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use NestedUpdatable;

    protected $fillable = [ 'title', 'body' ];

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function genre()
    {
        return $this->belongsTo(Genre::class);
    }

    public function tags()
    {
        return $this->morphMany(Tag::class, 'taggable');
    }

    
    public function someOtherRelationMethod()
    {
        return $this->belongsTo(Genre::class);
    }

    public function commentHasOne()
    {
        return $this->hasOne(Comment::class);
    }

    public function specials()
    {
        return $this->hasMany(Special::class);
    }

    // for testing per-model rules class/method validation configuration
    public function customRulesMethod()
    {
        return [
            'title' => 'in:custom,post,rules',
        ];
    }

}
