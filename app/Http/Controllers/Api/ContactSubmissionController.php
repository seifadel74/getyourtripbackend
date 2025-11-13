<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactSubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ContactSubmission::query()
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('mobile_number', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->latest();

        if ($request->sort_by) {
            $sortOrder = $request->sort_order === 'desc' ? 'desc' : 'asc';
            $query->orderBy($request->sort_by, $sortOrder);
        }

        $perPage = $request->per_page ?? 15;
        $submissions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $submissions->items(),
            'pagination' => [
                'total' => $submissions->total(),
                'per_page' => $submissions->perPage(),
                'current_page' => $submissions->currentPage(),
                'last_page' => $submissions->lastPage(),
                'from' => $submissions->firstItem(),
                'to' => $submissions->lastItem(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $submission = ContactSubmission::create($request->only([
            'name', 'email', 'mobile_number', 'message'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your message. We will get back to you soon!',
            'data' => $submission,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ContactSubmission $contactSubmission)
    {
        return response()->json([
            'success' => true,
            'data' => $contactSubmission,
        ]);
    }

    /**
     * Mark a submission as read.
     */
    public function markAsRead(ContactSubmission $contactSubmission)
    {
        $contactSubmission->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Submission marked as read',
            'data' => $contactSubmission->fresh(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Not implemented - we don't allow updating submissions
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Not implemented - we don't allow deleting submissions
    }
}
