<img src="https://www.sipgatedesign.com/wp-content/uploads/wort-bildmarke_positiv_2x.jpg" alt="sipgate logo" title="sipgate" align="right" height="112" width="200"/>

# sipgate.io PHP OAuth example
To demonstrate how to authenticate against the sipgate REST API using the OAuth mechanism, 
we make use of the `/authorization/userinfo` endpoint which provides information about the user. 

> For further information regarding the sipgate REST API please visit https://api.sipgate.com/v2/doc

For educational purposes we do not use an OAuth client library in this example, but if you plan to implement authentication using OAuth in you application we recommend using one. You can find various client libraries here: [https://oauth.net/code/](https://oauth.net/code/).


## What is OAuth and when to use it
OAuth is a standard protocol for authorization. You can find more information on the OAuth website [https://oauth.net/](https://oauth.net/) or on wikipedia [https://en.wikipedia.org/wiki/OAuth](https://en.wikipedia.org/wiki/OAuth).

Applications that use the sipgate REST API on behalf of another user should use the OAuth authentication method instead of Basic Auth.


## Prerequisites
- PHP
- Composer

## Setup OAuth with sipgate
In order to authenticate against the sipgate REST API via OAuth you first need to create a Client in the sipgate Web App.

You can create a client as follows:

1. Navigate to [console.sipgate.com](https://console.sipgate.com/) and login with your sipgate account credentials
2. Make sure you are in the **Clients** tab in the left side menu
3. Click the **New client** button
4. Fill out the **New client** dialog (Find information about the Privacy Policy URL and Terms of use URL [here](#privacy-policy-url-and-terms-of-use-url))
5. The **Clients** list should contain your new client
6. Select your client
7. The entries **Id** and **Secret** are `YOUR_CLIENT_ID` and `YOUR_CLIENT_SECRET` required for the configuration of your application (see [Configuration](#configuration))
8. Now you just have to add your `REDIRECT_URI` to your Client by clicking the **Add redirect uri** button and fill in the dialog. In our example we provide a server within the application itself so we use `http://localhost:{port}/oauth` (the default port is `8080`).

Now your Client is ready to use.


### Privacy Policy URL and Terms of use URL
In the Privacy Policy URL and Terms of use URL you must supply in the **New Client** dialog when creating a new Client to use with OAuth you must supply the Privacy Policy URL and Terms of use URL of the Service you want to use OAuth authorization for. During development and testing you can provide any valid URL but later you must change them.


## Configuration
Create the .env file by copying the .env.example. Set the values according to the comment above each variable.

The `oauth_scope` defines what kind of access your Client should have to your account and is specific to your respective application. In this case, since we only want to get your basic account information as an example, the scope `account:read` is sufficient.

```
oauth_scope=account:read
```
> Visit https://developer.sipgate.io/rest-api/oauth2-scopes/ to see all available scopes

The `redirect_uri` which we have previously used in the creation of our Client is supplied to the sipgate login page to specify where you want to be redirected after successful login. As explained above, our application provides a small web server itself that handles HTTP requests directed at `http://localhost:8080/oauth`. In case there is already a service listening on port `8080` of your machine you can choose a different port number, but be sure to adjust both the `redirect_uri` and the `port` property accordingly.


## Install dependencies
Navigate to the project's root directory and run:
```bash
$ composer install
```


## Execution
Run the application:
```bash
$ php -S localhost:8080 -t src/ src/router.php
```


## How It Works
The main function of our application looks like this: 

In the [router.php](./src/router.php) we first load the environment variables from [.env](./.env).
```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();
```

We then generate a unique identifier `io_session_identifier` for our authorization process and save it in the browser as a cookie so that we can match a server response to our request later. The authorization URI is composed from the properties previously loaded from the configuration file and printed to the browser when navigating to `http://localhost:8080/`.
```php
if(!isset($_COOKIE['io_session_identifier'])) {
    $io_session_identifier = uniqid();
    setcookie('io_session_identifier', $io_session_identifier);
} else {
    $io_session_identifier = $_COOKIE['io_session_identifier'];
}

// ...

Route::add('/', function () {

    $clientId = $_ENV['clientId'];
    $redirectUri = $_ENV['redirectUri'];
    $oauthScope = $_ENV['oauthScope'];

    $params = [
        "client_id" => $clientId,
        "redirect_uri" => $redirectUri,
        "scope" => $oauthScope,
        "response_type" => "code",
        "state" => $GLOBALS['io_session_identifier']
    ];

    $queryString = http_build_query($params);
    $apiAuthUrl = $_ENV['authUrl'] . "?" . $queryString;

    print("Please open the following URL in your browser: \n" . "<a href='" . $apiAuthUrl . "'> Link </a>");

});
```

Pressing the link in your browser takes you to the sipgate login page where you need to confirm the scope that your Client is requesting access to before logging in with your sipgate credentials. You are then redirected to `http://localhost:8080/oauth` and our application's web server receives your request.

We create another route to handle the `/oauth` request. First, we fetch the `session_identifier` for the request and check if it matches the previously saved `io_session_identifier` cookie. In the case of multiple concurrent authorization processes this state also serves to match pairs of request and response.
```php
$query_session_identifier = $_GET['state'];

if ($query_session_identifier != $GLOBALS['io_session_identifier']) {
	echo 'State in the callback does not match the state in the original request.';
	return;
}
```
 We use the code obtained from the request to fetch a set of tokens from the authorization server and try them out by making an request to the `/authorization/userinfo` endpoint of the REST API. Lastly, we use the refresh token to obtain another set of tokens. Note that this invalidates the previous set.

The `retrieveTokens` function fetches the tokens from the authorization server using a POST request via `php-curl`. The POST-Request must contain the `client_id`, `client_secret`, `redirect_uri`, `code` and `grant_type` as form data.
```php
function retrieveTokens()
{
    $params = [
        "client_id" => $_ENV['clientId'],
        "client_secret" => $_ENV['clientSecret'],
        "redirect_uri" => $_ENV['redirectUri'],
        "code" => $_GET['code'],
        "grant_type" => 'authorization_code',
    ];

    $params_string = http_build_query($params);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $_ENV['tokenUrl']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    $decoded_result = json_decode($result);
    $access_token = $decoded_result->access_token;
    $refresh_token = $decoded_result->refresh_token;


    return [
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
    ];
}
```
In some cases where the package might not be installed on your machine by default, you can install it using your preferred package manager:
```shell
sudo apt-get install php-curl
```

The `refreshTokens` function is very similar to the `retrieveTokens` function. It differs in that we set the `grant_type` to `refresh_token` to indicate that we want to refresh our token, and provide the `refresh_token` we got from the `retrieveTokens` function instead of the `code`.
> ```php
> ...
> "refresh_token" => $refresh_token,
> "grant_type" => 'refresh_token',
> ...
> ```

To see if authorization with the token works, we query the `/authorization/userinfo` endpoint of the REST API.
```php
function userInfo($accessToken)
{
    $options = array(
        'http' => array(
            'header' => "Authorization: Bearer $accessToken\r\n",
        ),
    );
    $context = stream_context_create($options);
    return file_get_contents($_ENV['testApiEndpoint'], false, $context);
}
```
To use the token for authorization we set the `Authorization` header to `Bearer` followed by a space and the `accessToken` we obtained with the `retrieveTokens` or `refreshTokens` function.


## Common Issues

### "State in the callback does not match the state in the original request"
Possible reasons are:
- the application was restarted and you used old url again or refreshed the browser tab


### "Error: listen EADDRINUSE: address already in use :::{port}"
Possible reasons are:
- another instance of the application is running
- the port configured in the [.env](./env) file and [execution command](#Execution) is used by another application


### "Error: listen EACCES: permission denied 0.0.0.0:{port}"
Possible reasons are:
- you do not have the permission to bind to the specified port. This can happen if you use port 80, 443 or another well-known port which you can only bind to if you run the application with superuser privileges


### "invalid parameter: redirect_uri"
Possible reasons are:
- the redirect_uri in the [.env](./env) is invalid or not set
- the redirect_uri is not correctly configured the sipgate Web App (You can find more information about the configuration process in the [Setup OAuth with sipgate](#setup-oauth-with-sipgate) section)


### "client not found" or "invalid client_secret"
Possible reasons are:
- the client_id or client_secret configured in the [.env](./.env) is invalid. You can check them in the sipgate Web App. See [Setup OAuth with sipgate](#setup-oauth-with-sipgate)


## Related
+ [OAuth RFC6749](https://tools.ietf.org/html/rfc6749)
+ [oauth.net](https://oauth.net/)
+ [auth0.com/docs/](https://auth0.com/docs/)

## Contact Us
Please let us know how we can improve this example. 
If you have a specific feature request or found a bug, please use **Issues** or fork this repository and send a **pull request** with your improvements.


## License
This project is licensed under **The Unlicense** (see [LICENSE file](./LICENSE)).


## External Libraries
This code uses the following external libraries
+ PHP dotenv:
  + Licensed under the [The BSD 3-Clause License](https://opensource.org/licenses/BSD-3-Clause)
  + Website: https://github.com/vlucas/phpdotenv
+ SimplePHPRouter:
  + Licensed under the [MIT License](https://opensource.org/licenses/MIT)
  + Website: https://github.com/steampixel/simplePHPRouter


----
[sipgate.io](https://www.sipgate.io) | [@sipgateio](https://twitter.com/sipgateio) | [API-doc](https://api.sipgate.com/v2/doc)
