<?php
/**
 * Chatter Twitter Api class file 
 *
 * @package Chatter
 */

/**
 * ChatterTwitterApi class
 *
 * @package Chatter
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 2.3.3
 */
class ChatterTwitterApi
{
    /**#@+
     * @var string Twitter API Base url
     */
    const TWITTER_API_URL        = 'https://api.twitter.com/1/';
    const TWITTER_SEARCH_API_URL = 'http://search.twitter.com/';
    const TWITTER_STREAM_API_URL = 'https://stream.twitter.com/1/';
    /**#@-*/

    /**
     * @var mixed Home chatter path
     */
    protected $_configPath;

    /**
     * TwitterOAuth connection object
     * 
     * @var object
     */
    protected $_connection = null;

    /**
     * Whether to log API requests (for debugging)
     *
     * @var bool
     */
    protected $_logApiRequests = false;

    /**
     * Constructor
     *
     * @param object $connection TwitterOAuth connection object
     * @param string $configPath Chatter config path
     * @return void
     */
    public function __construct($connection, $configPath = '', $options = array())
    {
        $this->_connection = $connection;

        if ($this->_configPath == '') {
            if (DIRECTORY_SEPARATOR != "\\") {
                $this->_configPath = $_SERVER['HOME'] . '/.chatter/';
            } else {
                $this->_configPath = $_SERVER['USERPROFILE'] . '\\.chatter\\';
            }
        } else {
            $this->_configPath = $configPath;
        }

        if (isset($options['log'])) {
            $this->_logApiRequests = $options['log'];
        }
    }

    /**
     * Get a timeline
     * 
     * @param string $type Timeline type (home or public)
     * @param array $options Options to pass to API call
     * @return array
     */
    public function getTimeline($type, $options = array())
    {
        switch ($type) {
        case 'user':
            break;
        case 'home':
        default:
            $type = 'home';
            break;
        }

        $url = self::TWITTER_API_URL . 'statuses/' . $type . '_timeline.json';

        $defaultOptions = array(
            'include_entities' => 1,
            'include_rts'      => 1,
        );

        $options = array_merge($defaultOptions, $options);

        $tweets = $this->getHttp($url, true, $options);
        if (is_array($tweets)) {
            $tweets = array_reverse($tweets);
        }

        return $tweets;
    }

    /**
     * Get Home timeline
     *
     * @param array $options Additional options
     * @return void
     */
    public function getHomeTimeline($options = array())
    {
        return $this->getTimeline('home', $options);
    }

    /**
     * Get a user's timeline
     *
     * @param mixed $user The user id or screen name
     * @param array $options Additional options
     * @return array
     */
    public function getUserTimeline($user=null, $options=array())
    {
        $userOptions = array(
            'screen_name' => $user,
        );

        $options = array_merge($options, $userOptions);

        return $this->getTimeline('user', $options);
    }

    /**
     * Get samples from public timeline
     *
     * @return void
     */
    public function getSampleStatuses()
    {
        $url = self::TWITTER_STREAM_API_URL . 'statuses/sample.json';

        $tweets = $this->getHttp($url, true);
        return $tweets;
    }

    /**
     * Get show user
     * 
     * @param string $user Username
     * @return void
     */
    public function getShowUser($user = null)
    {
        $url = self::TWITTER_API_URL . 'users/show.json';

        $options = array(
            'screen_name' => $user,
        );

        $data = $this->getHttp($url, true, $options);
        return $data;
    }

    /**
     * Get a list of friend ids for a given user
     *
     * @param string $screen_name Twitter screen name
     * @return array
     */
    public function getFriendIds($screen_name = null)
    {
        $url = self::TWITTER_API_URL . 'friends/ids.json';

        $options = array(
            'screen_name' => $screen_name,
        );

        $data = $this->getHttp($url, true, $options);
        return $data->ids;
    }

    /**
     * Lookup users
     *
     * @param string|array $user_id User id or array of user ids
     * @return output
     */
    public function lookupUsers($user_id)
    {
        if (!is_array($user_id)) {
            $user_id = array($user_id);
        }

        $url = self::TWITTER_API_URL . 'users/lookup.json';

        $options = array(
            'user_id' => implode(',', $user_id),
        );

        $data = $this->getHttp($url, true, $options);
        return $data;
    }

    /**
     * Get mentions for the authenticated user
     *
     * @param array $options Options
     * @return array
     */
    public function getMentions($options = array())
    {
        $url = self::TWITTER_API_URL . 'statuses/mentions.json';

        $tweets = $this->getHttp($url, true, $options);
        $tweets = array_reverse($tweets);

        return $tweets;
    }

    /**
     * Get request for a raw api command
     * 
     * @param string $command Command
     * @return mixed
     */
    public function rawApiCommand($command)
    {
        $url = self::TWITTER_API_URL . $command;
        echo $url . "\n";

        $data = $this->getHttp($url, true);
        return $data;
    }

    /**
     * Search for tweets
     *
     * @param string $search_term Search term text
     * @param array $options Additional options
     * @return array Array of search results
     */
    public function searchTweets($search_term = '', $options = array())
    {
        $url = self::TWITTER_SEARCH_API_URL . 'search.json';

        $options['q'] = urlencode($search_term);

        $tweets  = array();

        $search = $this->getHttp($url, false, $options);
        if (count($search->results)) {
            $results = array_reverse($search->results);
            foreach ($results as $result) {
                $result->user = new StdClass();

                $result->user->screen_name = $result->from_user;

                $tweets[] = $result;
            }
        }

        return $tweets;
    }

    /**
     * Get trends on twitter
     *
     * @return array
     */
    public function getTrends()
    {
        $url = self::TWITTER_API_URL . 'trends/weekly.json';

        $trends = $this->getHttp($url);
        return $trends;
    }

    /**
     * Update Twitter status
     *
     * @param string $status Status text
     * @param string $inReplyToStatusId An optional status id in reply to
     * @return mixed
     */
    public function updateStatus($status, $inReplyToStatusId = null)
    {
        $url = self::TWITTER_API_URL . 'statuses/update.json';

        $data = array(
            'status' => $status,
        );

        if (null !== $inReplyToStatusId) {
            $data['in_reply_to_status_id'] = $inReplyToStatusId;
        }

        $result = $this->getHttp($url, true, $data, 'POST');
        return $result;
    }

    /**
     * Get Http request
     * 
     * @param mixed $url Url
     * @param bool $auth Auth
     * @param array $params Request parameters
     * @param string $method Request method (default: GET)
     * @return string
     */
    public function getHttp($url, $auth = true, $params = array(),
        $method = 'GET')
    {
        $this->_logApiRequest($url, $method, $params);

        $response = $this->_connection->oAuthRequest($url, $method, $params);

        file_put_contents(
            $this->_configPath . "lasthttp", print_r($response, 1)
        );

        $response = json_decode($response);

        return $response;
    }

    /**
     * Log API request (for debugging)
     *
     * @param string $url URL
     * @param string $method HTTP method
     * @param array $params GET/POST params
     * @return void
     */
    protected function _logApiRequest($url, $method, $params)
    {
        if (!$this->_logApiRequests) {
            return false;
        }

        $paramListing = array();

        foreach ($params as $key => $value) {
            $paramListing[] = $key . '=' . $value;
        }

        $message = date('Y-m-d H:i:s') . ' -- '
            . $method . ' ' . $url . ' ' . implode('&', $paramListing);

        file_put_contents(
            $this->_configPath . "apirequests.log", $message . "\n", FILE_APPEND
        );
    }
}
