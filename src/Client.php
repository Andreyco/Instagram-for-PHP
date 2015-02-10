<?php namespace Andreyco\Instagram;

use Andreyco\Instagram\Exception\AuthException;
use Andreyco\Instagram\Exception\CurlException;
use Andreyco\Instagram\Exception\InvalidParameterException;
use Andreyco\Instagram\Exception\PaginationException;

/**
 * Instagram API class
 * API Documentation: http://instagram.com/developer/
 * Class Documentation: https://github.com/Andreyco/Instagram
 *
 * @author Andrej Badin
 * @since 8.7.2014
 * @copyright Andrej Badin - 2014
 * @version 3.0.0
 * @license BSD http://www.opensource.org/licenses/bsd-license.php
 */
class Client {

    /**
     * The API base URL
     */
    const API_URL = 'https://api.instagram.com/v1/';

    /**
     * The API OAuth URL
     */
    const API_OAUTH_URL = 'https://api.instagram.com/oauth/authorize';

    /**
     * The OAuth token URL
     */
    const API_OAUTH_TOKEN_URL = 'https://api.instagram.com/oauth/access_token';

    /**
     * The Instagram API Key
     *
     * @var string
     */
    private $_apikey;

    /**
     * The Instagram OAuth API secret
     *
     * @var string
     */
    private $_apisecret;

    /**
     * The callback URL
     *
     * @var string
     */
    private $_callbackurl;

    /**
     * The user access token
     *
     * @var string
     */
    private $_accesstoken;

    /**
     * An array of accessTokens of users to make batchCalls
     *
     * @var array
     *
     * Added by @yesh
     */
    private $_accesstokens;

    /**
     * Available scopes
     *
     * @var array
     */
    private $_scope = array();
    private $_defaulScope = array('basic');
    private $_availableScope = array('basic', 'likes', 'comments', 'relationships');

    /**
     * Available actions
     *
     * @var array
     */
    private $_actions = array('follow', 'unfollow', 'block', 'unblock', 'approve', 'deny');


    /**
     * Default constructor
     *
     * @param array|string $config Instagram configuration data
     *
     * @throws Exception\InvalidParameterException
     * @return \Andreyco\Instagram\Client
     */
    public function __construct($config) {
        // if you want to access user data
        if (true === is_array($config)) {
            $this->setApiKey($config['apiKey']);
            $this->setApiSecret($config['apiSecret']);
            $this->setApiCallback($config['apiCallback']);
            $scope = empty($config['scope']) ? $this->_defaulScope : $config['scope'];
            $this->setScope($scope);
            return;
        }

        // if you only want to access public data
        if (true === is_string($config)) {
            $this->setApiKey($config);
            return;
        }

        throw new InvalidParameterException('Error: __construct() -  Invalid configuration data for client.');
    }

    /**
     * Generates the OAuth login URL
     *
     * @param array $scope
     * @param null  $state
     *
     * @internal param $array [optional] $scope       Requesting additional permissions
     * @return string                       Instagram OAuth login URL
     */
    public function getLoginUrl($scope = array(), $state = null) {
        $scope = $this->mergeScope($scope);

        $state = is_string($state) ? "&state={$state}" : '';

        return self::API_OAUTH_URL .
            '?client_id=' . $this->getApiKey() .
            '&redirect_uri=' . urlencode($this->getApiCallback()) .
            '&scope=' . implode('+', $scope) .
            '&response_type=code' .
            $state;
    }

    /**
     * Search for a user
     *
     * @param string $name                  Instagram username
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function searchUser($name, $limit = 0) {
        return $this->_makeCall('users/search', false, array('q' => $name, 'count' => $limit));
    }

    /**
     * Get user info
     *
     * @param integer [optional] $id        Instagram user ID
     * @return mixed
     */
    public function getUser($id = 0) {
        $auth = false;
        if ($id === 0 && isset($this->_accesstoken)) { $id = 'self'; $auth = true; }
        return $this->_makeCall('users/' . $id, $auth);
    }

