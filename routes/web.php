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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/lolMatch/{idioma}/{region}/{summoonerName}','lolController@getMatchHistory');
Route::get('/liveGame/{idioma}/{region}/{summonerName}','liveGameController@getLiveGame');
Route::get('/SummonerDataBot/{region}/{summonerName}','BotController@SummonerData');
Route::get('/Master/{idioma}/{region}/{summonerName}','MasterAppController@GetLeague');
Route::get('/Rank/','RankController@GetRank');
Route::get('/SummonerDataBotChamp/{champ}','BotController@ChampsTips');
