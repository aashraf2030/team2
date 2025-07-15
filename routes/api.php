<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\SocialAuthController;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/dashboard', [AuthController::class, 'dashboard']);

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'No user found with this email.'], 404);
    }

    $code = rand(100000, 999999);

    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        ['token' => bcrypt($code), 'created_at' => now()]
    );

    Mail::raw("Your password reset code is: $code", function ($message) use ($request) {
        $message->to($request->email)
            ->subject('Password Reset Code');
    });

    return response()->json(['message' => 'Reset code sent to your email.']);
});

Route::post('/verify-reset-code', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'code' => 'required|string'
    ]);

    $record = DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->first();

    if (!$record) {
        return response()->json(['message' => 'No reset request found.'], 404);
    }

    if (!Hash::check($request->code, $record->token)) {
        return response()->json(['message' => 'Invalid code.'], 400);
    }

    return response()->json(['message' => 'Code verified successfully.']);
});


Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'code' => 'required|string',
        'password' => 'required|confirmed|min:6',
    ]);

    $record = DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->first();

    if (!$record || !Hash::check($request->code, $record->token)) {
        return response()->json(['message' => 'Invalid reset attempt.'], 400);
    }

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    $user->update([
        'password' => Hash::make($request->password)
    ]);

    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return response()->json(['message' => 'Password has been reset successfully.']);
});

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);