    /**
     * Get user info
     *
     * @param integer [optional] $id        Instagram user ID
     * @return mixed
     */
    public function getUsers() {
        $auth = false;
        if (isset($this->_accesstokens))
        {
            $id = 'self';
            $auth = true;
        }
        return $this->_makeCalls('users/' . $id, $auth);
    }

    /**
     * Get user activity feed
     *
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getUserFeed($limit = 0) {
        return $this->_makeCall('users/self/feed', true, array('count' => $limit));
    }

    /**
     * Get user recent media
     *
     * @param integer [optional] $id        Instagram user ID
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getUserMedia($id = 'self', $limit = 0) {
        return $this->_makeCall('users/' . $id . '/media/recent', ($id === 'self'), array('count' => $limit));
    }

    /**
     * Get user recent media
     *
     * @param integer [optional] $id        Instagram user ID
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getUserMedias($id = 'self', $limit = 0) {
        return $this->_makeCalls('users/' . $id . '/media/recent', ($id === 'self'), array('count' => $limit));
    }

    /**
     * Get the liked photos of a user
     *
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getUserLikes($limit = 0) {
        return $this->_makeCall('users/self/media/liked', true, array('count' => $limit));
    }


    /**
     * Get the list of users this user follows
     *
     * @param string $id
     * @param int    $limit [optional] $id        Instagram user ID
     *
     * @return mixed
     */
    public function getUserFollows($id = 'self', $limit = 0) {
        return $this->_makeCall('users/' . $id . '/follows', true, array('count' => $limit));
    }


    /**
     * Get the list of users this user is followed by
     *
     * @param string $id
     * @param int    $limit [optional] $id        Instagram user ID
     *
     * @return mixed
     */
    public function getUserFollower($id = 'self', $limit = 0) {
        return $this->_makeCall('users/' . $id . '/followed-by', true, array('count' => $limit));
    }

    /**
     * Get the list of users this user is followed by
     *
     * @param string $id
     * @param int    $limit [optional] $id        Instagram user ID
     *
     * @return mixed
     */
    public function getUserFollowers($id = 'self', $limit = 0) {
        return $this->_makeCalls('users/' . $id . '/followed-by', true, array('count' => $limit));
    }

    /**
     * Get information about a relationship to another user
     *
     * @param integer $id                   Instagram user ID
     * @return mixed
     */
    public function getUserRelationship($id) {
        return $this->_makeCall('users/' . $id . '/relationship', true);
    }


    /**
     * Modify the relationship between the current user and the target user
     *
     * @param string  $action Action command (follow/unfollow/block/unblock/approve/deny)
     * @param integer $user   Target user ID
     *
     * @throws Exception\InvalidParameterException
     * @return mixed
     */
    public function modifyRelationship($action, $user) {
        if (true === in_array($action, $this->_actions) && isset($user)) {
            return $this->_makeCall('users/' . $user . '/relationship', true, array('action' => $action), 'POST');
        }
        throw new InvalidParameterException('Error: modifyRelationship() - This method requires an action command and the target user id.');
    }

    /**
     * Search media by its location
     *
     * @param float $lat                    Latitude of the center search coordinate
     * @param float $lng                    Longitude of the center search coordinate
     * @param integer [optional] $distance  Distance in metres (default is 1km (distance=1000), max. is 5km)
     * @param long [optional] $minTimestamp Media taken later than this timestamp (default: 5 days ago)
     * @param long [optional] $maxTimestamp Media taken earlier than this timestamp (default: now)
     * @return mixed
     */
    public function searchMedia($lat, $lng, $distance = 1000, $minTimestamp = NULL, $maxTimestamp = NULL) {
        return $this->_makeCall('media/search', false, array('lat' => $lat, 'lng' => $lng, 'distance' => $distance, 'min_timestamp' => $minTimestamp, 'max_timestamp' => $maxTimestamp));
    }

