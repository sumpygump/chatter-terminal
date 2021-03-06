<?php
/**
 * Chatter terminal client file
 *
 * @package ChatterTerminal
 */

namespace ChatterTerminal;

use Qi_Console_ArgV;
use Qi_Console_Std;
use Qi_Console_Terminal;
use Qi_Console_TermLetters;

/**
 * Chatter: Twitter shell client class
 *
 * @package Chatter
 * @author Jansen Price <jansen.price@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @version 2.5
 */
class Client
{
    /**
     * Chatter Terminal client version
     * 
     * @var string
     */
    protected $_version = '2.5';

    /**
     * The terminal object
     *
     * @var object Terminal
     */
    protected $_terminal;

    /**
     * Storage for the Twitter APi object
     *
     * @var mixed
     */
    protected $_twitterApi;

    /**
     * The arguments object
     *
     * @var object ArgV
     */
    protected $_args;

    /**
     * Home chatter path
     *
     * @var mixed
     */
    protected $_configPath;

    /**
     * Options for runtime
     *
     * @var mixed
     */
    protected $_options;

    /**
     * Flag to indicate no data was returned
     *
     * @var bool
     */
    protected $_noData = false;

    /**
     * Consumer key
     * 
     * @var string
     */
    protected $_consumerKey = 'aD7xakeNjk6xdjA9WW5jkg';

    /**
     * Consumer secret
     * 
     * @var string
     */
    protected $_consumerSecret = '0cP0OYdYWP8g1UdUoPFK7Hd8tvhqaJU1V9yGAXttNKA';

    /**
     * Access token
     * 
     * @var array
     */
    protected $_accessToken = array();

    /**
     * Storage for command history
     * 
     * @var array
     */
    protected $_commandHistory = array();

    /**
     * Storage for command history pointer
     * 
     * @var float
     */
    protected $_commandPtr = 0;

    /**
     * Storage of most recent tweets retrieved
     * 
     * @var array
     */
    protected $_savedTweets = array();

    /**
     * TermLetters object
     * 
     * @var object
     */
    protected $_termLetters = null;

    /**
     * Constructor
     *
     * @param object $argv ArgV object
     * @return void
     */
    public function __construct($argv)
    {
        $this->_terminal = new Qi_Console_Terminal();

        $this->_args = new Qi_Console_ArgV(
            $argv,
            array(
                'arg:action' => 'home',
                'arg:param1' => '',
                'a'          => 'all',
                'b'          => 'bigletters',
                'c'          => 'continuous',
                'e'          => 'espeak', //experimental
                'f'          => 'figlet',
                'h'          => 'help',
                'l'          => 'logapi',
                'i'          => 'interactive',
                't'          => 'time',
                'include4sq' => false,
            )
        );

        // Skip to help and exit
        if ($this->_args->help == true) {
            $this->showHelp();
            exit(0);
        }

        $this->_check_environment();

        $this->_options = array();

        if (DIRECTORY_SEPARATOR != "\\") {
            $this->_configPath = $_SERVER['HOME'] . '/.chatter/';
        } else {
            $this->_configPath = $_SERVER['USERPROFILE'] . '\\.chatter\\';
        }

        // Load access token or begin authentication process
        $this->_loadAuth();

        // Set whether to show all (last 20) or since last call
        if ($this->_args->all == true) {
            $this->_options['all'] = true;
        } else {
            $this->_options['all'] = false;
        }

        $apiOptions = array(
            'log' => $this->_args->logapi,
        );

        // Set up twitter api object
        $this->_twitterApi = new TwitterApi(
            $this->getConnection(), $this->_configPath, $apiOptions
        );

        if ($this->_args->bigletters) {
            $options = array(
                'terminal' => $this->_terminal,
            );
            $this->_termLetters = new Qi_Console_TermLetters($options);
        }

        // interactive mode
        if ($this->_args->interactive && $this->_terminal->isatty()) {
            $this->_noData = true; // reset noData flag

            $this->_executeInteractiveMode();
        } else {
            $this->execute();
        }

        if ($this->_args->continuous) {
            $this->_continuous();
        }

        $this->_safe_exit(0);
    }

