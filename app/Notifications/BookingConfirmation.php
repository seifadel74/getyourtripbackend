<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tour = $this->booking->tour;
        
        return (new MailMessage)
            ->subject('✅ Booking Confirmed: ' . $tour->title . ' - ' . $this->booking->booking_number)
            ->greeting('Hello ' . $this->booking->name . '!')
            ->line('Thank you for booking with us! We\'re excited to have you join our ' . $tour->title . ' tour.')
            ->line('')
            ->line('## 📅 Booking Details')
            ->line('')
            ->line('**Tour:** ' . $tour->title)
            ->line('**Booking Reference:** ' . $this->booking->booking_number)
            ->line('**Date:** ' . $this->booking->booking_date->format('F j, Y'))
            ->line('**Number of Adults:** ' . $this->booking->adults)
            ->line('**Number of Children:** ' . ($this->booking->children ?? '0'))
            
            ->when(!empty($this->booking->special_requests), function ($message) {
                return $message
                    ->line('')
                    ->line('## 💬 Special Requests')
                    ->line($this->booking->special_requests);
            })
            ->line('**Date:** ' . $this->booking->booking_date->format('l, F j, Y'))
            ->line('**Time:** ' . $this->booking->booking_date->format('g:i A'))
            ->line('**Number of Guests:** ' . ($this->booking->adults + $this->booking->children) . 
                   ' (' . $this->booking->adults . ' Adults, ' . $this->booking->children . ' Children)')
            ->line('**Total Amount:** $' . number_format($this->booking->total_amount, 2))
            
            ->line('')
            ->line('## 📍 Meeting Point')
            ->line('Please arrive 15 minutes before the scheduled time at: ' . ($tour->meeting_point ?? 'The main entrance'))
            
            ->line('')
            ->line('## ❓ Need Help?')
            ->line('If you have any questions or need to make changes to your booking, please contact our support team at ' . 
                  config('mail.from.address') . ' or call us at ' . (config('app.phone') ?? 'our support number') . '.')
            
            ->line('')
            ->line('We look forward to seeing you soon!')
            ->salutation('Best regards,The ' . config('app.name') . ' Team')
            
            // Add a custom header for better email client compatibility
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-Mailer', 'PHP/' . phpversion());
            });
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'Your booking has been confirmed.',
        ];
    }
}
