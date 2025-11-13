<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'price',
        'location',
        'duration',
        'image',
        'type',
        'max_group_size',
        'rating',
        'reviews_count',
        'itinerary',
        'highlights',
        'countries',
        'languages',
        'included',
        'excluded',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'max_group_size' => 'integer',
        'image' => 'array', // Laravel will automatically JSON encode/decode
        'itinerary' => 'array',
        'highlights' => 'array',
        'countries' => 'array',
        'languages' => 'array',
        'included' => 'array',
        'excluded' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the primary image (first image in the array)
     */
    public function getPrimaryImageAttribute(): ?string
    {
        if (!$this->image || !is_array($this->image)) {
            return null;
        }
        return $this->image[0] ?? null;
    }

    /**
     * Get all bookings for this tour
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get all backups related to this tour
     */
    public function backups()
    {
        return $this->hasMany(BookingBackup::class);
    }

    /**
     * Get all reviews for this tour
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get only approved reviews for this tour
     */
    public function approvedReviews()
    {
        return $this->reviews()->where('is_approved', true);
    }
}
