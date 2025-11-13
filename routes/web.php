<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Models\Booking;
use App\Models\Tour;
use App\Notifications\BookingConfirmation;

Route::get('/', function () {
    return view('welcome');
});

// Test email route - Only for development
Route::get('/test-email', function () {
    try {
        // Test sending a simple email
        Mail::raw('This is a test email from your application', function($message) {
            $message->to('seifadel034@gmail.com')
                   ->subject('Test Email from Laravel');
        });
        
        return 'Test email sent successfully. Check your email inbox.';
    } catch (\Exception $e) {
        return 'Error sending email: ' . $e->getMessage();
    }
});

// Test booking email route - Only for development
Route::get('/test-booking-email', function () {
    try {
        // Temporarily disable queue for testing
        config(['queue.default' => 'sync']);
        \Illuminate\Support\Facades\Log::info('Starting test booking email');

        // Create a test booking
        $tour = Tour::first();
        
        if (!$tour) {
            return 'No tours found in the database. Please create a tour first.';
        }

        $booking = new Booking([
            'tour_id' => $tour->id,
            'name' => 'Test User',
            'email' => 'seifadel034@gmail.com',
            'phone' => '1234567890',
            'booking_date' => now()->addDays(7),
            'adults' => 2,
            'children' => 1,
            'special_requests' => 'Test booking request',
            'total_amount' => 100.00,
            'status' => 'confirmed',
            'booking_number' => 'TEST-' . time(),
        ]);

        // Save the booking to get an ID
        $booking->save();
        \Illuminate\Support\Facades\Log::info('Created test booking', ['booking_id' => $booking->id]);

        // 1. First, test with a simple email
        try {
            \Illuminate\Support\Facades\Mail::raw('This is a simple test email', function($message) {
                $message->to('seifadel034@gmail.com')
                       ->subject('Simple Test Email');
            });
            \Illuminate\Support\Facades\Log::info('Simple test email sent');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send simple test email', ['error' => $e->getMessage()]);
            return 'Failed to send simple test email: ' . $e->getMessage();
        }

        // 2. Then test with the booking notification
        try {
            $notification = new BookingConfirmation($booking);
            \Illuminate\Support\Facades\Notification::route('mail', 'seifadel034@gmail.com')
                ->notifyNow($notification);
            
            \Illuminate\Support\Facades\Log::info('Booking notification sent successfully');
            return 'Test booking email sent successfully. Check your email inbox and server logs.';
        } catch (\Exception $e) {
            $error = 'Error sending booking email: ' . $e->getMessage() . 
                    '\n\nStack trace:\n' . $e->getTraceAsString();
            \Illuminate\Support\Facades\Log::error($error);
            return $error;
        }
    } catch (\Exception $e) {
        $error = 'Unexpected error: ' . $e->getMessage() . 
                '\n\nStack trace:\n' . $e->getTraceAsString();
        \Illuminate\Support\Facades\Log::error($error);
        return $error;
    }
});
