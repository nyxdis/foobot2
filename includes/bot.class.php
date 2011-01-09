<?php
/**
 * bot class
 *
 * Low-level bot interaction
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Logging levels
 **/
define('DEBUG', 0);
define('INFO', 1);
define('WARNING', 2);
define('ERROR', 3);

/**
 * bot class
 * @package foobot
 * @subpackage classes
 **/
class bot
{
	/**
	 * The bot's internal userlist
	 * @var array
	 **/
	public $userlist = array();

	/**
	 * Is the bot connected?
	 * @var bool
	 **/
	public $connected = false;

	/**
	 * The protocol to use (e.g. IRC)
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
	 * @ignore
	 **/
	private function __clone() {}

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
		global $settings;

		$this->protocol = new $settings['protocol'];
		$this->log(DEBUG, 'Connecting');
		if (!socket_connect($this->socket, $settings['server'], $settings['port']))
			return false;
		$this->protocol->connect();
		socket_set_nonblock($this->socket);
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
		if (in_array($channel, $this->channels))
			return;
		$this->channels[] = $channel;
		$this->protocol->join($channel, $key);
	}

	/**
	 * Send raw protocol data
	 * @param string $raw data
	 **/
	public function send($raw)
	{
		$this->protocol->send($raw);
	}

	/**
	 * Write data to the socket
	 * @param string $data data
	 **/
	public function write($data)
	{
		socket_write($this->socket, $data);
	}

	/**
	 * Read data from the socket
	 **/
	public function read()
	{
		$buf = socket_read($this->socket, 4096, PHP_NORMAL_READ);
		if (!$buf)
			die ('Error while reading socket');
		return $buf;
	}

	/**
	 * Write a log entry
	 * @param int $level log level
	 * @param string $msg text to log
	 **/
	public function log($level, $msg)
	{
		// TODO implement
		echo $msg . LF;
	}

	/**
	 * Wait for acion on the socket
	 **/
	public function wait()
	{
		$nul = NULL;
		$sock = array($this->socket);
		if (socket_select($sock, $nul, $nul, 1)) {
			$line = $this->read();
			if (!$line) {
				socket_close($this->socket);
				$this->connect();
			}
			$this->parse($line);
		} else {
			// reminders
			// save last seen table
			// announce events
		}
	}

	/**
	 * Parse incoming messages
	 * @access private
	 * @param string $line
	 **/
	private function parse($line)
	{
		global $settings, $db, $usr, $channel;

		$line = trim($line);

		if (!strncmp($line, 'PING :', 6))
			$this->send('PONG ' . strstr($line, ':'));

		// Update userlist on JOIN, NICK and WHO events
		if (preg_match('/:\S+ 352 ' . $settings['nick'] . ' \S+ (?<ident>\S+) (?<host>\S+) \S+ (?<nick>\S+) \S+ :\d+ (?<realname>.+)/', $line, $whoinfo) ||
			preg_match('/:(?<nick>.+)!(?<ident>.+)@(?<host>.+) JOIN .*/', $line, $whoinfo) ||
			preg_match('/:(?<oldnick>\S+)!(?<ident>\S+)@(?<host>\S+) NICK :(?<nick>\S+)/', $line, $whoinfo)) {
			$this->userlist[$whoinfo['nick']] = array('ident' => $whoinfo['ident'], 'host' => $whoinfo['host']);
			if (isset ($whoinfo['realname']))
				$this->userlist[$whoinfo['nick']]['realname'] = $whoinfo['realname'];
			if (!isset ($whoinfo['realname']) && !isset ($whoinfo['oldnick']))
				$this->send('WHO ' . $whoinfo['nick']);
			if (isset ($whoinfo['oldnick']))
				unset ($this->userlist[$whoinfo['oldnick']]);
			$user = new user($whoinfo['nick'], $whoinfo['ident'], $whoinfo['host']);
			$this->userlist[$whoinfo['nick']]['usr'] = $user;
		}

		$plugins = plugins::get_instance();

		// Parse text
		if (preg_match('/:(?<nick>\S+)!(?<ident>\S+)@(?<host>\S+) PRIVMSG (?<channel>#\S+) :(?<text>.*)/', $line, $nickinfo)) {
			$nick = $nickinfo['nick'];
			$usr = $this->userlist[$nickinfo['nick']]['usr'];
			$channel = $nickinfo['channel'];

			$plugins->run_event('text', $nickinfo['text']);
		}
		if (preg_match('/:(?<nick>\S+)!(?<ident>\S+)@(?<host>\S+) PRIVMSG ((?<target1>#\S+) :(' . $settings['command_char'] . '|' . $settings['nick'] . ': )|(?<target2>[^#]\S+) :' . $settings['command_char'] . '?)(?<cmd>\S+)(?<args>.*)/', $line, $cmdinfo)) {

			/* CTCP VERSION */
			if ($cmdinfo['cmd'] == "\001VERSION\001") {
				exec('git rev-parse --short HEAD', $gitver);
				$this->send("NOTICE $cmdinfo[nick] :\001VERSION foobot v" . BOT_VERSION . "-$gitver[0]\001");
				return;
			}

			if (!empty ($cmdinfo['target1'])) {
				$channel = $cmdinfo['target1'];
			} else {
				$channel = $cmdinfo['nick'];
				$usr = new user($cmdinfo['nick'], $cmdinfo['ident'], $cmdinfo['host']);
			}
			$cmd = strtolower($cmdinfo['cmd']);
			if (isset ($cmdinfo['args']))
				$args = $cmdinfo['args'];
			else
				$args = NULL;

			/* regular commands */
			$plugins->run_event('command', $cmd, trim($args));

			/* karma */
			if (preg_match('/(?<item>.*)(?<karma>(\+\+|--)+)($| ?# ?(?<comment>.*))/', $cmd . $args, $karmainfo)) {
				if ($channel[0] == '#') {
					$ki = array('item' => $karmainfo['item'], 'karma' => $karmainfo['karma']);
					if (isset ($karmainfo['comment'])) $ki['comment'] = $karmainfo['comment'];
					if ($ki['item'] == $usr->name)
						$ki['karma'] = '--';
					if ($karmainfo['karma'] == '++')
						$this->execute_command('karmaup', $ki);
					else
						$this->execute_command('karmadown', $ki);
				}
			}
			/* definitions */
			elseif (preg_match('/(?<item>.+)(\?| is (?<definition>.+))/', $cmd . $args, $definfo)) {
				$this->execute_command('define', $definfo);
			} elseif (strncmp($channel, '#', 1) != 0) {
				$this->say($settings['main_channel'], '<' . $cmdinfo['nick'] . '> ' . $cmd . ' ' . trim($args));
			}
		}
	}

	/**
	 * Execute command sent to the bot
	 * @param string $command the command to execute
	 * @param string $args optional string with parameters
	 **/
	private function execute_command($command, $args = NULL)
	{
		echo 'BUG: DO NOT USE bot::execute_command()!';
	}

	/**
	 * Send text to target
	 * @param string $target where to send text to
	 * @param string $text text to send
	 **/
	public function say($target, $text)
	{
		$this->protocol->say($target, $text);
	}
}

?>
