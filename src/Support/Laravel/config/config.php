<?php

return array(

    /*
    |--------------------------------------------------------------------------
	| Application identity
	|--------------------------------------------------------------------------
    |
    | Client ID and Secret used to associate
    | your server, script, or program with
    | a specific application.
    |
    */

    'clientId' =>        '',
    'clientSecret' =>    '',

    /*
    |--------------------------------------------------------------------------
    | OAuth redirect URI.
    |--------------------------------------------------------------------------
    |
    | The redirect_uri specifies where we redirect users
    | after they have chosen whether or not to
    | authenticate your application.
    |
    */

    'redirectUri' =>    '',

    /*
    |--------------------------------------------------------------------------
    | Permission scope.
    |--------------------------------------------------------------------------
    |
    | You may provide an optional scope parameter to request
    | additional permissions outside of the “basic"
    | permissions scope. Learn more about scope.
    |
    | Valid values:
    |     'basic' - to read any and all data related to a user - granted by default
                    (e.g. following/followed-by lists, photos, etc.)
    |     'comments' - to create or delete comments on a user’s behalf
    |     'relationships' - to follow and unfollow users on a user’s behalf
    |     'likes' - to like and unlike items on a user’s behalf
    |
    */
    'scope' => ['basic'],

);
