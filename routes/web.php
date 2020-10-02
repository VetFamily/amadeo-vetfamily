<?php

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

/*
|	Déclaration des routes pour l'authentification
*/
// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes...
Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('register', 'Auth\RegisterController@register');

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

// Password Change Routes...
Route::get('password/change', 'HomeController@showChangePasswordForm')->name('page.password.change');
Route::post('password/change','HomeController@changePassword')->name('password.change');

/* 
|	Déclaration des routes pour les vues 
*/
// Route principale
Route::get('/', ['middleware' => 'auth', 'uses' => 'HomeController@index']);

// Tableau de bord
Route::get('tableaudebord', 
	['as' => 'page.tableaudebord', 'middleware' => ['auth', 'auth.laboratoire'], 'uses' => 'Commun\HeaderController@showTableauDeBord']);
Route::post('tableaudebord-general', ['middleware' => 'auth', 'uses' => 'TableauDeBord\TableauDeBordController@getObjectifsGeneral']);

// Purchases
Route::get('statistiques', 
['as' => 'page.statistiques', 'middleware' => ['auth', 'auth.laboratoire'], 'uses' => 'Commun\HeaderController@showOngletStatistiques']);
Route::post('getListOfPurchasesByParams', ['middleware' => 'auth', 'uses' => 'Statistiques\StatistiquesController@getListOfPurchasesByParams']);
Route::get('downloadPurchasesCSV/{year}', 
['as' => 'page.statistiques-downloadPurchasesCSV', 'middleware' => 'auth', 'uses' => 'Statistiques\StatistiquesController@downloadPurchasesCSV']);
Route::get('downloadPurchasesByParamsCSV', 
['as' => 'page.statistiques-downloadPurchasesByParamsCSV', 'middleware' => 'auth', 'uses' => 'Statistiques\StatistiquesController@downloadPurchasesByParamsCSV']);
Route::post('getListOfClinicsByParams', ['middleware' => 'auth', 'uses' => 'Parametrage\ParametrageController@getListOfClinicsByParams']);
Route::post('getCountOfClinicsByParams', ['middleware' => 'auth', 'uses' => 'Parametrage\ParametrageController@getCountOfClinicsByParams']);
Route::post('getListOfTherapeuticClassesByParams', ['middleware' => 'auth', 'uses' => 'Parametrage\ParametrageController@getListOfTherapeuticClassesByParams']);
Route::post('getListOfProductsByParams', ['middleware' => 'auth', 'uses' => 'Parametrage\ParametrageController@getListOfProductsByParams']);
Route::post('getCountOfProductsByParams', ['middleware' => 'auth', 'uses' => 'Parametrage\ParametrageController@getCountOfProductsByParams']);

// Cliniques
Route::get('cliniques', 
	['as' => 'page.cliniques', 'middleware' => ['auth', 'auth.laboratoire'], 'uses' => 'Commun\HeaderController@showCliniques']);
Route::get('clinic-ajax/downloadClinicsCSV', 'Ajax\CliniqueAjaxController@downloadClinicsCSV');
Route::resource('clinic-ajax', 'Ajax\CliniqueAjaxController');

// Produits
Route::get('produits', 
	['as' => 'page.produits', 'middleware' => ['auth', 'auth.veterinaire'], 'uses' => 'Commun\HeaderController@showProduits']);
Route::resource('produit-ajax', 'Ajax\ProduitAjaxController');

// Catégories
Route::get('categories', 
	['as' => 'page.categories', 'middleware' => 'auth', 'uses' => 'Commun\HeaderController@showCategories']);
Route::resource('categorie-ajax', 'Ajax\CategorieAjaxController');
Route::post('categorie-produit-ajax/showListOfProducts', 'Ajax\CategorieProduitAjaxController@showListOfProducts');
Route::resource('categorie-produit-ajax', 'Ajax\CategorieProduitAjaxController');

// Objectifs
Route::get('objectifs', 
	['as' => 'page.objectifs', 'middleware' => 'auth', 'uses' => 'Commun\HeaderController@showObjectifs']);
Route::get('downloadObjectifParCliniquesCSV/{objectifId}/{annee}', 
	['as' => 'page.objectifs-downloadParCliniquesCSV', 'middleware' => 'auth', 'uses' => 'Statistiques\StatistiquesObjectifsController@downloadObjectifParCliniquesCSV']);
Route::post('getCategoriesObjectifAjax', ['middleware' => 'auth', 'uses' => 'UtilsController@searchCategorieByCountryAndYearAndSupplier']);
Route::post('getLaboratoiresObjectifAjax', ['middleware' => 'auth', 'uses' => 'UtilsController@searchSuppliersByCountryAndYear']);
Route::resource('objectif-ajax', 'Ajax\ObjectifAjaxController');
Route::resource('objectif-produit-ajax', 'Ajax\ObjectifProduitAjaxController');

// Engagements
Route::get('engagements', 
	['as' => 'page.engagements', 'middleware' => ['auth', 'auth.laboratoire'], 'uses' => 'Commun\HeaderController@showEngagements']);
Route::resource('engagement-ajax', 'Ajax\EngagementAjaxController');

// Administration
Route::get('administration', 
	['as' => 'page.administration', 'middleware' => ['auth', 'auth.laboratoire'], 'uses' => 'Commun\HeaderController@showAdministration']);
Route::post('getCodesCentralesClinique', ['middleware' => 'auth', 'uses' => 'Administration\AdministrationController@searchCodesCentralesByClinique']);
Route::get('exportEstimationRFAExcel/{detail}/{mois_debut}/{annee_debut}/{mois_fin}/{annee_fin}/{entrepriseId}/{entrepriseCodesCentrales}/{annee_obj}', 
	['as' => 'exportEstimationRFAExcel', 'middleware' => 'auth', 'uses' => 'Administration\AdministrationController@exportEstimationRFAExcel']);
Route::get('exportBilanRFAExcel/{detail}/{annee}', 
	['as' => 'exportBilanRFAExcel', 'middleware' => ['auth'], 'uses' => 'Administration\AdministrationController@exportBilanRFAExcel']);
Route::get('exportExtractionPrixNetsExcel/{annee}/{remise}', 
	['as' => 'exportExtractionPrixNetsExcel', 'middleware' => 'auth', 'uses' => 'Administration\AdministrationController@exportExtractionPrixNetsExcel']);

app('debugbar')->disable();




