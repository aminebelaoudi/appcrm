<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CentrisController;
use App\Http\Controllers\PropertyRelationController;

use App\Http\Controllers\GhlAuthController;
use App\Http\Controllers\CentrisSubmissionController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// GHL OAuth routes
Route::get('/ghl/login', [GhlAuthController::class, 'redirectToGhl'])->name('ghl.login');
Route::get('/ghl/callback', [GhlAuthController::class, 'handleGhlCallback'])->name('ghl.callback');
Route::get('/crm/oauth', [GhlAuthController::class, 'handleGhlCallback']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/properties', [CentrisController::class, 'showProperties'])
    ->name('centris.properties')
    ->middleware('iframe');

Route::get('/properties/{listingKey}', [CentrisController::class, 'showPropertyDetails'])
    ->name('centris.property.details')
    ->middleware('iframe');

// API Routes pour GHL
Route::get('/api/ghl/contacts', [CentrisController::class, 'getGHLContacts'])
    ->name('api.ghl.contacts')
    ->middleware(['api.rate.limit']);

Route::get('/api/ghl/opportunities', [CentrisController::class, 'getGHLOpportunities'])
    ->name('api.ghl.opportunities')
    ->middleware(['api.rate.limit']);

Route::post('/api/centris/submissions/webhook', [CentrisSubmissionController::class, 'storeWebhook'])
    ->name('api.centris.submissions.webhook')
    ->middleware(['api.rate.limit']);

// API Routes pour les relations Propriété-Personnes-Opportunités
Route::prefix('api/properties')->middleware(['api.rate.limit'])->group(function () {
    // Personnes impliquées
    Route::get('{listingId}/persons', [PropertyRelationController::class, 'getPersons'])->name('api.property.persons');
    Route::post('persons', [PropertyRelationController::class, 'addPerson'])->name('api.property.persons.add');
    Route::put('persons/{id}/role', [PropertyRelationController::class, 'updatePersonRole'])->name('api.property.persons.role');
    Route::delete('persons/{id}', [PropertyRelationController::class, 'removePerson'])->name('api.property.persons.remove');
    
    // Opportunités liées
    Route::get('{listingId}/opportunities', [PropertyRelationController::class, 'getOpportunities'])->name('api.property.opportunities');
    Route::post('opportunities', [PropertyRelationController::class, 'addOpportunity'])->name('api.property.opportunities.add');
    Route::delete('opportunities/{id}', [PropertyRelationController::class, 'removeOpportunity'])->name('api.property.opportunities.remove');
});
