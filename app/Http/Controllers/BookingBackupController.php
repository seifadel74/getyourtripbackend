<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingBackup;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingBackupController extends Controller
{
    /**
     * Create a backup of a booking
     */
    public function createBackup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($request->booking_id);
            $backup = $booking->createBackup($request->reason ?? 'manual');

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => $backup,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create backup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a backup of a tour
     */
    public function createTourBackup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,id',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tour = Tour::findOrFail($request->tour_id);
            
            $backup = BookingBackup::create([
                'tour_id' => $tour->id,
                'backup_type' => 'tour',
                'tour_data' => $tour->toArray(),
                'backup_reason' => $request->reason ?? 'manual',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tour backup created successfully',
                'data' => $backup,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tour backup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all backups
     */
    public function index(Request $request): JsonResponse
    {
        $query = BookingBackup::with(['booking', 'tour']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('backup_type', $request->type);
        }

        // Filter by booking_id
        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        // Filter by tour_id
        if ($request->has('tour_id')) {
            $query->where('tour_id', $request->tour_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $backups = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $backups,
        ]);
    }

    /**
     * Get a specific backup
     */
    public function show(int $id): JsonResponse
    {
        try {
            $backup = BookingBackup::with(['booking', 'tour'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $backup,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup not found',
            ], 404);
        }
    }

    /**
     * Restore a backup
     */
    public function restore(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $backup = BookingBackup::findOrFail($id);

            if ($backup->restored_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'This backup has already been restored',
                ], 400);
            }

            $restoredBooking = null;
            $restoredTour = null;

            // Restore booking if available
            if ($backup->booking_data) {
                $restoredBooking = $backup->restore();
            }

            // Restore tour if needed
            if ($backup->backup_type === 'tour' && $backup->tour_data && !$backup->tour_id) {
                $restoredTour = $backup->restoreTour();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Backup restored successfully',
                'data' => [
                    'backup' => $backup->fresh(),
                    'restored_booking' => $restoredBooking,
                    'restored_tour' => $restoredTour,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore backup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a backup
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $backup = BookingBackup::findOrFail($id);
            $backup->delete();

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-backup before update (can be called via middleware or model events)
     */
    public function autoBackupBeforeUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $booking = Booking::findOrFail($request->booking_id);
            $backup = $booking->createBackup('before_update');

            return response()->json([
                'success' => true,
                'message' => 'Auto-backup created before update',
                'data' => $backup,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create auto-backup',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
