<?php

use Illuminate\Http\Request;

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

Route::group(['middleware' => ['api']], function () {

    Route::group(['prefix' => 'etats-objectif'], function () {
        Route::get('/', 'TableauDeBord\EtatsObjectifController@index');
        Route::get('by-year/{year}', 'TableauDeBord\EtatsObjectifController@findByYear');
        Route::get('by-year-and-state/{year}/{stateId}', 'TableauDeBord\EtatsObjectifController@findByYearAndState');
        Route::get('sesonality-objectif/{objectifId}/', 'TableauDeBord\EtatsObjectifController@findMonthlySesonalityByObjectifId');
        Route::get('evolution-and-participation-rates-products/{objectifId}/', 'TableauDeBord\EtatsObjectifController@findEvolutionAndParticipationRatesProducts');
        Route::get('evolution-and-participation-rates-clinics/{objectifId}/', 'TableauDeBord\EtatsObjectifController@findEvolutionAndParticipationRatesClinics');
    });
});
