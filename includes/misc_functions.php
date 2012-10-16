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

	// Check if the bot is already initialized
	if ($bot->connected) {
		if (!empty (settings::$debug_channel)) {
			// print backtrace in debug mode
			if (settings::$debug_mode) {
				$bt = debug_backtrace();
				foreach ($bt as $num => $data) {
					$file = getifset($data, "file");
					$line = getifset($data, "line");
					$fn = $data["function"];

					// convert array args to strings
					foreach ($data["args"] as $k => $v) {
						$tlines = explode("\n",
							print_r($v, true));
						$data["args"][$k] = "";
						foreach ($tlines as $tline)
							$data["args"][$k] .= trim($tline);
					}

					$args = implode(", ", $data["args"]);

					$string = "#$num  $fn($args)";
					if (!empty ($file) && !empty ($line))
						$string .= " called at [$file:$line]";
					$bot->say(settings::$debug_channel['channel'], $string);
					file_put_contents('logs/' . settings::$network . '-error.log', $string . LF, FILE_APPEND);
				}
			} else {
				$string = $typestr . ' in ' . $file .
					' on line ' . $line . ': ' . $error;
				$bot->say(settings::$debug_channel['channel'],
					$string);
				file_put_contents('logs/' . settings::$network . '-error.log', $string . LF, FILE_APPEND);
			}
		}
	} else {
		die ($string);
	}
}

/**
 * Check an array for a specific index and return the value if it
 * exists, a default value otherwise.
 * @param string $array the array to search
 * @param string $index the index to search for
 * @param string $default value to return when index wasn't found
 * @return string $default or the value
 */
function getifset($array, $index, $default = "") {
	if (isset ($array[$index]))
		return $array[$index];
	else
		return $default;
}

/**
 * Execute a command and killing if it doesn't return fast enough
 * @param string $exec path to the binary
 * @param string $args arguments
 * @param int $timeout time to wait for the process
 * @return mixed false stdout of the process or false on error
 */
function exec_timeout($exec, $args = "", $timeout = 5)
{
	$pid = pcntl_fork();
	$fifopath = "foo";

	posix_mkfifo($fifopath, 0644);

	if ($pid == -1) {
		unlink($fifopath);
		return false;
	}

	// parent
	else if ($pid > 0) {
		$handle = fopen($fifopath, 'r');

		$read = array($handle);
		$write = NULL;
		$except = NULL;

		$changed = stream_select($read, $write, $execpt, $timeout);

		posix_kill($pid, SIGKILL);
		if (!$changed) {
			unlink($fifopath);
			return false;
		}

		$result = fgets($handle);
		unlink($fifopath);
		return $result;
	}

	// child
	else {
		$handle = fopen($fifopath, 'w');
		$cmd = $exec . ' ' . escapeshellarg($args);
		$result = exec($cmd);
		fputs($handle, $result);
		// race condition, this thread should never return
		sleep(1);
	}
}

set_error_handler('foobot_error_handler');

?>