    /**
     * Execute an action
     *
     * @param array $additional_params Parameters
     * @return void
     */
    public function execute($additional_params = array())
    {
        // Handle actions
        switch ($this->_args->action) {
        case 'pb':
        case 'public':
            $this->showPublicTimeline();
            break;
        case 's':
        case 'search':
            if ($this->_args->param1 == '') {
                $this->_halt('Missing required search parameter');
                return false;
            }
            $this->showSearch($this->_args->param1, $additional_params);
            break;
        case 'trends':
            $this->showTrends();
            break;
        case 'user':
            $this->showUserTimeline($this->_args->param1, $additional_params);
            break;
        case 'userinfo':
            if ($this->_args->param1 == '') {
                $this->_halt('Userinfo requires a screen name');
                return false;
            }
            $this->showUserInfo($this->_args->param1);
            break;
        case 'mn':
        case 'mentions':
            $this->showMentions($additional_params);
            break;
        case 'h':
        case 'help':
            $this->showHelp();
            break;
        case 'up':
        case 'update':
            if ($this->_args->param1 == '') {
                $status = $this->_promptStatus();
            } else {
                if ($this->_args->__arg3) {
                    // Their status isn't quoted.
                    $args   = $this->_args->getArgs();
                    $status = implode(' ', array_slice($args, 1));
                } else {
                    // Note: _args->param1 is the same as _args->__arg2
                    $status = $this->_args->param1;
                }
            }
            $this->updateStatus($status);
            break;
        case 'reply':
            $internalId = $this->_args->param1;

            if ($internalId == '') {
                $this->_halt('Missing required tweet id.');
                return false;
            }

            if (!array_key_exists($internalId, $this->_savedTweets)) {
                $this->_halt("No tweet with id $internalId.");
                return false;
            }

            $tweet = $this->_savedTweets[$internalId];

            echo "Replying to ";
            $this->displayTweets(array($tweet), false, '', false);

            $status = $this->_promptStatus();

            $r = $this->updateStatus($status, $tweet->id_str);
            if ($r) {
                echo "Replied to tweet status id " . $tweet->id_str . "\n";
            }
            break;
        case 'detail':
            $internalId = $this->_args->param1;

            if ($internalId == '') {
                $this->_halt('Missing required tweet id.');
                return false;
            }

            if (!array_key_exists($internalId, $this->_savedTweets)) {
                $this->_halt("No tweet with id $internalId.");
                return false;
            }

            $tweet = $this->_savedTweets[$internalId];

            $this->showDetailedTweet($tweet);
            break;
        case 'repeat':
            if (empty($this->_savedTweets)) {
                $this->_halt('No recent tweets to display.');
                return false;
            }
            $this->displayTweets($this->_savedTweets);
            break;
        case 'showfriends':
            $friendIds = $this->_twitterApi->getFriendIds();
            $firstFriendIds = array_slice($friendIds, 0, 100);
            $friends = $this->_twitterApi->lookupUsers($firstFriendIds);
            $this->showFriends($friends);
            break;
        case 'raw':
            $r = $this->_twitterApi->rawApiCommand($this->_args->param1);
            print_r($r);
            break;
        case 'fr':
        case 'friends':
            $this->showTimeline('friends', $additional_params);
            break;
        case 'n':
        case 'notify':
            $this->notifyHomeTimeline();
            break;
        case 'all':
            $this->_args->all      = true;
            $this->_options['all'] = true;
            // pass through
        case 'home':
            // pass through
        default:
            if ($this->_args->interactive == true
                && ($this->_args->action != 'home' 
                && $this->_args->action != 'all')
            ) {
                $this->_halt("Invalid command '" . $this->_args->action . "'");
                return false;
            }
            $this->showTimeline('home', $additional_params);
            break;
        }
    }

    /**
     * Initialize interactive mode
     *
     * Process commands from a prompt
     * 
     * @return void
     */
    protected function _executeInteractiveMode()
    {
        $options = array('page' => 1);
        $input   = '';

        while ($input != 'q') {
            // Get input
            $input = $this->_getInput();

            $this->_commandHistory[] = $input;

            $this->_commandPtr = count($this->_commandHistory);

            switch(trim($input)) {
            case 'q':
            case 'exit':
            case 'quit':
                $this->_safe_exit(0);
                break;
            case 'clear':
                $this->_terminal->clear();
                break;
            case '':
                if ($this->_noData) {
                    // reset to page 1
                    $options['page'] = 1;
                    $this->_terminal->cuu1();
                    echo $this->_args->action . "\n";
                } else {
                    // just pressing enter will increase the page #
                    $options['page']++;
                    $this->_terminal->cuu1();
                    echo "Page " . $options['page'] . "\n";
                }
                $this->execute($options);
                break;
            default:
                $args = preg_split(
                    "/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|"
                    . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/",
                    trim($input), 0,
                    PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE
                );

                $this->_args->action = trim($args[0]);

                if (isset($args[1])) {
                    if ($args[0] == 'update' && isset($args[2])) {
                        $status = implode(' ', array_slice($args, 1));

                        $this->_args->param1 = $status;
                    } else {
                        $this->_args->param1 = trim($args[1]);
                    }
                } else {
                    $this->_args->param1 = '';
                }

                $options['page'] = 1; // reset to page 1

                $this->execute();

                $this->_reset_tty();
                break;
            }
        }
    }

