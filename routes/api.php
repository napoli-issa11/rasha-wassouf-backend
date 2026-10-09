<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\HomeSlideController;
use App\Http\Controllers\Api\StatisticController;
use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ProjectInquiryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\CategoryController;

/*
|--------------------------------------------------------------------------
| Public Architectural Portfolio Routes
|--------------------------------------------------------------------------
| Accessible to visitors without authentication.
*/

// Projects & Categories
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{id}', [ProjectController::class, 'show']);
Route::post('/projects/{id}/like', [ProjectController::class, 'toggleLike']);
Route::post('/projects/upload', [ProjectController::class, 'uploadImage']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::post('/categories/upload', [CategoryController::class, 'uploadImage']);

// Real-Time Dashboard Stats (Counts live database records)
Route::get('/admin/stats', [ProjectController::class, 'dashboardStats']);
Route::get('/stats', [ProjectController::class, 'dashboardStats']);

// Visitor Comments (Saved with default status = 'pending')
Route::post('/projects/{id}/comments', [CommentController::class, 'store']);

// Home Hero Slides Showcase
Route::get('/home-slides', [HomeSlideController::class, 'index']);

// About Page Content & Statistics
Route::get('/about', [AboutController::class, 'show']);
Route::get('/statistics', [StatisticController::class, 'index']);

// Global Studio Settings & Social Media Links
Route::get('/settings', [SettingController::class, 'index']);

// Project Brief Inquiries (Rate-limited: 10 requests per minute to thwart spam/abuse)
Route::post('/inquiries', [ProjectInquiryController::class, 'store'])->middleware('throttle:10,1');

// Admin Authentication (Public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Admin Dashboard & CMS Routes
|--------------------------------------------------------------------------
| Requires 'auth:sanctum' token.
*/
Route::middleware('auth:sanctum')->group(function () {
    // Current user & logout
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/admin/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/admin/logout', [AuthController::class, 'logout']);

    // Admin Credentials & Settings Update
    Route::post('/settings/update', [AuthController::class, 'updateCredentials']);
    Route::put('/settings/update', [AuthController::class, 'updateCredentials']);
    Route::post('/admin/settings/update', [AuthController::class, 'updateCredentials']);
    Route::put('/admin/settings/update', [AuthController::class, 'updateCredentials']);
    Route::post('/admin/credentials', [AuthController::class, 'updateCredentials']);
    Route::put('/admin/credentials', [AuthController::class, 'updateCredentials']);

    // Comment Moderation System
    Route::get('/admin/comments/pending', [CommentController::class, 'pending']);
    Route::patch('/admin/comments/{id}/approve', [CommentController::class, 'approve']);
    Route::delete('/admin/comments/{id}', [CommentController::class, 'destroy']);

    // Projects CMS (Full CRUD)
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::post('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{id}/upload-image', [ProjectController::class, 'uploadImage']);
    Route::post('/admin/projects/upload', [ProjectController::class, 'uploadImage']);

    // Categories CMS (Full CRUD & Direct Cloudinary Upload)
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::post('/categories/{id}/upload-image', [CategoryController::class, 'uploadImage']);
    Route::post('/admin/categories/upload', [CategoryController::class, 'uploadImage']);

    // Home Page CMS (Full CRUD)
    Route::post('/home-slides', [HomeSlideController::class, 'store']);
    Route::put('/home-slides/{id}', [HomeSlideController::class, 'update']);
    Route::delete('/home-slides/{id}', [HomeSlideController::class, 'destroy']);

    // About Page Content CMS
    Route::put('/admin/about', [AboutController::class, 'update']);
    Route::post('/admin/about', [AboutController::class, 'update']);

    // About Page Statistics CMS
    Route::put('/admin/statistics/{id}', [StatisticController::class, 'update']);
    Route::put('/admin/statistics', [StatisticController::class, 'batchUpdate']);
    Route::post('/admin/statistics', [StatisticController::class, 'batchUpdate']);

    // Global Settings & Social Media Links CMS
    Route::put('/admin/settings', [SettingController::class, 'update']);
    Route::post('/admin/settings', [SettingController::class, 'update']);

    // Direct Media & Image Uploads (Cloudinary CDN)
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::post('/admin/upload', [UploadController::class, 'upload']);

    // Project Inquiries & Briefs CMS
    Route::get('/admin/inquiries', [ProjectInquiryController::class, 'index']);
    Route::get('/admin/inquiries/{id}', [ProjectInquiryController::class, 'show']);
    Route::patch('/admin/inquiries/{id}/status', [ProjectInquiryController::class, 'updateStatus']);
    Route::delete('/admin/inquiries/{id}', [ProjectInquiryController::class, 'destroy']);
});

// Admin Direct Client Email Reply (Protected with internal multi-auth validation)
Route::post('/admin/reply-client', [ProjectInquiryController::class, 'reply']);
Route::post('/admin/inquiries/{id}/reply', [ProjectInquiryController::class, 'reply']);

