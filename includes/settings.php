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

// TODO doc
class settings
{
	public static $command_char = '!';
	public static $protocol = 'irc';
	public static $nick = 'foobot';
	public static $username = 'foobot';
	public static $realname = 'foobot';
	public static $server = '';
	public static $port = 6667;
	public static $network = 'default';
	public static $channels = array();
	public static $authpass = '';
	public static $authnick = '';
	public static $authserv = 'NickServ';
	public static $authcmd = 'identify';
	public static $debug_mode = false;
	public static $debug_channel = '';
	public static $main_channel = '';

	public static function load()
	{
		global $argc, $argv;

		if ($argc != 2)
			$file = 'config.ini';
		else
			$file = $argv[1];

		$settings = parse_ini_file($file);
		foreach ($settings as $key => $value)
			if (!empty ($key))
				self::$$key = $value;

		$required = array('server', 'channels');

		foreach ($required as $key)
			if (empty (self::$$key))
				die ('Required setting \'' . $key . '\' missing!');

		self::$channels = explode(',', str_replace(' ', '', self::$channels));
		if (empty (self::$main_channel))
			self::$main_channel = key(self::$channels);
	}
}