    /**
     * Get input
     * 
     * If windows, it is just a simple prompt, otherwise there are features
     * such as command history
     * 
     * @return void
     */
    protected function _getInput()
    {
        if (DIRECTORY_SEPARATOR == "\\") {
            // windows OS
            return $this->_terminal->prompt('>');
        }

        // Enter cbreak and no echo tty mode
        shell_exec('stty -icanon -echo min 1 time 0');

        $input      = '';
        $esc_time   = 0.0;
        $esc_buffer = '';
        $pos        = 0;

        // prompt
        echo "> ";

        while (true) {
            $char = fgetc(STDIN);
            //echo " " . round(microtime(true), 2) . "\n " . ord($char);
            if (round(microtime(true), 2) == $esc_time) {
                $esc_buffer .= $char;
                switch ($esc_buffer) {
                case '[A':  // up arrow
                    if ($this->_commandPtr > 0) {
                        $this->_commandPtr--;
                        if (strlen($input)) {
                            $this->_terminal->cub(strlen($input));
                        }
                        $this->_terminal->el();
                        $input = $this->_commandHistory[$this->_commandPtr];
                        echo $input;
                    }
                    $esc_buffer = '';
                    break;
                case '[B':  // down arrow
                    $commandCount = count($this->_commandHistory);
                    if ($this->_commandPtr < $commandCount - 1) {
                        $this->_commandPtr++;
                        if (strlen($input)) {
                            $this->_terminal->cub(strlen($input));
                        }
                        $this->_terminal->el();
                        $input = $this->_commandHistory[$this->_commandPtr];
                        echo $input;
                    } else {
                        $this->_commandPtr = $commandCount;
                        if (strlen($input)) {
                            $this->_terminal->cub(strlen($input));
                        }
                        $this->_terminal->el();
                        $input = '';
                    }
                    $esc_buffer = '';
                    break;
                case '[C':  // right arrow
                    $this->_terminal->cuf1();
                    $esc_buffer = '';
                    break;
                case '[D':  // left arrow
                    $this->_terminal->cub(1);
                    $esc_buffer = '';
                    break;
                default:
                    //echo $esc_buffer . ' ';
                    break;
                }

                continue; // continue while loop
            }

            if ($char == "\n") {
                echo "\n";
                break;
            } else if (ord($char) === 127) {
                // backspace
                if (strlen($input) >= 0) {
                    $this->_terminal->cub(1);
                    $this->_terminal->dch1();
                    $input = substr($input, 0, -1);
                }
            } else {
                if (ord($char) >= 32 && ord($char) <= 126) {
                    // printable characters
                    echo $char;
                    $pos++;
                    $input .= $char;
                } else {
                    // If an escape character is detected, enter esc buffer mode
                    // Any chars received within 1/100ths of a second
                    // will be handled as a termcap
                    if (ord($char) == 27) {
                        $esc_time = round(microtime(true), 2);
                    }
                }
            }
        }

        return $input;
    }

    /**
     * Loop and continuously run
     * 
     * @return void
     */
    protected function _continuous()
    {
        echo "Entering continuous mode, press ctrl+c to exit.\n";

        // User would hit ctrl+c to exit
        while (true) {
            $this->execute();
            sleep(120);
        }
    }

    /**
     * Prompt for a status update
     *
     * @return string
     */
    protected function _promptStatus()
    {
        echo "|---------|---------|---------|---------|---------|---------|"
            . "---------|---------|---------|---------|---------|---------|"
            . "---------|---------|";
        echo "\n";
        $this->_reset_tty();
        return $this->_terminal->prompt(':');
    }

    /**
     * Show friends timeline
     *
     * @param string $type Timeline type
     * @param array $options Timeline options
     * @return void
     */
    public function showTimeline($type = 'home', $options = array())
    {
        if ($this->_options['all'] == false) {
            $lastid = $this->_getLastId();
            if ($lastid != false) {
                $options['since_id'] = $lastid;
            }
        }

        switch ($type) {
        case 'friends':
            //pass through
        case 'home':
            //pass through
        default:
            $tweets = $this->_twitterApi->getHomeTimeline($options);
            break;
        }

        if (!isset($options['page']) || $options['page'] < 2) {
            $this->_saveLastId($tweets);
        }

        if (isset($options['page']) && $options['page'] > 1) {
            $show_date = true;
        } else {
            $show_date = false;
        }

        $this->saveTweets($tweets);

        $this->displayTweets($tweets, $show_date, 'No new tweets.');
    }

    /**
     * Save tweets to index, map and use for replies
     * 
     * @param array $tweets Tweets array
     * @return void
     */
    public function saveTweets($tweets)
    {
        $this->_savedTweets = $tweets;
    }

    /**
     * Show public timeline
     *
     * @param array $options Additional options
     * @return void
     */
    public function showPublicTimeline()
    {
        // This doesn't work until a solution for handling the Twitter stream 
        // API is discovered.
        // Potential: http://stackoverflow.com/questions/1342583/php-manipulate-a-string-that-that-is-30-mil-chars-long/1342760#1342760
        $this->_halt('Public timeline deprecated');
        return false;

        $tweets = $this->_twitterApi->getSampleStatuses();
        $this->displayTweets($tweets);
    }

    /**
     * Show timeline for a user
     *
     * @param mixed $user The username
     * @param array $options Additional options
     * @return void
     */
    public function showUserTimeline($user=null, $options=array())
    {
        if ($this->_args->continuous) {
            // If we're in continuous mode we should only show since the last 
            // set of tweets
            $lastid = $this->_getSavedTweetsLastId();
            if ($lastid != false) {
                $options['since_id'] = $lastid;
            }
        }

        $tweets = $this->_twitterApi->getUserTimeline($user, $options);

        if (!empty($tweets)) {
            $this->saveTweets($tweets);
        }

        if (isset($tweets->error)) {
            $this->_halt("Error: " . $tweets->error);
            return false;
        }

        $this->displayTweets($tweets, true);
    }

