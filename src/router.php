<?php
// Autoload files using composer
require_once __DIR__ . '/../vendor/autoload.php';

use Sipgate\Io\Example\OAuth\OAuthHandler;
use Steampixel\Route;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/..");
$dotenv->load();


Route::add('/', function() {

    $clientId = $_ENV['clientId'];
    $redirectUri = $_ENV['redirectUri'];
    $oauthScope = $_ENV['oauthScope'];
    $sessionState = uniqid();

    //   const queryString = querystring.stringify(params);
    $params = array(
        "client_id" => $clientId,
        "redirect_uri" => $redirectUri,
        "scope" => $oauthScope,
        "response_type" => "code",
        "state" => $sessionState,
    );

    $queryString = http_build_query($params);
    $apiAuthUrl = $_ENV['authUrl']."?".$queryString;

    print("Please open the following URL in your browser: \n" . "<a href='".$apiAuthUrl."'> Link </a>");

});

Route::add('/oauth', function(){

    $receivedState = $_GET['state'];
    $authorizationCode = $_GET['code'];
    $tokenURL = $_ENV['tokenUrl'];

    $params =  array(
        "client_id" =>  $_ENV['clientId'],
        "client_secret" => $_ENV['clientSecret'],
        "redirect_uri" => $_ENV['redirectUri'],
        "code" => $authorizationCode,
        "grant_type" => 'authorization_code'
    );

    $params_string = http_build_query($params);
    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $tokenURL);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $params_string);

    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    $decoded_result = json_decode($result);
    $access_token = $decoded_result->access_token;
    $refresh_token = $decoded_result->refresh_token;

    echo $access_token;
    echo $refresh_token;
});

Route::run('/');


?>