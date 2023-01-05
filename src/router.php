<?php
// Autoload files using composer
require_once __DIR__ . '/../vendor/autoload.php';

use Sipgate\Io\Example\OAuth\OAuthHandler;
use Steampixel\Route;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__."/..");
$dotenv->load();

function retrieveTokens(){
    $params =  [
        "client_id" =>  $_ENV['clientId'],
        "client_secret" => $_ENV['clientSecret'],
        "redirect_uri" => $_ENV['redirectUri'],
        "code" =>  $_GET['code'],
        "grant_type" => 'authorization_code'
    ];

    $params_string = http_build_query($params);
    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $_ENV['tokenUrl']);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $params_string);

    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    $decoded_result = json_decode($result);
    $access_token = $decoded_result->access_token;
    $refresh_token = $decoded_result->refresh_token;

    return [
        'access_token'=>$access_token,
        'refresh_token'=>$refresh_token
    ];
}

function refreshTokens($refresh_token){
    $params =  [
        "client_id" =>  $_ENV['clientId'],
        "client_secret" => $_ENV['clientSecret'],
        "refresh_token" => $refresh_token,
        "grant_type" => 'refresh_token'
    ];

    $params_string = http_build_query($params);
    $ch = curl_init();

    curl_setopt($ch,CURLOPT_URL, $_ENV['tokenUrl']);
    curl_setopt($ch,CURLOPT_POST, true);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $params_string);

    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    $decoded_result = json_decode($result);

    $access_token = $decoded_result->access_token;
    $refresh_token = $decoded_result->refresh_token;

    return [
        'access_token'=>$access_token,
        'refresh_token'=>$refresh_token
    ];
}

function userInfo($accessToken) {
    $options = array(
        'http' => array(
            'header'  => "Authorization: Bearer $accessToken\r\n",
        ),
    );
    $context  = stream_context_create($options);
    $response = file_get_contents($_ENV['testApiEndpoint'], false, $context);
    echo "Response: ";
    echo $response;
    return $response;
}


Route::add('/', function() {

    $clientId = $_ENV['clientId'];
    $redirectUri = $_ENV['redirectUri'];
    $oauthScope = $_ENV['oauthScope'];
    $sessionState = uniqid();

    //   const queryString = querystring.stringify(params);
    $params = [
        "client_id" => $clientId,
        "redirect_uri" => $redirectUri,
        "scope" => $oauthScope,
        "response_type" => "code",
        "state" => $sessionState,
    ];

    $queryString = http_build_query($params);
    $apiAuthUrl = $_ENV['authUrl']."?".$queryString;

    print("Please open the following URL in your browser: \n" . "<a href='".$apiAuthUrl."'> Link </a>");

});

//retrieveToken
Route::add('/oauth', function(){
    define("retrievedTokens", retrieveTokens());
    $access_token = retrievedTokens['access_token'];
    $refresh_token = retrievedTokens['refresh_token'];
    echo "Access Token: ";
    echo $access_token;
    userInfo($access_token);
});

Route::run('/');


?>