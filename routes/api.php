<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
    $api->group(['prefix' => 'auth'], function(Router $api) {
        $api->post('signup', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
        $api->get('verifyToken', 'App\\Api\\V1\\Controllers\\SignUpController@verifyToken');
        $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');
        $api->post('sendOTP', 'App\\Api\\V1\\Controllers\\OTPController@sendOTP');
        $api->post('verifyOTP', 'App\\Api\\V1\\Controllers\\OTPController@verifyOTP');

        $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
        $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');
    });

    $api->get('generateQR', 'App\\Api\\V1\\Controllers\\QRController@generateQR');
    $api->post('qrData', 'App\\Api\\V1\\Controllers\\QueueController@getTokenData');
    $api->get('leaveQueue', 'App\\Api\\V1\\Controllers\\QueueController@leaveQueue');
    $api->post('savePushToken', 'App\\Api\\V1\\Controllers\\SignUpController@savePushToken');
    $api->get('nextToken', 'App\\Api\\V1\\Controllers\\QueueController@nextToken');
    $api->get('deleteQueue', 'App\\Api\\V1\\Controllers\\QueueController@deleteQueue');
    $api->get('checkQueue', 'App\\Api\\V1\\Controllers\\QueueController@checkQueue');
    
    $api->resource('contact', 'App\\Api\\V1\\Controllers\\ContactController');

    $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
        $api->get('protected', function() {
            return response()->json([
                'message' => 'Access to protected resources granted! You are seeing this text as you provided the token correctly.'
            ]);
        });

        $api->get('refresh', [
            'middleware' => 'jwt.refresh',
            function() {
                return response()->json([
                    'message' => 'By accessing this endpoint, you can refresh your access token at each request. Check out this response headers!'
                ]);
            }
        ]);
    });

    $api->get('hello', function() {
        return response()->json([
            'message' => 'This is a simple example of item returned by your APIs. Everyone can see it.'
        ]);
    });
});