    /**
     * Show detailed information of a single tweet
     * 
     * @param mixed $tweet Tweet object
     * @return void
     */
    public function showDetailedTweet($tweet)
    {
        $name = '@' . $tweet->user->screen_name;
        $text = $this->sanitizeTweet($tweet->text);

        $date_format = 'm/d/Y H:i:s';

        if ($this->_args->time) {
            $time = date($date_format, strtotime(trim($tweet->created_at)));
        } else {
            $time = Datelib::humanRelativeDate(
                strtotime(trim($tweet->created_at)), false, ''
            );
        }

        if ($tweet->source) {
            $sourceParts = self::_getSourceElements($tweet->source);
            $source = 'via ' . $sourceParts['name'];
            if ($sourceParts['url'] != '') {
                $source .= ' (' . $sourceParts['url'] . ')';
            }
        }

        if (isset($tweet->user->name) && $name != $tweet->user->name) {
            $name .= " (" . $tweet->user->name . ")";
        }

        if (isset($tweet->user->profile_image_url)) {
            $this->_display_image($this->getProfileImage($tweet->user));
            echo "\n";
        }

        $this->_terminal->setaf(2);
        echo $name . "\n";
        $this->_terminal->op();
        echo $this->highlightTweetElements($text) . "\n";

        $this->_terminal->setaf(4);
        echo $time;

        if ($source) {
            $this->_terminal->op();
            echo " " . $source;
        }

        $this->_terminal->op();
        echo "\n";
    }

    /**
     * Show user info
     * 
     * @param string $user Screen name
     * @return void
     */
    public function showUserInfo($user)
    {
        $data = $this->_twitterApi->getShowUser($user);

        if (isset($data->error)) {
            $this->_halt("Error: " . $data->error);
            return false;
        }

        $name = $data->screen_name;

        if (isset($data->name) && $data->screen_name != $data->name) {
            $name .= " (" . $data->name . ")";
        }

        echo "\n";
        $this->_display_image($this->getProfileImage($data));

        $this->_terminal->setaf(2);
        echo "\nName: " . $name . "\n";
        echo "------" . str_repeat('-', strlen($name)) . "\n";

        $this->_terminal->setaf(3);
        echo $data->description . "\n";
        echo $data->url . "\n";
        echo "Profile Image: " . $data->profile_image_url . "\n";
        echo "Location: " . $data->location
            . " -- Timezone: " . $data->time_zone . "\n\n";

        $this->_terminal->setaf(4);
        echo "Tweets:    " . self::_padNumber($data->statuses_count) . "\n";
        echo "Following: " . self::_padNumber($data->friends_count) . "\n";
        echo "Followers: " . self::_padNumber($data->followers_count) . "\n";
        echo "Listed:    " . self::_padNumber($data->listed_count) . "\n";

        if ($data->following) {
            $this->_terminal->setaf(2);
            echo "\nYou follow " . $data->screen_name;
        }

        echo "\n";
        $this->_terminal->op();
    }

    /**
     * Pad a number
     * 
     * @param mixed $number Number
     * @param int $pad Number of chars to pad
     * @param mixed $type Pad left or right
     * @return string
     */
    protected static function _padNumber($number, $pad = 10,
        $type = STR_PAD_LEFT)
    {
        return str_pad(number_format($number), $pad, ' ', $type);
    }

    /**
     * Show mentions of the authenticated user
     *
     * @param array $options Additional options to use
     * @return void
     */
    public function showMentions($options=array())
    {
        $tweets = $this->_twitterApi->getMentions($options);
        $this->saveTweets($tweets);
        $this->displayTweets($tweets, true);
    }

    /**
     * Search for tweets
     *
     * @param mixed $search_term Search term
     * @param array $options Additional options to use
     * @return void
     */
    public function showSearch($search_term, $options=array())
    {
        $tweets = $this->_twitterApi->searchTweets($search_term, $options);
        $this->saveTweets($tweets);
        $this->displayTweets($tweets, true);
    }

    /**
     * Show trends
     *
     * @return void
     */
    public function showTrends()
    {
        $trendsData = $this->_twitterApi->getTrends();
        $today = date('Y-m-d');

        $trendsData = $trendsData[0];
        
        $this->_terminal->setaf(4);
        echo "Trends for: " . $trendsData->as_of . "\n";

        $this->_terminal->setaf(3);

        $i = 0;
        foreach ($trendsData->trends as $trend) {
            echo ++$i . ". " . $trend->name . "\n";
        }

        $this->_terminal->op();
    }

    /**
     * Update twitter status
     *
     * @param string $status Status
     * @return void
     */
    public function updateStatus($status, $inReplyToStatusId = null)
    {
        if (trim($status) == '') {
            return false;
        }

        $r = $this->_twitterApi->updateStatus($status, $inReplyToStatusId);
        return true;
    }

    /**
     * Sanitize a tweet text
     *
     * @param mixed $text Text of a tweet
     * @return string
     */
    public function sanitizeTweet($text)
    {
        $out = trim($text);

        $from = array(
            "\r", "\n\n", '&gt;', '&lt;', '&#39;', '&amp;', '&quot;',
            chr(hexdec('E2')) . chr(hexdec('80')) . chr(hexdec('99')),
            chr(hexdec('E2')) . chr(hexdec('80')) . chr(hexdec('9C')),
            chr(hexdec('E2')) . chr(hexdec('80')) . chr(hexdec('9D')),
            chr(hexdec('E2')) . chr(hexdec('80')) . chr(hexdec('94')),
            chr(hexdec('E2')) . chr(hexdec('80')) . chr(hexdec('93')),
            chr(hexdec('E2')) . chr(hexdec('80')) . chr(hexdec('98')),
            chr(hexdec('E2')) . chr(hexdec('80')) . chr(hexdec('A6')),
        );

        $to = array(
            ' ', ' ', '>', '<', "'", '&', '"',
            "'",
            '"',
            '"',
            '--',
            '-',
            "'",
            "...",
        );

        $out = str_replace($from, $to, $out);

        return ($out);
    }

