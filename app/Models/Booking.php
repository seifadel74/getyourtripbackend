<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tour_id',
        'booking_number',
        'name',
        'email',
        'phone',
        'booking_date',
        'adults',
        'children',
        'special_requests',
        'total_amount',
        'status',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_amount' => 'decimal:2',
        'adults' => 'integer',
        'children' => 'integer',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = 'GYT-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the tour that this booking belongs to
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Get all backups for this booking
     */
    public function backups()
    {
        return $this->hasMany(BookingBackup::class);
    }

    /**
     * Create a backup of this booking
     */
    public function createBackup(string $reason = 'manual'): BookingBackup
    {
        return BookingBackup::create([
            'booking_id' => $this->id,
            'tour_id' => $this->tour_id,
            'backup_type' => 'booking',
            'tour_data' => $this->tour ? $this->tour->toArray() : null,
            'booking_data' => $this->toArray(),
            'customer_name' => $this->name,
            'customer_email' => $this->email,
            'customer_phone' => $this->phone,
            'backup_reason' => $reason,
        ]);
    }
}