    /**
     * Get media by its id
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function getMedia($id) {
        return $this->_makeCall('media/' . $id);
    }

    /**
     * Get the most popular media
     *
     * @return mixed
     */
    public function getPopularMedia() {
        return $this->_makeCall('media/popular');
    }

    /**
     * Search for tags by name
     *
     * @param string $name                  Valid tag name
     * @return mixed
     */
    public function searchTags($name) {
        return $this->_makeCall('tags/search', false, array('q' => $name));
    }

    /**
     * Get info about a tag
     *
     * @param string $name                  Valid tag name
     * @return mixed
     */
    public function getTag($name) {
        return $this->_makeCall('tags/' . $name);
    }

    /**
     * Get a recently tagged media
     *
     * @param string $name                  Valid tag name
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getTagMedia($name, $limit = 0) {
        return $this->_makeCall('tags/' . $name . '/media/recent', false, array('count' => $limit));
    }

    /**
     * Get a list of users who have liked this media
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function getMediaLikes($id) {
        return $this->_makeCall('media/' . $id . '/likes', true);
    }

    /**
     * Get a list of comments for this media
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function getMediaComments($id) {
        return $this->_makeCall('media/' . $id . '/comments', false);
    }

    /**
     * Add a comment on a media
     *
     * @param integer $id                   Instagram media ID
     * @param string $text                  Comment content
     * @return mixed
     */
    public function addMediaComment($id, $text) {
        return $this->_makeCall('media/' . $id . '/comments', true, array('text' => $text), 'POST');
    }

    /**
     * Remove user comment on a media
     *
     * @param integer $id                   Instagram media ID
     * @param string $commentID             User comment ID
     * @return mixed
     */
    public function deleteMediaComment($id, $commentID) {
        return $this->_makeCall('media/' . $id . '/comments/' . $commentID, true, null, 'DELETE');
    }

    /**
     * Set user like on a media
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function likeMedia($id) {
        return $this->_makeCall('media/' . $id . '/likes', true, null, 'POST');
    }

    /**
     * Remove user like on a media
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function deleteLikedMedia($id) {
        return $this->_makeCall('media/' . $id . '/likes', true, null, 'DELETE');
    }

    /**
    * Get information about a location
    *
    * @param integer $id                   Instagram location ID
    * @return mixed
    */
    public function getLocation($id) {
        return $this->_makeCall('locations/' . $id, false);
    }

    /**
     * Get recent media from a given location
     *
     * @param integer $id                   Instagram location ID
     * @return mixed
     */
    public function getLocationMedia($id) {
        return $this->_makeCall('locations/' . $id . '/media/recent', false);
    }

    /**
     * Get recent media from a given location
     *
     * @param float $lat                    Latitude of the center search coordinate
     * @param float $lng                    Longitude of the center search coordinate
     * @param integer [optional] $distance  Distance in meter (max. distance: 5km = 5000)
     * @return mixed
     */
    public function searchLocation($lat, $lng, $distance = 1000) {
        return $this->_makeCall('locations/search', false, array('lat' => $lat, 'lng' => $lng, 'distance' => $distance));
    }

    /**
     * Pagination feature
     *
     * @param object  $obj                  Instagram object returned by a method
     * @param integer $limit                Limit of returned results
     * @return mixed
     */
    public function pagination($obj, $limit = 0) {
        if (true === is_object($obj) && !is_null($obj->pagination)) {
            if (!isset($obj->pagination->next_url)) {
                return;
            }
            $apiCall = explode('?', $obj->pagination->next_url);
            if (count($apiCall) < 2) {
                return;
            }
            $function = str_replace(self::API_URL, '', $apiCall[0]);
            $auth = (strpos($apiCall[1], 'access_token') !== false);
            if (isset($obj->pagination->next_max_id)) {
                return $this->_makeCall($function, $auth, array('max_id' => $obj->pagination->next_max_id, 'count' => $limit));
            } else {
                return $this->_makeCall($function, $auth, array('cursor' => $obj->pagination->next_cursor, 'count' => $limit));
            }
        } else {
            throw new PaginationException("Error: pagination() | This method doesn't support pagination.");
        }
    }

