<?php
/**
 * Settings management
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * @ignore
 **/
define('BOT_VERSION_ID', 19900);
define('LF', "\n");

// Generate BOT_VERSION from BOT_VERSION_ID
$major = floor(BOT_VERSION_ID / 10000);
$minor = floor((BOT_VERSION_ID - ($major * 10000)) / 100);
$micro = BOT_VERSION_ID - ($major * 10000) - ($minor * 100);
define('BOT_VERSION', $major . '.' . $minor . '.' . $micro);
unset ($major, $minor, $micro);

// define git revision if we're running from a git repo
if (file_exists('.git')) {
	$rev = file_get_contents('.git/refs/heads/master');
	$rev = substr($rev, 0, 7);
	define('GIT_REV', $rev);
	unset ($rev);
}

/**
 * Settings management
 *
 * @package foobot
 * @subpackage classes
 **/
class settings
{
	/**
	 * Prefix for commands
	 **/
	public static $command_char = '!';
	/**
	 * Currently used protocol
	 **/
	public static $protocol = 'irc';
	/**
	 * Nick of the bot
	 **/
	public static $nick = 'foobot';
	/**
	 * Username of the bot
	 **/
	public static $username = 'foobot';
	/**
	 * Realname of the bot
	 **/
	public static $realname = 'foobot';
	/**
	 * Server address
	 **/
	public static $server = '';
	/**
	 * Server port
	 **/
	public static $port = 6667;
	/**
	 * Network description (for internal use)
	 **/
	public static $network = 'default';
	/**
	 * Channels to join on startup
	 **/
	public static $channels = array();
	/**
	 * Authentication password
	 **/
	public static $authpass = '';
	/**
	 * Authentication username
	 **/
	public static $authnick = '';
	/**
	 * Service to authenticate against
	 **/
	public static $authserv = 'NickServ';
	/**
	 * Authentication command
	 **/
	public static $authcmd = 'identify';
	/**
	 * Print debug output?
	 **/
	public static $debug_mode = false;
	/**
	 * Send debug output to this channels
	 **/
	public static $debug_channel = '';
	/**
	 * Main channel of the bot
	 **/
	public static $main_channel = '';

	/**
	 * Load settings from config ini
	 **/
	public static function load($argc, $argv)
	{
		if ($argc != 2)
			$file = 'config.ini';
		else
			$file = $argv[1];

		$settings = parse_ini_file($file);
		foreach ($settings as $key => $value) {
			if (!property_exists(__CLASS__, $key)) {
				bot::get_instance()->log(WARNING, 'Unknown config key: ' . $key);
				continue;
			}

			if (!empty ($key))
				self::$$key = $value;
		}

		$required = array('server', 'channels');

		foreach ($required as $key)
			if (empty (self::$$key))
				die ('Required setting \'' . $key . '\' missing!');

		self::$channels = explode(',', str_replace(' ', '', self::$channels));
		if (empty (self::$main_channel))
			self::$main_channel = key(self::$channels);
	}
}
