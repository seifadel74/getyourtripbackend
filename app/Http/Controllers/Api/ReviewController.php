<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Get all approved reviews for a tour
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $perPage = $request->get('per_page', 10);
            $reviews = Review::forTour($request->tour_id)
                ->approved()
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reviews,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reviews',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new review
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'author' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $review = Review::create([
                'tour_id' => $request->tour_id,
                'booking_id' => $request->booking_id,
                'author' => $request->author,
                'email' => $request->email,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'is_verified' => $request->booking_id ? true : false,
                'is_approved' => true, // Auto-approve reviews for now
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully. It will be published after approval.',
                'data' => $review,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific review
     */
    public function show(int $id): JsonResponse
    {
        try {
            $review = Review::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found',
            ], 404);
        }
    }

    /**
     * Update a review (admin only)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_approved' => 'nullable|boolean',
            'comment' => 'nullable|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $review = Review::findOrFail($id);
            $review->update($request->only(['is_approved', 'comment']));

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a review
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $review = Review::findOrFail($id);
            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tour rating summary
     */
    public function getTourRatingSummary(int $tourId): JsonResponse
    {
        try {
            $tour = Tour::findOrFail($tourId);
            
            $reviews = Review::forTour($tourId)->approved()->get();
            
            if ($reviews->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tour_id' => $tourId,
                        'average_rating' => 0,
                        'total_reviews' => 0,
                        'rating_distribution' => [
                            '5' => 0,
                            '4' => 0,
                            '3' => 0,
                            '2' => 0,
                            '1' => 0,
                        ],
                    ],
                ]);
            }

            $averageRating = $reviews->avg('rating');
            $ratingDistribution = [
                '5' => $reviews->where('rating', 5)->count(),
                '4' => $reviews->where('rating', 4)->count(),
                '3' => $reviews->where('rating', 3)->count(),
                '2' => $reviews->where('rating', 2)->count(),
                '1' => $reviews->where('rating', 1)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'tour_id' => $tourId,
                    'average_rating' => round($averageRating, 1),
                    'total_reviews' => $reviews->count(),
                    'rating_distribution' => $ratingDistribution,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get rating summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
