<?php
/**
 * Settings management
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Bot version id for comparisons
 */
define('BOT_VERSION_ID', 19900);
/**
 * @ignore
 */
define('LF', "\n");

// Generate BOT_VERSION from BOT_VERSION_ID
$major = floor(BOT_VERSION_ID / 10000);
$minor = floor((BOT_VERSION_ID - ($major * 10000)) / 100);
$micro = BOT_VERSION_ID - ($major * 10000) - ($minor * 100);
/**
 * Bot version as human readable string
 */
define('BOT_VERSION', $major . '.' . $minor . '.' . $micro);
unset ($major, $minor, $micro);

// define git revision if we're running from a git repo
if (file_exists('.git')) {
	$rev = file_get_contents('.git/refs/heads/master');
	$rev = substr($rev, 0, 7);
	/**
	 * git revision
	 */
	define('GIT_REV', $rev);
	unset ($rev);
}

/**
 * Settings management
 * Note: default values for runtime changable attributes are set in set_defaults()
 *
 * @package foobot
 * @subpackage classes
 */
class settings
{
	/**
	 * Prefix for commands
	 * @see settings::set_defaults()
	 */
	public static $command_char;
	/**
	 * Currently used protocol
	 */
	public static $protocol = 'irc';
	/**
	 * Nick of the bot
	 * @see settings::set_defaults()
	 */
	public static $nick;
	/**
	 * Username of the bot
	 */
	public static $username = 'foobot';
	/**
	 * Realname of the bot
	 */
	public static $realname = 'foobot';
	/**
	 * Server address
	 */
	public static $server = '';
	/**
	 * Server port
	 */
	public static $port = 6667;
	/**
	 * Network description (for internal use)
	 */
	public static $network = 'default';
	/**
	 * Channels to join on startup
	 * @see settings::set_defaults()
	 */
	public static $channels;
	/**
	 * Authentication password
	 * @see settings::set_defaults()
	 */
	public static $authpass;
	/**
	 * Authentication username
	 * @see settings::set_defaults()
	 */
	public static $authnick;
	/**
	 * Service to authenticate against
	 * @see settings::set_defaults()
	 */
	public static $authserv;
	/**
	 * Authentication command
	 * @see settings::set_defaults()
	 */
	public static $authcmd;
	/**
	 * Print debug output?
	 * @see settings::set_defaults()
	 */
	public static $debug_mode;
	/**
	 * Send debug output to this channels
	 * @see settings::set_defaults()
	 */
	public static $debug_channel;
	/**
	 * Main channel of the bot
	 * @see settings::set_defaults()
	 */
	public static $main_channel;
	/**
	 * IP address to listen on (important for DCC)
	 */
	public static $listen_addr = NULL;
	/**
	 * Port for DCC connections
	 */
	public static $dcc_port = 3333;
	/**
	 * Plugin blacklist
	 */
	public static $plugin_blacklist = array();
	/**
	 * Default timezone
	 */
	public static $timezone = 'UTC';

	/**
	 * Path to config
	 */
	private static $file;

	/**
	 * Set default values for runtime changable attributes
	 */
	private static function set_defaults() {
		self::$command_char = '!';
		self::$nick = 'foobot';
		self::$channels = array();
		self::$authpass = '';
		self::$authnick = '';
		self::$authserv = 'NickServ';
		self::$authcmd = 'identify';
		self::$debug_mode = false;
		self::$debug_channel = '';
		self::$main_channel = array();
	}

	/**
	 * Load settings from config ini
	 */
	public static function load($argc, $argv)
	{
		if ($argc == 1)
			self::$file = 'config.ini';
		else if ($argv[$argc - 1] != "main.php")
			self::$file = $argv[$argc - 1];

		if (!file_exists(self::$file))
			die ('Configuration file "' . self::$file . '" not found');

		self::set_defaults();
		self::do_load();
		self::check_required();
		self::parse_channels();

		if (self::$listen_addr == NULL)
			self::$listen_addr = gethostname();

		if (!is_array(self::$plugin_blacklist)) {
			$bl = str_replace(' ', '', self::$plugin_blacklist);
			self::$plugin_blacklist = explode(',', $bl);
		}
	}

	private static function do_load() {
		$settings = parse_ini_file(self::$file);
		foreach ($settings as $key => $value) {
			if (!property_exists(__CLASS__, $key)) {
				bot::get_instance()->log(WARNING, 'Unknown config key: ' . $key);
				continue;
			}

			if (!empty ($key))
				self::$$key = $value;
		}
	}

	private static function check_required() {
		$required = array('server', 'channels');

		foreach ($required as $key)
			if (empty (self::$$key))
				die ('Required setting \'' . $key . '\' missing!');

		if (!verify_timezone(settings::$timezone))
			die('Invalid timezone (' . settings::$timezone . ')');
	}

	private static function parse_channels() {
		foreach (explode(',', self::$channels) as $channel) {
			$c = explode(' ', trim($channel), 2);
			if (count($c) > 1)
				$channels[$c[0]] = $c[1];
			else
				$channels[$c[0]] = '';
		}
		self::$channels = $channels;

		if (empty (self::$main_channel)) {
			$key = key(self::$channels);
			self::$main_channel = array('channel' => $key,
					'key' => self::$channels[$key]);
		} else {
			$c = explode(' ', self::$main_channel, 2);
			self::$main_channel = array('channel' => $c[0]);
			if (count($c) == 2)
				self::$main_channel['key'] = $c[1];
		}

		if (!empty (self::$debug_channel)) {
			$c = explode(' ', self::$debug_channel, 2);
			self::$debug_channel = array('channel' => $c[0]);
			if (count($c) == 2)
				self::$debug_channel['key'] = $c[1];
			else
				self::$debug_channel['key'] = '';
		}
	}

	/**
	 * Reload settings
	 */
	public static function reload() {
		self::set_defaults();
		self::do_load();
		self::parse_channels();
	}
}