    /**
     * Get the OAuth data of a user by the returned callback code
     *
     * @param string $code                  OAuth2 code variable (after a successful login)
     * @param boolean [optional] $token     If it's true, only the access token will be returned
     * @return mixed
     */
    public function getOAuthToken($code, $token = false) {
        $apiData = array(
            'grant_type'      => 'authorization_code',
            'client_id'       => $this->getApiKey(),
            'client_secret'   => $this->getApiSecret(),
            'redirect_uri'    => $this->getApiCallback(),
            'code'            => $code
        );

        $result = $this->_makeOAuthCall($apiData);
        return (false === $token) ? $result : $result->access_token;
    }


    /**
     * The call operator
     *
     * @param string $function API resource path
     * @param bool   $auth
     * @param        array     [optional] $params      Additional request parameters
     * @param string $method
     *
     * @throws Exception
     * @throws Exception\AuthException
     * @internal param $boolean [optional] $auth      Whether the function requires an access token
     * @internal param $string [optional] $method     Request type GET|POST
     * @return mixed
     */
    protected function _makeCalls($function, $auth = false, $params = null, $method = 'GET') {

        $apiCalls = [];

        foreach ($this->_accesstokens as $accesstoken) {

            if (false === $auth) {
                // if the call doesn't requires authentication
                $authMethod = '?client_id=' . $this->getApiKey();
            } else {
                // if the call needs an authenticated user
                if (true === !empty($accesstoken)) {
                    $authMethod = '?access_token=' . $accesstoken;
                } else {
                    throw new AuthException("Error: _makeCall() | This method requires an valid users access token.");
                }
            }

            if (isset($params) && is_array($params)) {
                $paramString = '&' . http_build_query($params);
            } else {
                $paramString = null;
            }

            $apiCalls[] = self::API_URL . $function . $authMethod . (('GET' === $method) ? $paramString : null);
        }

        $mh = curl_multi_init();

        $x = 0;
        foreach ( $apiCalls as $apiCall ) {

            $$x = curl_init();

            curl_setopt($$x, CURLOPT_URL, $apiCall);
            curl_setopt($$x, CURLOPT_HTTPHEADER, array('Accept: application/json'));
            curl_setopt($$x, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($$x, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($$x, CURLOPT_SSL_VERIFYPEER, false);

            if ('POST' === $method) {
                curl_setopt($$x, CURLOPT_POST, count($params));
                curl_setopt($$x, CURLOPT_POSTFIELDS, ltrim($paramString, '&'));
            } else if ('DELETE' === $method) {
                curl_setopt($$x, CURLOPT_CUSTOMREQUEST, 'DELETE');
            }

            curl_multi_add_handle($mh, $$x);

            $x++;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ( $running );

        ///add each result to an array
        $y    = 0;

        foreach ( $apiCalls as $apiCall) {

            $responses[ $apiCall] = json_decode(
                                        curl_multi_getcontent($$y)
            );

            $y++;
        }

        curl_multi_close($mh);

        if ( empty($responses) ) {
            throw new Exception(
                'There are no responses even though we tried to send requests'
            );
        }

        return $responses;
    }

    /**
     * The call operator
     *
     * @param string $function API resource path
     * @param bool   $auth
     * @param        array     [optional] $params      Additional request parameters
     * @param string $method
     *
     * @throws Exception\CurlException
     * @throws Exception\AuthException
     * @internal param $boolean [optional] $auth      Whether the function requires an access token
     * @internal param $string [optional] $method     Request type GET|POST
     * @return mixed
     */
    protected function _makeCall($function, $auth = false, $params = null, $method = 'GET') {
        if (false === $auth) {
            // if the call doesn't requires authentication
            $authMethod = '?client_id=' . $this->getApiKey();
        } else {
            // if the call needs an authenticated user
            if (true === isset($this->_accesstoken)) {
                $authMethod = '?access_token=' . $this->getAccessToken();
            } else {
                throw new AuthException("Error: _makeCall() | This method requires an valid users access token.");
            }
        }

        if (isset($params) && is_array($params)) {
            $paramString = '&' . http_build_query($params);
        } else {
            $paramString = null;
        }

        $apiCall = self::API_URL . $function . $authMethod . (('GET' === $method) ? $paramString : null);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiCall);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ('POST' === $method) {
            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, ltrim($paramString, '&'));
        } else if ('DELETE' === $method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $jsonData = curl_exec($ch);
        if (false === $jsonData) {
            throw new CurlException('_makeCall() - cURL error: ' . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($jsonData);
    }

    /**
     * The OAuth call operator
     *
     * @param array $apiData The post API data
     * @return mixed
     */
    private function _makeOAuthCall($apiData) {
        $apiHost = self::API_OAUTH_TOKEN_URL;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiHost);
        curl_setopt($ch, CURLOPT_POST, count($apiData));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $jsonData = curl_exec($ch);
        if (false === $jsonData) {
            throw new CurlException('_makeOAuthCall() - cURL error: ' . curl_error($ch));
        }
        curl_close($ch);

        return json_decode($jsonData);
    }

    /**
     * Access Token Setter
     *
     * @param object|string $data
     * @return void
     */
    public function setAccessToken($data) {
        (true === is_object($data)) ? $token = $data->access_token : $token = $data;
        $this->_accesstoken = $token;
    }


    /**
     * Access Token Setter
     *
     * @param $accesstokens
     *
     * @internal param object|string $data
     * @return void
     *
     * Added by @yesh
     */
    public function setAccessTokens($accesstokens) {
        if (is_array($accesstokens)) {
            $this->_accesstokens = $accesstokens;
        };
    }

    /**
     * Access Token Getter
     *
     * @return string
     */
    public function getAccessToken() {
        return $this->_accesstoken;
    }

    /**
     * API-key Setter
     *
     * @param string $apiKey
     * @return void
     */
    public function setApiKey($apiKey) {
        $this->_apikey = $apiKey;
    }

    /**
     * API Key Getter
     *
     * @return string
     */
    public function getApiKey() {
        return $this->_apikey;
    }

    /**
     * API Secret Setter
     *
     * @param string $apiSecret
     * @return void
     */
    public function setApiSecret($apiSecret) {
        $this->_apisecret = $apiSecret;
    }

    /**
     * API Secret Getter
     *
     * @return string
     */
    public function getApiSecret() {
        return $this->_apisecret;
    }

    /**
     * API Callback URL Setter
     *
     * @param string $apiCallback
     * @return void
     */
    public function setApiCallback($apiCallback) {
        $this->_callbackurl = $apiCallback;
    }

    /**
     * API Callback URL Getter
     *
     * @return string
     */
    public function getApiCallback() {
        return $this->_callbackurl;
    }

    /**
     * Permission Scope setter.
     *
     * @param array $scope
     * @return void
     */
    public function setScope(array $scope) {
        $this->_scope = $this->mergeScope($scope);
    }

    /**
     * Merge permission scope with default
     * scope. Allow only valid values.
     *
     * @param array @scope
     */
    private function mergeScope(array $scope) {
        if (empty($scope)) return $this->_scope;
        $scope = array_merge($scope, $this->_defaulScope);
        $scope = array_unique($scope);

        $intersectingScope = array_intersect($scope, $this->_availableScope);

        if (count($intersectingScope) !== count($scope)) {
            throw new InvalidParameterException('Error: mergeScope() - Invalid permission scope parameter used.');
        }

        return $intersectingScope;
    }


    /**
     * Permission Scope getter.
     *
     * @return array
     */
    public function getScope() {
        return empty($this->_scope) ? $this->_defaulScope : $this->_scope;
    }

}
