<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'age' => 'required|integer|min:0',
            'gender' => ['required', Rule::in(['male', 'female'])],
            'phone_number' => 'required|string|max:20',
            'medical_condition' => 'nullable|string',
            'role' => ['nullable', Rule::in(['doctor', 'patient'])],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'age' => $request->age,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'medical_condition' => $request->medical_condition,
            'role' => $request->role ?? 'patient',
        ]);

        $token = $user->createToken('med_api_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'User registered successfully'
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // $user->tokens()->delete();

        $token = $user->createToken('med_api_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'age' => $user->age,
                'gender' => $user->gender,
                'phone_number' => $user->phone_number,
                'medical_condition' => $user->medical_condition,
            ],
            'token' => $token,
            'dashboard' => $user->role === 'doctor' ? 'doctor_dashboard' : 'patient_dashboard',
            'message' => 'Login successful'
        ]);
    }

        public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

            public function dashboard(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'doctor') {
            return response()->json([
                'role' => 'doctor',
                'message' => 'Welcome to the doctor dashboard',
                'doctor_data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                ],
            ]);
        } else {
            return response()->json([
                'role' => 'patient',
                'message' => 'Welcome to the patient dashboard',
                'patient_data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'age' => $user->age,
                    'gender' => $user->gender,
                    'phone_number' => $user->phone_number,
                    'medical_condition' => $user->medical_condition,
                ],
            ]);
        }
    }

}
