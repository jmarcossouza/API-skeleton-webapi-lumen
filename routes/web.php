<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'teste'], function () use ($router) {
    $router->get('', 'TesteController@get');
});

$router->group(['prefix' => 'usuario'], function () use ($router) {
    $router->post('novo', 'AuthController@store');
    $router->post('login', 'AuthController@login');
    $router->post('reenviar-confirmacao-email', 'AuthController@reenviarConfirmarEmail');
    $router->post('confirmar-email', 'AuthController@confirmarEmail');
    $router->post('esqueci-minha-senha', 'AuthController@esqueciMinhaSenha');
    $router->post('redefinir-senha', 'AuthController@redefinirSenha');
});
