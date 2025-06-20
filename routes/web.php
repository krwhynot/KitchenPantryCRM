<?php

use App\Livewire\DashboardComponent;
use App\Livewire\OrganizationsComponent;
use App\Livewire\ContactsComponent;
use App\Livewire\InteractionsComponent;
use App\Livewire\ReportsComponent;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardComponent::class)->name('home');
Route::get('/dashboard', DashboardComponent::class)->name('dashboard');
Route::get('/organizations', OrganizationsComponent::class)->name('organizations');
Route::get('/contacts', ContactsComponent::class)->name('contacts');
Route::get('/interactions', InteractionsComponent::class)->name('interactions');
Route::get('/reports', ReportsComponent::class)->name('reports');
