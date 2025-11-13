<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // IMPORTANT: Change column type FIRST to avoid "data too long" errors
        // Then convert the data to JSON arrays
        
        $driver = DB::connection()->getDriverName();
        
        // Step 1: Change column type to support larger data
        if ($driver === 'mysql') {
            // For MySQL, change to TEXT (can store up to 65,535 characters)
            Schema::table('tours', function (Blueprint $table) {
                $table->text('image')->nullable()->change();
            });
        } elseif ($driver === 'pgsql') {
            // For PostgreSQL, use JSONB for better performance
            Schema::table('tours', function (Blueprint $table) {
                $table->jsonb('image')->nullable()->change();
            });
        } elseif ($driver === 'sqlite') {
            // For SQLite, TEXT is fine - no change needed
            // SQLite already uses TEXT for strings
        } else {
            // For other databases, try TEXT first
            try {
                Schema::table('tours', function (Blueprint $table) {
                    $table->text('image')->nullable()->change();
                });
            } catch (\Exception $e) {
                // If that fails, log and continue
                \Log::warning('Could not change image column type: ' . $e->getMessage());
            }
        }

        // Step 2: Now convert existing image data to JSON arrays
        // We do this AFTER changing the column type so we don't hit size limits
        $tours = DB::table('tours')->whereNotNull('image')->get();
        
        foreach ($tours as $tour) {
            if ($tour->image && !empty($tour->image)) {
                // Check if it's already a JSON array
                $decoded = json_decode($tour->image, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Already JSON array, ensure it's properly formatted
                    $images = json_encode(array_values(array_filter($decoded)));
                } else {
                    // Convert single image string to array
                    $images = json_encode([$tour->image]);
                }
                
                // Update the tour with JSON data
                DB::table('tours')
                    ->where('id', $tour->id)
                    ->update(['image' => $images]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON arrays back to single image strings
        $tours = DB::table('tours')->whereNotNull('image')->get();
        
        foreach ($tours as $tour) {
            if ($tour->image) {
                $images = json_decode($tour->image, true);
                if (is_array($images) && count($images) > 0) {
                    // Take the first image
                    DB::table('tours')
                        ->where('id', $tour->id)
                        ->update(['image' => $images[0]]);
                }
            }
        }

        // Change column type back to string
        Schema::table('tours', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
        });
    }
};
