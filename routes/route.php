<?php

use App\Controllers\UserController;
use Support\Request;
use Support\Route;
use Support\View;
use Support\CSRFToken;
use Support\AuthMiddleware; //<-- Penambahan Middleware atau session login
use Support\Crypto;
use Support\UUID;
use Support\Response;

handleMiddleware();
Route::init($prefix);
Route::get('/', function(){
    View::render('home',[],'navbar/navbar');
});
Route::group([AuthMiddleware::class], function(){

});
Route::get('/dokumentasi', function(){
    $title = "Get Started";
    View::render('documentation/install',['title'=>$title],'documentation/doc');
})->name('instalasi');
Route::dispatch();
?>