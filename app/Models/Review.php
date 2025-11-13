<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'tour_id',
        'booking_id',
        'author',
        'email',
        'rating',
        'comment',
        'is_verified',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tour this review belongs to
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Get the booking this review is associated with
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope to get only approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to get reviews for a specific tour
     */
    public function scopeForTour($query, $tourId)
    {
        return $query->where('tour_id', $tourId);
    }
}
