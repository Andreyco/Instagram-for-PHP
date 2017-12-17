![instagram-logo-400x400](https://user-images.githubusercontent.com/829963/27837919-95368730-60e7-11e7-8071-0ce79f35579b.png)
# Instagram PHP API 4.0.0

A PHP wrapper for the Instagram API.
Feedback or bug reports are appreciated.

> Supports Laravel 5.3, 5.4 & 5.5

> Now supports [Instagram video](#instagram-videos) responses.


# Requirements
- Registered Instagram App
- PHP 5.3 or higher
- cURL

# Get started

To use the Instagram API with OAuth you have to register yourself as developer at the [Instagram Developer Platform](http://instagr.am/developer/register/) and set up an App. Take a look at the [uri guidlines](#samples-for-redirect-urls) before registering a redirect URI.

Please note that Instagram mainly refers to »Clients« instead of »Apps«. So »Client ID« and »Client Secret« are the same as »App Key« and »App Secret«.

> A good place to get started is the example App.

## Initialize the class
### Pure PHP
```php
<?php
    require '../vendor/autoload.php';

	$instagram = new Andreyco\Instagram\Client(array(
      'apiKey'      => 'YOUR_APP_KEY',
      'apiSecret'   => 'YOUR_APP_SECRET',
      'apiCallback' => 'YOUR_APP_CALLBACK',
      'scope'       => array('basic'),
    ));

    echo "<a href='{$instagram->getLoginUrl()}'>Login with Instagram</a>";
?>
```
### Laravel
This package offers Laravel support out of the box. These steps are required to setup the package.

**Installation**
```shell
composer require andreyco/instagram
```

**Add Service provider and register Facade**

```php
'providers' => array(
    // ...
    Andreyco\Instagram\Support\Laravel\ServiceProvider\Instagram::class,
    // ...
),

'aliases' => array(
    // ...
    'Instagram' => Andreyco\Instagram\Support\Laravel\Facade\Instagram::class,
    // ...
),
```

**Configuration**
```php
// Pushlish configuration file.
php artisan vendor:publish --provider="Andreyco\Instagram\Support\Laravel\ServiceProvider\Instagram"

// Edit previously created `config/instagram.php` file
return [
    'clientId'     => '...',
    'clientSecret' => '...',
    'redirectUri'  => '...',
    'scope'        => ['basic'],
]
```


# Usage
In Laravel application, you can access library by simply using `Instagram` facade, e.g.
```php
Instagram::getLoginUrl();
```
For usage in pure PHP, you have to create instance of class.

```php
$instagram = new Andreyco\Instagram\Client($config);
$instagram->getLoginUrl()
```

## Authentication example
```php
<?php
    // Generate and redirect to login URL.
    $url = Instagram::getLoginUrl();

    // After allowing to access your profile, grab authorization *code* when redirected back to your page.
    $code = $_GET['code'];
    $data = Instagram::getOAuthToken($code);

    // Now, you have access to authentication token and user profile
    echo 'Your username is: ' . $data->user->username;
    echo 'Your access token is: ' . $data->access_token;
?>
```

## Get user likes example

```php
<?php
    // Set user access token
    Instagram::setAccessToken($accessToken);

    // Get all user likes
    $likes = Instagram::getUserLikes();

    // Take a look at the API response
    echo '<pre>';
    print_r($likes);
    echo '<pre>';
?>
```

# Available methods

## Setup Instagram

`new Instagram($config: Array|String);`

`array` if you want to authenticate a user and access its data:

```php
new Instagram([
    'apiKey'      => 'YOUR_APP_KEY',
    'apiSecret'   => 'YOUR_APP_SECRET',
    'apiCallback' => 'YOUR_APP_CALLBACK'
]);
```

`string` if you *only* want to access public data:

```php
new Instagram('YOUR_APP_KEY');
```

## Get login URL

`getLoginUrl($scope: [Array], $state: [string])`

```php
getLoginUrl(['basic', 'likes'], 'uMFYKG5u6v');
```

**Optional scope parameters:**
To find out more about Scopes, please visit https://www.instagram.com/developer/authorization/

## Get OAuth token

`getOAuthToken($code, <true>/<false>)`

`true` : Return only the OAuth token
`false` *[default]* : Returns OAuth token and profile data of the authenticated user

## Set / Get access token

Set access token, for further method calls:
`setAccessToken($token)`

Return access token, if you want to store it for later usage:
`getAccessToken()`

## User methods

- `getUser()`
- `getUser($id)`
- `searchUser($name, <$limit>)`
- `getUserMedia($id, <$limit>)`
- `getUserLikes(<$limit>)`
- `getUserMedia(<$id>, <$limit>)`
    - if an `$id` isn't defined, or equals to `self` it returns the media of the logged in user

> [Sample responses of the User Endpoints.](http://instagram.com/developer/endpoints/users/)

## Relationship methods

- `getSelfFollows()`
- `getSelfFollowedBy()`
- `getUserRelationship($id)`
- `modifyRelationship($action, $user)`
    - `$action` : Action command (follow / unfollow / block / unblock / approve / deny)
    - `$user` : Target user id

```php
<?php
    // Follow the user with the ID 1574083
    $instagram->modifyRelationship('follow', 1574083);
?>
```

---

Please note that the `modifyRelationship()` method requires the `relationships` [scope](#get-login-url).

---

> [Sample responses of the Relationship Endpoints.](http://instagram.com/developer/endpoints/relationships/)

## Media methods

- `getMedia($id)`
- `searchMedia($lat, $lng, <$distance>, <$minTimestamp>, <$maxTimestamp>)`
    - `$lat` and `$lng` are coordinates and have to be floats like: `48.145441892290336`,`11.568603515625`
    - `$distance` Radial distance in meter (default is 1km = 1000, max. is 5km = 5000)
    - `$minTimestamp` All media returned will be taken *later* than this timestamp (default: 5 days ago)
    - `$maxTimestamp` All media returned will be taken *earlier* than this timestamp (default: now)

> [Sample responses of the Media Endpoints.](http://instagram.com/developer/endpoints/media/)

## Comment methods

- `getMediaComments($id)`
- `addMediaComment($id, $text)`
    - **restricted access:** please email `apidevelopers[at]instagram.com` for access
- `deleteMediaComment($id, $commentID)`
    - the comment must be authored by the authenticated user

---

Please note that the authenticated methods require the `comments` [scope](#get-login-url).

---

> [Sample responses of the Comment Endpoints.](http://instagram.com/developer/endpoints/comments/)

## Tag methods

- `getTag($name)`
- `getTagMedia($name)`
- `searchTags($name)`

> [Sample responses of the Tag Endpoints.](http://instagram.com/developer/endpoints/tags/)

## Likes methods

- `getMediaLikes($id)`
- `likeMedia($id)`
- `deleteLikedMedia($id)`

> How to like a Media: [Example usage](https://gist.github.com/3287237)
> [Sample responses of the Likes Endpoints.](http://instagram.com/developer/endpoints/likes/)

All `<...>` parameters are optional. If the limit is undefined, all available results will be returned.

## Instagram videos

Instagram entries are marked with a `type` attribute (`image` or `video`), that allows you to identify videos.

An example of how to embed Instagram videos by using [Video.js](http://www.videojs.com), can be found in the `/example` folder.

---

**Please note:** Instagram currently doesn't allow to filter videos.

---

## Signed Requests

In order to prevent that your access tokens gets stolen, Instagram recommends to sign your requests with a hash of your API secret, the called endpoint and parameters.

1. Activate ["Enforce Signed Header"](http://instagram.com/developer/clients/manage/) in your Instagram client settings.
2. Enable the signed-requests in your Instagram class:

```php
$instagram->setEnforceSignedRequests(true);
```

## Pagination

Each endpoint has a maximum range of results, so increasing the `limit` parameter above the limit won't help (e.g. `getUserMedia()` has a limit of 90).

That's the point where the "pagination" feature comes into play.
Simply pass an object into the `pagination()` method and receive your next dataset:

```php
<?php
    $photos = $instagram->getTagMedia('kitten');

    $result = $instagram->pagination($photos);
?>
```

Iteration with `do-while` loop.

## Samples for redirect URLs

<table>
  <tr>
    <th>Registered Redirect URI</th>
    <th>Redirect URI sent to /authorize</th>
    <th>Valid?</th>
  </tr>
  <tr>
    <td>http://yourcallback.com/</td>
    <td>http://yourcallback.com/</td>
    <td>yes</td>
  </tr>
  <tr>
    <td>http://yourcallback.com/</td>
    <td>http://yourcallback.com/?this=that</td>
    <td>yes</td>
  </tr>
  <tr>
    <td>http://yourcallback.com/?this=that</td>
    <td>http://yourcallback.com/</td>
    <td>no</td>
  </tr>
  <tr>
    <td>http://yourcallback.com/?this=that</td>
    <td>http://yourcallback.com/?this=that&another=true</td>
    <td>yes</td>
  </tr>
  <tr>
    <td>http://yourcallback.com/?this=that</td>
    <td>http://yourcallback.com/?another=true&this=that</td>
    <td>no</td>
  </tr>
  <tr>
    <td>http://yourcallback.com/callback</td>
    <td>http://yourcallback.com/</td>
    <td>no</td>
  </tr>
  <tr>
    <td>http://yourcallback.com/callback</td>
    <td>http://yourcallback.com/callback/?type=mobile</td>
    <td>yes</td>
  </tr>
</table>

> If you need further information about an endpoint, take a look at the [Instagram API docs](http://instagram.com/developer/authentication/).

## Example App

![Image](http://cl.ly/image/221T1g3w3u2J/preview.png)

This example project, located in the `example/` folder, helps you to get started.
The code is well documented and takes you through all required steps of the OAuth2 process.
Credit for the awesome Instagram icons goes to [Ricardo de Zoete Pro](http://dribbble.com/RZDESIGN).

### More examples and tutorials:

- [User likes](https://gist.github.com/cosenary/3287237)
- [Follow user](https://gist.github.com/cosenary/8322459)
- [User follower](https://gist.github.com/cosenary/7267139)
- [Load more button](https://gist.github.com/cosenary/2975779)
- [User most recent media](https://gist.github.com/cosenary/1711218)
- [Instagram login](https://gist.github.com/cosenary/8803601)
- [Instagram signup (9lessons tutorial)](http://www.9lessons.info/2012/05/login-with-instagram-php.html)

> Let me know if you have to share a code example, too.

# Release notes
You can find [release notes here](https://github.com/Andreyco/Instagram-for-PHP/releases)


# Credits

Copyright (c) 2014 - Andrej Badin
Released under the [BSD License](http://www.opensource.org/licenses/bsd-license.php).

Instagram-PHP-API contains code taken from [Christian Metz's](https://github.com/cosenary) [Instagram-PHP-API](https://github.com/cosenary/Instagram-PHP-API), also licensed under [BSD License](COSENARY).
