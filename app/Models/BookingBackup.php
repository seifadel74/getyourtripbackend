<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingBackup extends Model
{
    protected $fillable = [
        'booking_id',
        'tour_id',
        'backup_type',
        'tour_data',
        'booking_data',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
        'backup_reason',
        'restored_at',
    ];

    protected $casts = [
        'tour_data' => 'array',
        'booking_data' => 'array',
        'restored_at' => 'datetime',
    ];

    /**
     * Get the booking this backup belongs to
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the tour this backup belongs to
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Restore this backup to a new booking
     */
    public function restore(): Booking
    {
        if (!$this->booking_data) {
            throw new \Exception('No booking data available in backup');
        }

        $bookingData = $this->booking_data;
        
        // Remove fields that shouldn't be restored
        unset($bookingData['id'], $bookingData['booking_number'], $bookingData['created_at'], $bookingData['updated_at'], $bookingData['deleted_at']);

        // Create new booking from backup
        $booking = Booking::create($bookingData);

        // Mark this backup as restored
        $this->update(['restored_at' => now()]);

        return $booking;
    }

    /**
     * Restore tour data if available
     */
    public function restoreTour(): ?Tour
    {
        if (!$this->tour_data || $this->tour_id) {
            return Tour::find($this->tour_id);
        }

        $tourData = $this->tour_data;
        unset($tourData['id'], $tourData['created_at'], $tourData['updated_at'], $tourData['deleted_at']);

        return Tour::create($tourData);
    }
}
