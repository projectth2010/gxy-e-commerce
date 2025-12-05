<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::prefix('center')->group(function () {
    // Center Control APIs (e.g., tenant management) will be defined here.
});

Route::middleware(['tenant'])->prefix('tenant')->group(function () {
    // Tenant-facing APIs (catalog, orders, etc.) will be defined here.
});
