<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::get('/', function () {
    return view('welcome');
});

// Test Authentication Page
Route::get('/test-auth', function () {
    $token = session('auth_token');
    $user = null;
    
    if ($token) {
        try {
            $response = Http::withToken($token)->get(url('/api/user'));
            if ($response->successful()) {
                $user = $response->json('user');
            }
        } catch (\Exception $e) {
            session()->forget('auth_token');
            return redirect('/test-auth')->with('error', 'Session expired. Please login again.');
        }
    }
    
    return view('test-auth', [
        'token' => $token,
        'user' => $user,
        'apiUrl' => url('/api')
    ]);
})->name('test.auth');

// Handle Login Form
Route::post('/test-auth/login', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);
    
    try {
        $response = Http::post(url('/api/login'), [
            'email' => $request->email,
            'password' => $request->password,
            'device_name' => 'test-browser-' . Str::random(10)
        ]);
        
        if ($response->successful()) {
            $token = $response->json('token');
            session(['auth_token' => $token]);
            return redirect('/test-auth')->with('success', 'Login successful!');
        }
        
        return back()->with('error', 'Invalid credentials');
    } catch (\Exception $e) {
        return back()->with('error', 'Login failed: ' . $e->getMessage());
    }
});

// Handle Logout
Route::post('/test-auth/logout', function () {
    $token = session('auth_token');
    
    if ($token) {
        try {
            Http::withToken($token)->post(url('/api/logout'));
        } catch (\Exception $e) {
            // Ignore errors on logout
        }
    }
    
    session()->forget('auth_token');
    return redirect('/test-auth')->with('success', 'Logged out successfully');
});
