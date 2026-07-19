<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Placeholder dashboard admin — diganti DashboardController di Fase 5.
Route::get('/', fn () => Inertia::render('admin/placeholder'))->name('dashboard');