    /**
     * Display tweets
     *
     * @param mixed $tweets Array of tweets
     * @param bool $show_date Whether to show the date
     * @param string $no_data_message Message to show when there are no messages
     * @return void
     */
    public function displayTweets($tweets, $show_date = false,
        $no_data_message = 'No tweets', $showInternalIds = true)
    {
        $display_tweets = array();
        $longest_name   = 0;
        $longest_time   = 0;

        if ($show_date) {
            $date_format = 'm/d/Y H:i:s';
        } else {
            $date_format = 'H:i:s';
        }

        if ($tweets === null) {
            $this->_terminal->setaf(3);
            if (!$this->_args->continuous) {
                echo $no_data_message . "\n";
            }
            $this->_noData = true;
            $this->_terminal->op();
            return;
        } else {
            $this->_noData = false;
        }

        if (isset($tweets->errors)) {
            $errorMessage = "Error\n";
            foreach ($tweets->errors as $error) {
                $errorMessage .= $error->message . "\n";
            }

            $this->_halt($errorMessage);
        }

        foreach ($tweets as $id => $tweet) {
            if (!$this->_args->include4sq
                && strpos($tweet->text, '4sq.com')
            ) {
                continue;
            }
            $name = trim($tweet->user->screen_name);

            if ($this->_args->time) {
                $time = date($date_format, strtotime(trim($tweet->created_at)));
            } else {
                $time = Datelib::humanRelativeDate(
                    strtotime(trim($tweet->created_at)), false, ''
                );
            }

            $add_tweet['screen_name'] = $name;
            $add_tweet['created_at']  = $time;
            $add_tweet['text']        = $this->sanitizeTweet($tweet->text);

            if (isset($tweet->user->profile_image_url)) {
                $add_tweet['profile_image_url'] = $tweet->user->profile_image_url;
            } else {
                $add_tweet['profile_image_url'] = '';
            }

            if (strlen($name) > $longest_name) {
                $longest_name = strlen($name);
            }

            if (strlen($time) > $longest_time) {
                $longest_time = strlen($time);
            }

            $display_tweets[$id] = $add_tweet;
        }

        // Manually force the terminal as NOT a tty
        if ($this->_args->figlet || $this->_args->bigletters) {
            $this->_terminal->setIsatty(false);
        }

        foreach ($display_tweets as $id => $tweet) {
            $text = '';

            if ($showInternalIds) {
                $text .= str_pad($id, 2, ' ', STR_PAD_LEFT) . " ";
            }

            $text .= $this->_doForegroundColor(4);
            $text .= str_pad($tweet['created_at'], $longest_time) . " ";

            $text .= $this->tweep($tweet['screen_name']) . " ";

            $text .= $this->_terminal->do_sgr0();
            $text .= $this->_doOriginalPair();
            $text .= $this->highlightTweetElements($tweet['text']) . "\n";

            if ($this->_args->figlet) {
                $cmd = 'figlet "' . $text . '"';
                echo $text;
                passthru('figlet -t "' . trim($text) . '"');
            } elseif ($this->_args->bigletters) {
                $this->_terminal->setIsatty();
                $this->_display_image($tweet['profile_image_url']);
                $this->_termLetters->techo($text);
                $this->_terminal->setIsatty(false);
            } else {
                echo $text;
            }
        }

        // return to default terminal isatty setting
        if ($this->_args->figlet || $this->_args->bigletters) {
            $this->_terminal->setIsatty();
        }

        $this->_terminal->op();
    }

    /**
     * Highlight hashtags and nicknames in tweet texts
     * 
     * @param string $text Text
     * @return string
     */
    public function highlightTweetElements($text)
    {
        $bold   = $this->_terminal->do_bold();
        $unbold = $this->_terminal->do_sgr0();

        // hashtags
        if (strpos($text, "#") !== false) {
            $text = preg_replace(
                "/(#[a-zA-Z0-9\-_]+)/", $bold . "$1" . $unbold, $text
            );
        }

        $em   = $this->_doForegroundColor(3);
        $unem = $this->_doOriginalPair();

        if ($this->_args->bigletters) {
            // Need to prepend with backslashes for values being passed to 
            // preg_replace
            $em = "\\" . $em;
            $unem = "\\" . $unem;
        }

        // usernames
        if (strpos($text, "@") !== false) {
            $text = preg_replace(
                "/(@[a-zA-Z0-9_]+)/", $em . "$1" . $unem, $text
            ); 
        }

        return $text;
    }

    /**
     * Format a twitter username with < and > and colors
     * 
     * @param string $name Twitter username
     * @return string
     */
    public function tweep($name)
    {
        if ($this->_args->bigletters) {
            $delimiterAttr = $this->_doForegroundColor(2);
        } else {
            $delimiterAttr = $this->_terminal->do_bold()
                . $this->_terminal->do_setaf(0);
        }

        $nameAttr = $this->_terminal->do_sgr0()
            . $this->_doForegroundColor(2);

        return $delimiterAttr . "<" . $nameAttr . $name . $delimiterAttr . ">";
    }

