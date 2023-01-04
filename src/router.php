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

    print("Please open the following URL in your browser: \n" . $apiAuthUrl);

});

Route::add('/oauth', function() {

    $receivedState = $_GET['state'];
    $authorizationCode = $_GET['code'];

    $test = new OAuthHandler();
    // OAuthHandler::retrieveTokens($authorizationCode);
    // Get access token
    // console.log("Getting tokens...");
    // const tokens = await retrieveTokens(authorizationCode);
    // console.log("Received new tokens: \n", tokens);
    http://localhost:8080/oauth?state=fbdc0997-977f-45a5-82e7-724f421f2b3e&session_state=64e289ba-3cd8-4a91-81a7-181e5b4fd0c4&code=5ffe98f0-b9de-4960-979a-661ff1f60b63.64e289ba-3cd8-4a91-81a7-181e5b4fd0c4.e6d373ba-e5d1-474e-8a3b-1b636437244c
});

Route::run('/');


?>