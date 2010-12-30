<?php
/**
 * bot class
 *
 * Low-level bot interaction
 * @author Christoph Mende <angelos@unkreativ.org
 * @package foobot
 **/

/**
 * bot class
 * @package foobot
 * @subpackage classes
 **/
class bot
{
	/**
	 * Is the bot connected?
	 * @var bool
	 **/
	public $connected = false;

	/**
	 * TODO doc
	 * @var communication
	 **/
	private $protocol = NULL;

	/**
	 * Global instance of this class
	 * @access private
	 * @var bot
	 **/
	private static $instance = NULL;

	/**
	 * List of joined channels
	 * @access private
	 * @var array
	 **/
	private $channels;

	/**
	 * Socket for the connection
	 * @access private
	 * @var resource
	 **/
	private $socket;

	/**
	 * Resource for the log file
	 * @access private
	 * @var resource
	 **/
	private $log_fp;

	/**
	 * Returns the global instance for the bot class
	 * @return bot The instance
	 **/
	public static function get_instance()
	{
		if (self::$instance == NULL)
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Constructor, creates log dir if neccessary and opens the socket
	 * and log resource handle.
	 * @access private
	 **/
	private function __construct()
	{
		global $settings;

		if (!file_exists('logs'))
			mkdir('logs');

		$this->open_log();
		$this->create_socket();
	}

	/**
	 * Open log file
	 * @access private
	 **/
	private function open_log()
	{
		global $settings;

		$filename = 'logs/cmdlog-' . $settings['network'] . '.log';
		$this->log_fp = fopen($filename, 'a+');
		if (!$this->log_fp)
			die ('Failed to open log file');
	}

	/**
	 * Create socket
	 * @access private
	 **/
	private function create_socket()
	{
		$this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
	}

	/**
	 * Connect the bot
	 * @return bool
	 **/
	public function connect()
	{
		$this->protocol = new $settings['protocol'];
		$this->log(DEBUG, 'Connecting');
		if (!socket_connect($this->socket, $settings['server'], $settings['port']))
			return false;
		return $this->protocol->connect();
	}

	/**
	 * Stuff that needs to be done after connecting
	 **/
	public function post_connect()
	{
		global $settings;

		$this->protocol->post_connect();

		if (isset ($settings['debug_channel']))
			$this->join($settings['debug_channel']);

		$this->log(DEBUG, 'Joining channels');
		foreach ($settings['channels'] as $channel => $key) {
			$this->log(DEBUG, 'Joining ' . $channel);
			$this->join($channel, $key);
		}
	}

	/**
	 * Join a channel
	 * @param string $channel name of the channel
	 * @param string $key key of the channel
	 **/
	public function join($channel, $key = NULL)
	{
		if (!in_array($channel, $this->channels))
			$this->channels[] = $channel;
		$this->protocol->join($channel, $key);
	}
}

?>