    /**
     * Show friends
     * 
     * @param mixed $friends Friends data
     * @return void
     */
    public function showFriends($friends)
    {
        $displayFriends = array();
        $longestName    = 0;

        foreach ($friends as $friend) {
            $screenName = trim($friend->screen_name);
            $realName   = trim($friend->name);

            $name = $screenName;

            if ($screenName != $realName) {
                $name .= " (" . $realName . ")";
            }

            $data = array(
                'name' => $name,
                'status' => '',
            );

            if (strlen($name) > $longestName) {
                $longestName = strlen($name);
            }

            if (isset($friend->status)) {
                $data['status']     = $friend->status->text;
                $data['created_at'] = date(
                    'm/d/Y H:i:s', strtotime($friend->status->created_at)
                );
            }

            $displayFriends[$name] = $data;
        }

        ksort($displayFriends);

        foreach ($displayFriends as $name => $data) {
            echo $this->tweep($name);
            // This doesn't work when the name is formatted.
            //echo str_pad($this->tweep($name), $longestName);

            if ($data['status']) {
                $status = $this->sanitizeTweet($data['status']);
                $this->_terminal->sgr0()->op();
                echo " " . $this->highlightTweetElements($status);

                $this->_terminal->setaf(4);
                echo " " . $data['created_at'];
            }
            echo "\n";
        }
        $this->_terminal->op();
    }

    /**
     * Show a notification window of latest tweets
     * 
     * Alternate use of the program. This can be called in a cron
     * to show growl type notifications of tweets
     * 
     * @return void
     */
    public function notifyHomeTimeline()
    {
        $lastid  = $this->_getLastId();
        $options = array(
            'since_id' => $lastid,
        );

        $tweets = $this->_twitterApi->getHomeTimeline($options);

        echo date('Y-m-d H:i:s') . " --- ";

        if (!$tweets || empty($tweets)) {
            echo "No new tweets\n";
            return false;
        }

        echo count($tweets) . " tweets\n";

        $this->_saveLastId($tweets);
        array_reverse($tweets);

        foreach ($tweets as $tweet) {
            if (!$this->_args->include4sq
                && strpos($tweet->text, '4sq.com')
            ) {
                continue;
            }
            
            $this->notifyTweet($tweet);
        }
    }

    /**
     * Notify an individual tweet
     * 
     * @param array $tweet Tweet
     * @return void
     */
    public function notifyTweet($tweet)
    {
        $body    = $tweet->text;
        $summary = $tweet->user->screen_name;

        $time = date('H:ia', strtotime(trim($tweet->created_at)));

        $body = $time . ",\n" . $body;

        $imageFile = $this->getProfileImage($tweet->user);

        $cmd = 'notify-send'
            . ' -i ' . $imageFile
            . " \"$summary\""
            . " \"" . self::_escapeArg($body) . "\"";

        echo $cmd . "\n";

        passthru($cmd);

        if ($this->_args->espeak == true) {
            $body = self::_replaceEmoticons($body);
            $body = self::_replaceShortenedUrls($body);
            $cmd = 'espeak --punct=\'!/()\' "Tweet from ' . $summary . ': ' . self::_escapeArg($body, "\"", false) . '"';

            // Redirect errors to not display
            $cmd .= ' > /dev/null 2>&1';

            echo $cmd . "\n";
            passthru($cmd);
        }
    }

    /**
     * Escape characters in a shell argument
     * 
     * @param string $text Text to clean up
     * @return string
     */
    protected static function _escapeArg($text, $quote='"', $escapeBackslash=true)
    {
        if (substr($text, 0, 1) == '-') {
            $text = "\\" . $text;
        }

        if ($escapeBackslash) {
            $text = str_replace("!", '\!', $text);
        }
        $text = str_replace($quote, "\\" . $quote, $text);

        return $text;
    }

    /**
     * Replace emoticons with words
     *
     * @param string $text Text input containing emoticons
     * @return string
     */
    protected static function _replaceEmoticons($text)
    {
        $text = str_replace(':)', ', smiley face,', $text);
        $text = str_replace(':(', ', frowney face,', $text);
        $text = str_replace(':D', ', happy face,', $text);

        return $text;
    }

    /**
     * Replace URL references (t.co) with words
     *
     * When espeak reads the URLs verbatim, it's annoying
     *
     * @param string $text Text input containing href
     * @return string
     */
    protected static function _replaceShortenedUrls($text)
    {
        $text = preg_replace('#http://t.co/([A-Za-z0-9]+)#', ',Hyperlink to T dot co', $text);

        return $text;
    }

    /**
     * Get Profile image
     *
     * Save to cache and return a local full path
     * 
     * @param string $user Tweep information
     * @return string Local fullpath to image
     */
    public function getProfileImage($user)
    {
        $url       = $user->profile_image_url;
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        if (!$url) {
            return '';
        }

        $filename = $user->screen_name . "-" . md5($url) . '.' . $extension;

        $path = $this->_configPath . "profile-img-cache" . DIRECTORY_SEPARATOR;
        if (!file_exists($path)) {
            mkdir($path);
        }

        $file = $path . $filename;
        if (!file_exists($file)) {
            $image = file_get_contents($url);
            file_put_contents($file, $image);
        }

        return $file;
    }

