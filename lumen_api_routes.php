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

/** @var \Laravel\Lumen\Application $app */
$app->get('/', 'PagesController@home');

$app->post('/users', 'UserController@register');
$app->post('/auth/token', 'UserController@getToken');
$app->post('/user/password/reset', 'UserController@requestResetPassword');
$app->patch('/user/password/reset/{token}', 'UserController@resetPassword');
$app->patch('/user/email-confirmation', 'UserController@confirmEmail');
$app->post('/user/password/reset/validate', 'UserController@isPasswordResetTokenValid');
$app->post('/dlr', 'SmsController@smsDeliverEvent');

$app->group(['middleware' => 'auth', 'namespace' => 'App\Http\Controllers'], function () use ($app) {
    $app->get('/user', 'UserController@show');
    $app->patch('/user', 'UserController@update');
    $app->put('/user/password', 'UserController@changePassword');
    $app->delete('/user', 'UserController@delete');
    $app->post('/user/email-confirmation', 'UserController@sendConfirmationEmail');
    $app->get('/integrations/shopify/auth-url', 'ShopifyController@getAuthorizationUri');
    $app->post('/integrations/shopify/confirmation', 'ShopifyController@confirmAuthorization');
    $app->post('/sms', 'SmsController@sendSms');
    $app->post('/mail', 'MailController@sendMail');
});
