<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ray;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RayController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'temperature' => 'nullable|numeric',
            'systolic_bp' => 'nullable|integer',
            'heart_rate' => 'nullable|integer',
            'has_cough' => 'boolean',
            'has_headaches' => 'boolean',
            'can_smell_taste' => 'boolean',
        ]);

        $path = $request->file('image')->store('rays', 'public');

        $ray = Ray::create([
            'user_id' => Auth::id(),
            'image_path' => $path,
            'temperature' => $request->temperature,
            'systolic_bp' => $request->systolic_bp,
            'heart_rate' => $request->heart_rate,
            'has_cough' => $request->has_cough,
            'has_headaches' => $request->has_headaches,
            'can_smell_taste' => $request->can_smell_taste,
        ]);

        return response()->json([
            'message' => 'Ray uploaded successfully.',
            'data' => $ray
        ], 201);
    }

    public function index()
    {
        $user = Auth::user();

        $rays = $user->rays()->latest()->get()->map(function ($ray) {
            return [
                'id' => $ray->id,
                'image_url' => asset('storage/' . $ray->image_path),
                'temperature' => $ray->temperature,
                'systolic_bp' => $ray->systolic_bp,
                'heart_rate' => $ray->heart_rate,
                'has_cough' => $ray->has_cough,
                'has_headaches' => $ray->has_headaches,
                'can_smell_taste' => $ray->can_smell_taste,
                'ai_status' => $ray->ai_status,
                'ai_summary' => $ray->ai_summary,
                'ai_confidence' => $ray->ai_confidence,
                'differential_diagnosis' => $ray->differential_diagnosis,
                'uploaded_at' => $ray->created_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'message' => 'Rays retrieved successfully.',
            'data' => $rays
        ]);
    }
}
