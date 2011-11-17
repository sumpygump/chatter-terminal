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
     * Constructor
     *
     * @param object $connection TwitterOAuth connection object
     * @param string $configPath Chatter config path
     * @return void
     */
    public function __construct($connection, $configPath='')
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
        case 'public':
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
     * Get public timeline
     *
     * @param array $options Additional options
     * @return array
     */
    public function getPublicTimeline($options = array())
    {
        return $this->getTimeline('public', $options);
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
        if ($user != null) {
            $url = self::TWITTER_API_URL . 'statuses/user_timeline/'
                . $user . '.json';
        } else {
            $url = self::TWITTER_API_URL . 'statuses/user_timeline.json';
        }

        $tweets = $this->getHttp($url, true, $options);
        if (is_array($tweets)) {
            $tweets = array_reverse($tweets);
        }

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
        if ($user != null) {
            $url = self::TWITTER_API_URL . 'users/show.json'
                . '?screen_name=' . urlencode($user);
        } else {
            $url = self::TWITTER_API_URL . 'users/show.json';
        }

        $data = $this->getHttp($url);
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
    public function searchTweets($search_term='', $options=array())
    {
        $url = self::TWITTER_SEARCH_API_URL . 'search.json';

        $options['q'] = urlencode($search_term);

        $search = $this->getHttp($url, false, $options);
        if (count($search->results)) {
            $results = array_reverse($search->results);
            $tweets  = array();
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
        $response = $this->_connection->oAuthRequest($url, $method, $params);

        file_put_contents(
            $this->_configPath . "lasthttp", print_r($response, 1)
        );

        $response = json_decode($response);

        return $response;
    }
}