    /**
     * Show the Chatter ascii art logo
     * 
     * @return void
     */
    public function showChatterAsciiArt()
    {
        $this->_terminal->setaf(2);
        echo "   ____  _   _  _____  ___________  _____  _____\n";
        echo "  / ___|| | | ||     ||_   ___   _||     ||     |\n";
        echo " | |    | |_| || (X) |  | |   | |  |  ---|| (X)_|\n";
        echo " | |___ |  _  ||  _  |  | |   | |  |  ---||  _ \ \n";
        echo "  \____||_| |_||_| |_|  |_|   |_|  |_____||_| |_|\n\n";
        echo "          Chatter Terminal Twitter Client\n";
        echo "                   version " . $this->_version . "\n\n";
        $this->_terminal->op();
    }

    /**
     * Show help
     *
     * @return void
     */
    public function showHelp()
    {
        $this->showChatterAsciiArt();
        $this->_terminal->setaf(4);

        //@codingStandardsIgnoreStart
        echo "Usage:\n";
        echo "  chatter [flags] [command] [arguments]\n";
        echo "\n";
        echo "Flags:\n"
            . "  -a --all         : Only for 'home' command. Instead of showing tweets since\n"
            . "                     last check, show all tweets from friends timeline.\n"
            . "  -b --bigletters  : Use big letters made up of unicode blocks.\n"
            . "  -c --continuous  : Continuously show tweets as they come in (checks every 1 minute).\n"
            . "  -e --espeak      : (experimental) use espeak to speak the tweets. Only works with notify command.\n"
            . "  -f --figlet      : Use figlet to display tweets.\n"
            . "  -i --interactive : Enter interactive mode. Commands can be entered from a prompt.\n"
            . "  -t --time        : Show actual date and time instead of human relative time.\n"
            . "     --include4sq  : Don't block 4sq.com tweets from timeline.\n";
        echo "\nCommands:\n"
            . "  help                   : This help.\n"
            . "  home                   : Show your home timeline <since last check>.\n"
            . "  mentions               : Show tweets mentioning you.\n"
            . "  notify                 : Notify of any new tweets using libnotify (ignores --all parameter).\n"
            . "  public                 : Show public timeline (DEPRECATED - August 2012).\n"
            . "  search <term>          : Search for term in tweets.\n"
            . "  trends                 : Show list of top 30 trends on Twitter.\n"
            . "  update [status]        : Update your status to [status]. Without argument presents a prompt with character meter.\n"
            . "  userinfo <screen_name> : Show info about user <screen_name>.\n"
            . "  user [screen_name]     : Show tweets for user [screen_name]. Default is you.\n"
            ;

        echo "\nInteractive only commands:\n"
            . "  The following list of commands are available when in interactive mode.\n"
            . "  detail <id>     : Show detail of tweet indicated in listing by id <id>.\n"
            . "  repeat          : Show the last list of tweets displayed.\n"
            . "  reply <id>      : Reply to tweet indicated in the listing by id <id>.\n"
            . "  exit | quit | q : Exit program.\n";

        echo "\nWhen in interactive mode, type 'exit' or 'quit' to exit. Pressing enter at the prompt will\n"
            . "  skip to the next page of results if applicable.\n\n";
        //@codingStandardsIgnoreEnd

        $this->_terminal->op();
    }

    /**
     * Set access token
     * 
     * @param array $token The access token
     * @return void
     */
    public function setAccessToken($token)
    {
        $this->_accessToken = $token;
    }

    /**
     * Get TwitterOAuth connection object
     * 
     * @return object TwitterOAuth
     */
    public function getConnection()
    {
        $connection = new \TwitterOAuth\Api(
            $this->_consumerKey, $this->_consumerSecret,
            $this->_accessToken['oauth_token'],
            $this->_accessToken['oauth_token_secret']
        );

        $connection->useragent = 'Chatter Terminal Client ' . $this->_version;

        return $connection;
    }

    /**
     * Load authentication (oAuth)
     * 
     * @return void
     */
    protected function _loadAuth()
    {
        $this->_ensureConfigPath();

        // Check file
        $file = $this->_configPath . 'accesstoken';

        if (file_exists($file)) {
            $this->setAccessToken(unserialize(file_get_contents($file)));
            return true;
        }
        
        // if no file, send request for oAuth
        $connection = new \TwitterOAuth\Api(
            $this->_consumerKey, $this->_consumerSecret
        );

        $connection->useragent = 'Chatter Terminal Client ' . $this->_version;

        // Get temporary credentials with out-of-band option
        // (uses PIN to authorize)
        $requestToken = $connection->getRequestToken('oob');

        // Build authorization URL
        $url = $connection->getAuthorizeURL($requestToken['oauth_token']);

        $this->showChatterAsciiArt();
        echo "To authorize Chatter Terminal on this terminal, "
            . "go to the URL below\n"
            . "in your web browser and click the Allow button "
            . "(after logging into Twitter):\n\n"
            . $url . "\n\n"
            . "After you have allowed access, return here "
            . "and type in the PIN to continue.\n\n";
        echo "Enter PIN:";
        $pin = Qi_Console_Std::in();

        $connection = new \TwitterOAuth\Api(
            $this->_consumerKey, $this->_consumerSecret,
            $requestToken['oauth_token'], $requestToken['oauth_token_secret']
        );

        $connection->useragent = 'Chatter Terminal Client ' . $this->_version;

        try {
            $accessToken = $connection->getAccessToken(trim($pin));
        } catch (Exception $e) {
            $this->_halt($e->getMessage());
        }

        file_put_contents(
            $this->_configPath . "accesstoken", serialize($accessToken)
        );
        chmod($this->_configPath . "accesstoken", 0600);

        $this->setAccessToken($accessToken);
    }

