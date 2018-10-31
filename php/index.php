<?php
/*

Copyright 2017 Amazon.com, Inc. or its affiliates. All Rights Reserved.

Licensed under the Apache License, Version 2.0 (the "License"). You may not use this file except in compliance with the License. A copy of the License is located at

    http://aws.amazon.com/apache2.0/

or in the "license" file accompanying this file. This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.

*/

require 'twitch.php';

$provider = new TwitchProvider([
    'clientId'                => '<YOUR CLIENT ID HERE>',     // The client ID assigned when you created your application
    'clientSecret'            => '<YOUR CLIENT SECRET HERE>', // The client secret assigned when you created your application
    'redirectUri'             => '<YOUR REDIRECT URL HERE>',  // Your redirect URL you specified when you created your application
    'scopes'                  => ['user_read']                // The scopes you would like to request
]);

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider, and store state in session
    $authorizationUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();

    // Display link to start auth flow
    echo '<html>
        <head><title>Twitch Auth Sample</title></head>
        <body>
            <a href="' . $authorizationUrl . '"><img src="http://ttv-api.s3.amazonaws.com/assets/connect_dark.png"></a>
        </body>
    </html>';
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    
    exit('Invalid state');

} else {

    try {

        // Get an access token using authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // Using the access token, get user profile
        $resourceOwner = $provider->getResourceOwner($accessToken);
        $user = $resourceOwner->toArray();

        echo '<html>
            <head><title>Twitch Auth Sample</title></head>
            <body>
                <table>
                    <tr><th>Access Token</th><td>' . htmlspecialchars($accessToken->getToken()) . '</td></tr>
                    <tr><th>Refresh Token</th><td>' . htmlspecialchars($accessToken->getRefreshToken()) . '</td></tr>
                    <tr><th>Username</th><td>' . htmlspecialchars($user['display_name']) . '</td></tr>
                    <tr><th>Bio</th><td>' . htmlspecialchars($user['bio']) . '</td></tr>
                    <tr><th>Image</th><td><img src="' . htmlspecialchars($user['logo']) . '"></td></tr>
                </table>
            </body>
        </html>';

        // You can now create authenticated API requests through the provider.
        //$request = $provider->getAuthenticatedRequest(
        //    'GET',
        //    'https://api.twitch.tv/kraken/user',
        //    $accessToken
        //);

    } catch (Exception $e) {
        exit('Caught exception: '.$e->getMessage());
    }
}
