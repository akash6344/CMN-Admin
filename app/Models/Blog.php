<?php

namespace App\Models;

use Database\Factories\BlogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'image_path', 'description', 'status'])]
class Blog extends Model
{
    /** @use HasFactory<BlogFactory> */
    use HasFactory;

    public function isActive(): bool
    {
        return strtolower(trim((string) $this->status)) === 'active';
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return '/storage/'.$this->image_path;
    }
}
