<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // If authenticated (admin), show all tours; otherwise only active tours
        $query = $request->user() ? Tour::query() : Tour::where('is_active', true);

        // Filter by featured
        if ($request->has('featured') && $request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $tours = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tours->items(),
            'pagination' => [
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
                'per_page' => $tours->perPage(),
                'total' => $tours->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'location' => 'required|string|max:255',
            'duration' => 'required|string|max:255',
            'image' => 'nullable|array',
            'image.*' => 'nullable|url|max:500',
            'type' => 'nullable|string|max:255',
            'max_group_size' => 'nullable|integer|min:1',
            'rating' => 'nullable|numeric|min:0|max:5',
            'reviews_count' => 'nullable|integer|min:0',
            'itinerary' => 'nullable|array',
            'highlights' => 'nullable|array',
            'highlights.*' => 'nullable|string|max:500',
            'included' => 'nullable|array',
            'excluded' => 'nullable|array',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'countries' => 'nullable|array',
            'countries.*' => 'string|min:2|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|min:2|max:100',
        ]);

        // Filter out empty image URLs and handle empty arrays
        if (isset($validated['image'])) {
            if (is_array($validated['image'])) {
                $validated['image'] = array_filter($validated['image'], function($url) {
                    return !empty($url) && is_string($url);
                });
                $validated['image'] = array_values($validated['image']); // Re-index array
                
                // If array is empty after filtering, set to null
                if (empty($validated['image'])) {
                    $validated['image'] = null;
                }
            } else {
                // If it's a string, convert to array for consistency
                $validated['image'] = !empty($validated['image']) ? [$validated['image']] : null;
            }
        }

        // Filter out empty highlights
        if (isset($validated['highlights']) && is_array($validated['highlights'])) {
            $validated['highlights'] = array_filter($validated['highlights'], function($highlight) {
                return !empty($highlight) && is_string($highlight);
            });
            $validated['highlights'] = array_values($validated['highlights']); // Re-index array
            
            // If array is empty after filtering, set to null
            if (empty($validated['highlights'])) {
                $validated['highlights'] = null;
            }
        }

        // Filter out empty included/excluded items
        if (isset($validated['included']) && is_array($validated['included'])) {
            $validated['included'] = array_filter($validated['included'], function($item) {
                return !empty($item) && is_string($item);
            });
            $validated['included'] = array_values($validated['included']);
            if (empty($validated['included'])) {
                $validated['included'] = null;
            }
        }

        if (isset($validated['excluded']) && is_array($validated['excluded'])) {
            $validated['excluded'] = array_filter($validated['excluded'], function($item) {
                return !empty($item) && is_string($item);
            });
            $validated['excluded'] = array_values($validated['excluded']);
            if (empty($validated['excluded'])) {
                $validated['excluded'] = null;
            }
        }

        // Filter out empty countries
        if (isset($validated['countries']) && is_array($validated['countries'])) {
            $validated['countries'] = array_filter($validated['countries'], function($item) {
                return !empty($item) && is_string($item);
            });
            $validated['countries'] = array_values($validated['countries']);
            if (empty($validated['countries'])) {
                $validated['countries'] = null;
            }
        }

        // Filter out empty languages
        if (isset($validated['languages']) && is_array($validated['languages'])) {
            $validated['languages'] = array_filter($validated['languages'], function($item) {
                return !empty($item) && is_string($item);
            });
            $validated['languages'] = array_values($validated['languages']);
            if (empty($validated['languages'])) {
                $validated['languages'] = null;
            }
        }

        try {
            $tour = Tour::create($validated);
        } catch (\Exception $e) {
            Log::error('Tour create error: ' . $e->getMessage());
            Log::error('Tour create data: ' . json_encode($validated));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tour: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tour created successfully',
            'data' => $tour,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        // If authenticated, show all tours (including inactive), otherwise only active
        $query = $request->user() ? Tour::query() : Tour::where('is_active', true);
        $tour = $query->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tour,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tour = Tour::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'location' => 'sometimes|string|max:255',
            'duration' => 'sometimes|string|max:255',
            'image' => 'nullable|array',
            'image.*' => 'nullable|url|max:500',
            'type' => 'nullable|string|max:255',
            'max_group_size' => 'nullable|integer|min:1',
            'rating' => 'nullable|numeric|min:0|max:5',
            'reviews_count' => 'nullable|integer|min:0',
            'itinerary' => 'nullable|array',
            'highlights' => 'nullable|array',
            'highlights.*' => 'nullable|string|max:500',
            'included' => 'nullable|array',
            'excluded' => 'nullable|array',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'countries' => 'nullable|array',
            'countries.*' => 'string|min:2|max:100',
            'languages' => 'nullable|array',
            'languages.*' => 'string|min:2|max:100',
        ]);

        // Filter out empty image URLs and handle empty arrays
        if (isset($validated['image'])) {
            if (is_array($validated['image'])) {
                $validated['image'] = array_filter($validated['image'], function($url) {
                    return !empty($url) && is_string($url);
                });
                $validated['image'] = array_values($validated['image']); // Re-index array
                
                // If array is empty after filtering, set to null
                if (empty($validated['image'])) {
                    $validated['image'] = null;
                }
            } else {
                // If it's a string, convert to array for consistency
                $validated['image'] = !empty($validated['image']) ? [$validated['image']] : null;
            }
        }

        // Filter out empty highlights
        if (isset($validated['highlights']) && is_array($validated['highlights'])) {
            $validated['highlights'] = array_filter($validated['highlights'], function($highlight) {
                return !empty($highlight) && is_string($highlight);
            });
            $validated['highlights'] = array_values($validated['highlights']); // Re-index array
            
            // If array is empty after filtering, set to null
            if (empty($validated['highlights'])) {
                $validated['highlights'] = null;
            }
        }

        // Filter out empty included/excluded items
        if (isset($validated['included']) && is_array($validated['included'])) {
            $validated['included'] = array_filter($validated['included'], function($item) {
                return !empty($item) && is_string($item);
            });
            $validated['included'] = array_values($validated['included']);
            if (empty($validated['included'])) {
                $validated['included'] = null;
            }
        }

        if (isset($validated['excluded']) && is_array($validated['excluded'])) {
            $validated['excluded'] = array_filter($validated['excluded'], function($item) {
                return !empty($item) && is_string($item);
            });
            $validated['excluded'] = array_values($validated['excluded']);
            if (empty($validated['excluded'])) {
                $validated['excluded'] = null;
            }
        }

        // Filter out empty countries
        if (isset($validated['countries']) && is_array($validated['countries'])) {
            $validated['countries'] = array_filter($validated['countries'], function($item) {
                return !empty($item) && is_string($item);
            });
            $validated['countries'] = array_values($validated['countries']);
            if (empty($validated['countries'])) {
                $validated['countries'] = null;
            }
        }

        // Filter out empty languages
        if (isset($validated['languages']) && is_array($validated['languages'])) {
            $validated['languages'] = array_filter($validated['languages'], function($item) {
                return !empty($item) && is_string($item);
            });
            $validated['languages'] = array_values($validated['languages']);
            if (empty($validated['languages'])) {
                $validated['languages'] = null;
            }
        }

        try {
            $tour->update($validated);
        } catch (\Exception $e) {
            Log::error('Tour update error: ' . $e->getMessage());
            Log::error('Tour update data: ' . json_encode($validated));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tour: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tour updated successfully',
            'data' => $tour,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $tour = Tour::findOrFail($id);
        $tour->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tour deleted successfully',
        ]);
    }
}
