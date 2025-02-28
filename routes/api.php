<?php

use App\Http\Controllers\CompanyController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {

    return User::all();
});


Route::middleware('throttle:60,1')->group(function () {
    Route::get('companies', [CompanyController::class, 'fetchCompanies']);
    Route::post('filter-companies', [CompanyController::class, 'filterCompanies']);
});