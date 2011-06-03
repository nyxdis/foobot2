<?php
/**
 * Misc functions
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * @ignore
 */
function __autoload($class)
{
	$path = 'includes/' . $class . '.class.php';
	if (!file_exists($path))
		die ('Attempting to load inexistent class \'' . $class . '\'!');
	require_once $path;
}

/**
 * @ignore
 */
function foobot_error_handler($errno, $error, $file, $line, $context)
{
	$bot = bot::get_instance();

	switch ($errno) {
	case E_USER_ERROR:
	case E_ERROR:		$typestr = 'Error'; break;
	case E_USER_WARNING:
	case E_WARNING:		$typestr = 'Warning'; break;
	case E_USER_NOTICE:
	case E_NOTICE:		$typestr = 'Notice'; break;
	case E_STRICT:		$typestr = 'Strict notice'; break;
	case E_DEPRECATED:	$typestr = 'Deprecation notice'; break;
	default:
		$typestr = 'Unknown error';
	}

	$string = $typestr . ' in ' . $file . ' on line ' . $line . ': ' .
		$error;

	// Check if the bot is already initialized
	if ($bot->connected)
		$bot->say(settings::$debug_channel, $string);
	else
		die ($string);
}

set_error_handler('foobot_error_handler');

?>
