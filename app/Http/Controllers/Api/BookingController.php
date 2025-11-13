<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Notifications\BookingConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with('tour');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by tour
        if ($request->has('tour_id')) {
            $query->where('tour_id', $request->tour_id);
        }

        // Filter by email
        if ($request->has('email')) {
            $query->where('email', $request->email);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $bookings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'booking_date' => 'required|date|after_or_equal:today',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'special_requests' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get tour to calculate total amount
        $tour = Tour::findOrFail($request->tour_id);
        $adults = $request->adults;
        $children = $request->children ?? 0;
        
        // Calculate total (assuming children are 50% of adult price)
        $totalAmount = ($tour->price * $adults) + ($tour->price * 0.5 * $children);

        // Generate a unique booking number
        $bookingNumber = 'BK' . now()->format('Ymd') . strtoupper(Str::random(6));
        
        $booking = Booking::create([
            'tour_id' => $request->tour_id,
            'booking_number' => $bookingNumber,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'booking_date' => $request->booking_date,
            'adults' => $adults,
            'children' => $children,
            'special_requests' => $request->special_requests,
            'total_amount' => $totalAmount,
            'status' => 'confirmed',
        ]);

        // Create backup
        $booking->createBackup('new_booking');

        // Send confirmation email
        try {
            // Ensure we're using sync queue for immediate sending
            $originalQueue = config('queue.default');
            config(['queue.default' => 'sync']);
            
            // Log the email sending attempt
            \Log::info('Attempting to send booking confirmation email', [
                'booking_id' => $booking->id,
                'email' => $booking->email,
                'booking_number' => $booking->booking_number
            ]);
            
            // Send the notification immediately (synchronously)
            $notification = new BookingConfirmation($booking);
            \Notification::route('mail', $booking->email)
                ->notifyNow($notification);
                
            // Log success
            \Log::info('Booking confirmation email sent successfully', [
                'booking_id' => $booking->id,
                'email' => $booking->email
            ]);
                
            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully. A confirmation email has been sent to your email address.',
                'data' => $booking->load('tour'),
            ], 201);
        } catch (\Exception $e) {
            // Log the detailed error
            $errorMessage = 'Failed to send booking confirmation email: ' . $e->getMessage();
            \Log::error($errorMessage, [
                'booking_id' => $booking->id,
                'email' => $booking->email,
                'exception' => $e->getTraceAsString()
            ]);
            
            // Still return success for the booking, but indicate email issue
            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully, but we encountered an issue sending the confirmation email. Please contact support for assistance.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null,
                'data' => $booking->load('tour'),
            ], 201);
        } finally {
            // Restore original queue config
            config(['queue.default' => $originalQueue]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $booking = Booking::with('tour')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:255',
            'booking_date' => 'sometimes|date|after_or_equal:today',
            'adults' => 'sometimes|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'special_requests' => 'nullable|string',
            'status' => 'sometimes|in:pending,confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create backup before update
        $booking->createBackup('before_update');

        $booking->update($validator->validated());

        // Update total amount if adults or children changed
        if ($request->has('adults') || $request->has('children')) {
            $tour = $booking->tour;
            $adults = $booking->adults;
            $children = $booking->children ?? 0;
            $totalAmount = ($tour->price * $adults) + ($tour->price * 0.5 * $children);
            $booking->update(['total_amount' => $totalAmount]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully',
            'data' => $booking->load('tour'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        
        // Create backup before delete
        $booking->createBackup('before_delete');
        
        $booking->delete();

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully',
        ]);
    }
}
