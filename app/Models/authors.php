<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class authors extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function books()
    {
        return $this->belongsToMany(books::class, 'author_book', 'author_id', 'book_id');
    }
}
