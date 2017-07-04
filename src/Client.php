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
     * Available scopes
     *
     * @var array
     */
    private $_scope = array();
    private $_defaulScope = array('basic');
    private $_availableScope = array(
      'basic',
      'public_content',
      'follower_list',
      'comments',
      'relationships',
      'likes',
    );

    /**
     * Available actions
     *
     * @var array
     */
    private $_actions = array('follow', 'unfollow', 'block', 'unblock', 'approve', 'deny');

    /**
     * Default constructor
     *
     * @param array|string $config          Instagram configuration data
     * @return void
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
     * @param array [optional] $scope       Requesting additional permissions
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
    public function searchUser($name, $limit = 20) {
        return $this->_makeCall('users/search', array('q' => $name, 'count' => $limit));
    }

    /**
     * Get user info
     *
     * @param integer [optional] $id        Instagram user ID
     * @return mixed
     */
    public function getUser($id = 'self') {
        return $this->_makeCall('users/' . $id);
    }

    /**
     * Get user recent media
     *
     * @param integer [optional] $id        Instagram user ID
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getUserMedia($id = 'self', $limit = 20) {
        return $this->_makeCall('users/' . $id . '/media/recent', array('count' => $limit));
    }

    /**
     * Get the liked photos of a user
     *
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getUserLikes($limit = 20) {
        return $this->_makeCall('users/self/media/liked', array('count' => $limit));
    }

    /**
     * Get the list of users authenticated user follows
     *
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getSelfFollows() {
        return $this->_makeCall('users/self/follows');
    }

    /**
     * Get the list of users authenticated user is followed by
     *
     * @param integer [optional] $id        Instagram user ID
     * @param integer [optional] $limit     Limit of returned results
     * @return mixed
     */
    public function getSelfFollowedBy() {
        return $this->_makeCall('users/self/followed-by');
    }

    /**
     * Get information about a relationship to another user
     *
     * @param integer $id                   Instagram user ID
     * @return mixed
     */
    public function getUserRelationship($id) {
        return $this->_makeCall('users/' . $id . '/relationship');
    }

    /**
     * Modify the relationship between the current user and the target user
     *
     * @param string $action                Action command (follow/unfollow/block/unblock/approve/deny)
     * @param integer $user                 Target user ID
     * @return mixed
     */
    public function modifyRelationship($action, $user) {
        if (true === in_array($action, $this->_actions) && isset($user)) {
            return $this->_makeCall('users/' . $user . '/relationship', array('action' => $action), 'POST');
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
        return $this->_makeCall('media/search', array('lat' => $lat, 'lng' => $lng, 'distance' => $distance, 'min_timestamp' => $minTimestamp, 'max_timestamp' => $maxTimestamp));
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
     * Search for tags by name
     *
     * @param string $name                  Valid tag name
     * @return mixed
     */
    public function searchTags($name) {
        return $this->_makeCall('tags/search', array('q' => $name));
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
    public function getTagMedia($name, $limit = 20) {
        return $this->_makeCall('tags/' . $name . '/media/recent', array('count' => $limit));
    }

    /**
     * Get a list of users who have liked this media
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function getMediaLikes($id) {
        return $this->_makeCall('media/' . $id . '/likes');
    }

    /**
     * Get a list of comments for this media
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function getMediaComments($id) {
        return $this->_makeCall('media/' . $id . '/comments');
    }

    /**
     * Add a comment on a media
     *
     * @param integer $id                   Instagram media ID
     * @param string $text                  Comment content
     * @return mixed
     */
    public function addMediaComment($id, $text) {
        return $this->_makeCall('media/' . $id . '/comments', array('text' => $text), 'POST');
    }

    /**
     * Remove user comment on a media
     *
     * @param integer $id                   Instagram media ID
     * @param string $commentID             User comment ID
     * @return mixed
     */
    public function deleteMediaComment($id, $commentID) {
        return $this->_makeCall('media/' . $id . '/comments/' . $commentID, null, 'DELETE');
    }

    /**
     * Set user like on a media
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function likeMedia($id) {
        return $this->_makeCall('media/' . $id . '/likes', null, 'POST');
    }

    /**
     * Remove user like on a media
     *
     * @param integer $id                   Instagram media ID
     * @return mixed
     */
    public function deleteLikedMedia($id) {
        return $this->_makeCall('media/' . $id . '/likes', null, 'DELETE');
    }

    /**
    * Get information about a location
    *
    * @param integer $id                   Instagram location ID
    * @return mixed
    */
    public function getLocation($id) {
        return $this->_makeCall('locations/' . $id);
    }

    /**
     * Get recent media from a given location
     *
     * @param integer $id                   Instagram location ID
     * @return mixed
     */
    public function getLocationMedia($id) {
        return $this->_makeCall('locations/' . $id . '/media/recent');
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
        return $this->_makeCall('locations/search', array('lat' => $lat, 'lng' => $lng, 'distance' => $distance));
    }

    /**
     * Pagination feature
     *
     * @param object  $obj                  Instagram object returned by a method
     * @param integer $limit                Limit of returned results
     * @return mixed
     */
    public function pagination($obj, $limit = 20) {
        if (true === is_object($obj) && !is_null($obj->pagination)) {
            if (!isset($obj->pagination->next_url)) {
                return;
            }
            $apiCall = explode('?', $obj->pagination->next_url);
            if (count($apiCall) < 2) {
                return;
            }
            $function = str_replace(self::API_URL, '', $apiCall[0]);
            if (isset($obj->pagination->next_max_id)) {
                return $this->_makeCall($function, array('max_id' => $obj->pagination->next_max_id, 'count' => $limit));
            } else {
                return $this->_makeCall($function, array('cursor' => $obj->pagination->next_cursor, 'count' => $limit));
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
     * @param string $function              API resource path
     * @param array [optional] $params      Additional request parameters
     * @param string [optional] $method     Request type GET|POST
     * @return mixed
     */
    protected function _makeCall($function, $params = null, $method = 'GET') {
        if (isset($params['count']) && $params['count'] < 1) {
            throw new InvalidParameterException('InstagramClient: you are trying to query 0 records!');
        }

        // if the call needs an authenticated user
        if (true === isset($this->_accesstoken)) {
            $authMethod = '?access_token=' . $this->getAccessToken();
        } else {
            throw new AuthException("Error: _makeCall() | This method requires an valid users access token.");
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
