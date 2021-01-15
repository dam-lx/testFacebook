<?php

namespace App\Http\Controllers;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Scottybo\LaravelFacebookSdk\LaravelFacebookSdk;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $fb;
    public function __construct (LaravelFacebookSdk $fb )
    {
        $this->fb = $fb;
    }

    public function test () {
        // Send an array of permissions to request
        $login_url = $this->fb->getLoginUrl(['email']);
        // Obviously you'd do this in blade :)
        echo '<a href="' . $login_url . '">Login with Facebook</a>';
    }

    public function test2(Request $request) {
        try {
            $token = $this->fb->getAccessTokenFromRedirect();
        } catch (FacebookSDKException $e) {
            dd($e->getMessage());
        }

        // Access token will be null if the user denied the request
        // or if someone just hit this URL outside of the OAuth flow.
        if (! $token) {
            // Get the redirect helper
            $helper = $this->fb->getRedirectLoginHelper();

            if (! $helper->getError()) {
                abort(403, 'Unauthorized action.');
            }

            // User denied the request
            dd(
                $helper->getError(),
                $helper->getErrorCode(),
                $helper->getErrorReason(),
                $helper->getErrorDescription()
            );
        }

        if (! $token->isLongLived()) {
            // OAuth 2.0 client handler
            $oauth_client = $this->fb->getOAuth2Client();

            // Extend the access token.
            try {
                $token = $oauth_client->getLongLivedAccessToken($token);
            } catch (FacebookSDKException $e) {
                dd($e->getMessage());
            }
        }

        $this->fb->setDefaultAccessToken($token);

        // Save for later
        Session::put('fb_user_access_token', (string) $token);

        // Get basic info on the user from Facebook.
        try {
            $response = $this->fb->get('/me?fields=id,name,email');
        } catch (FacebookSDKException $e) {
            dd($e->getMessage());
        }

        return redirect('/')->with('message', 'Successfully logged in with Facebook');
    }

    public function test3() {
        try {
            // Returns a `Facebook\FacebookResponse` object
            $response = $this->fb->get(
                '/145895315576553_1525788620920542/likes',
                \session()->get('fb_user_access_token')
            );
        } catch(FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $graphNode = $response->getGraphNode();
    }
}