    /**
     * Confirm config dir exists
     *
     * @return void
     */
    protected function _ensureConfigPath()
    {
        if (!file_exists($this->_configPath)) {
            mkdir($this->_configPath);
        }
    }

    /**
     * Get the last id that was received from timeline
     *
     * @return int The last id
     */
    protected function _getLastId()
    {
        $filepath = $this->_configPath . 'lastid';
        $lastid   = false;

        if (file_exists($filepath)) {
            $lastid = trim(file_get_contents($filepath));
        }

        return $lastid;
    }

    /**
     * Save the last id from the received tweets
     *
     * @param array $tweets An array of tweets
     * @return void
     */
    protected function _saveLastId($tweets)
    {
        if (!is_array($tweets) || empty($tweets)) {
            return false;
        }

        $last = end($tweets);
        if ($last->id_str != '') {
            file_put_contents(
                $this->_configPath . 'lastid', (string) $last->id_str
            );
        }
    }

    protected function _getSavedTweetsLastId()
    {
        $tweets = $this->_savedTweets;

        if (!is_array($tweets) || empty($tweets)) {
            return false;
        }

        $last = end($tweets);
        if ($last->id_str != '') {
            return $last->id_str;
        }

        return false;
    }

    /**
     * Exit with error message
     *
     * @param string $message Error message
     * @return void
     */
    protected function _halt($message)
    {
        if ($this->_args->interactive == true) {
            $this->_warning_message($message);
        } else {
            $this->_display_error($message);
            exit(2);
        }
    }

    /**
     * Exit program safely
     * 
     * @param int $status Exit status
     * @return void
     */
    protected function _safe_exit($status = 0)
    {
        if ($this->_terminal->isatty()) {
            $this->_reset_tty();
        }
        exit($status);
    }

    /**
     * Reset tty mode
     *
     * If not windows, revert back to a sane tty
     * 
     * @return void
     */
    protected function _reset_tty()
    {
        if (DIRECTORY_SEPARATOR != "\\") {
            // unix
            shell_exec('stty sane');
        }
    }

    /**
     * Set foreground color
     *
     * Handles various ways of setting fg color
     * 
     * @param int $value Color value
     * @return string
     */
    protected function _doForegroundColor($value)
    {
        if ($this->_args->bigletters) {
            return '\\' . $value;
        }

        return $this->_terminal->do_setaf($value);
    }

    /**
     * Do original pair (terminal op)
     * 
     * @return string
     */
    protected function _doOriginalPair()
    {
        if ($this->_args->bigletters) {
            return '\\7';
        }

        return $this->_terminal->do_op();
    }

    /**
     * Display a warning message
     *
     * @param mixed $message Warning message
     * @param mixed $ensure_newline Whether a new line should be appended
     * @return void
     */
    protected function _warning_message($message, $ensure_newline = true)
    {
        $this->_display_message($message, $ensure_newline, 1); //red
    }

    /**
     * Display a message
     *
     * @param mixed $message Message
     * @param mixed $ensure_newline Whether a new line should be appended
     * @param int $color Color to use
     * @return void
     */
    protected function _display_message($message, $ensure_newline = true,
        $color = 2)
    {
        if ($ensure_newline && substr($message, -1) != "\n") {
            $message .= "\n";
        }

        $this->_terminal->setaf($color);
        echo $message;
        $this->_terminal->op();
    }

    /**
     * Display an error
     *
     * @param string $message Error message
     * @return void
     */
    protected function _display_error($message)
    {
        echo "\n";
        $this->_terminal->pretty_message($message, 7, 1);
        echo "\n";
    }

    /**
     * This verifies that everything will run smoothly
     * 
     * @return void
     */
    protected function _check_environment()
    {
        if (!function_exists('curl_init')) {
            $this->_halt(
                'PHP configuration lacks curl module. '
                . 'Please install curl module to run.'
            );
        }
    }

    /**
     * Get source elements from the source data
     * 
     * @param string $source Source data string
     * @return array
     */
    protected static function _getSourceElements($source)
    {
        $source = html_entity_decode($source);

        $hasUrl = preg_match('/href="(.*)"/U', $source, $matches);

        if ($hasUrl && count($matches)) {
            $url = $matches[1];
        } else {
            $url = '';
        }

        $name = strip_tags($source);

        return array(
            'name' => $name,
            'url'  => $url,
        );
    }

    /**
     * Display image (using p2a)
     * 
     * @param string $filename Path to file
     * @return void
     */
    protected function _display_image($filename)
    {
        if (trim($filename) == '') {
            return false;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if ($this->_terminal->isatty()) {
            $cmdColors = " --colors";
        } else {
            $cmdColors = "";
        }

        switch (strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            $cmd = "jp2a -b$cmdColors --size=48x24 \"" . $filename . "\" 2> /dev/null";
            break;
        case 'png':
            $cmd = "convert \"$filename\" jpg:- "
                . "| jp2a -b$cmdColors --size=48x24 -";
            break;
        default:
            return false;
            break;
        }

        passthru($cmd);
    }
}

