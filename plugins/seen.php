<?php
/**
 * seen plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class seen extends plugin_interface
{
	private $seen = array();

	public function load()
	{
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'text', NULL, 'seen_save');
		$plugins->register_event(__CLASS__, 'command', 'seen', 'pub_seen');
	}

	public function seen_save($args)
	{
		global $usr;

		$this->seen[$usr->name] = time();
		$this->seen[$usr->nick] = time();
	}

	public function pub_seen($args)
	{
		global $usr, $settings;

		if (!isset ($args[0])) {
			parent::answer('Seen who?');
			return;
		}
		$nick = $args[0];
		if ($nick == $usr->nick || $nick == $usr->name) {
			parent::answer('That\'s you!');
			return;
		}
		if ($nick == $settings['nick']) {
			parent::answer('That\'s me!');
			return;
		}
		if (!isset ($this->seen[$nick])) {
			parent::answer('I\'ve never seen ' . $nick);
			return;
		}

		$seconds = (time() - $this->seen[$nick]);
		$days = $seconds / 86400;

		$seconds = abs($seconds);
		$days = $seconds / 86400;

		if ($days > 1) {
			$result .= floor($days) . ' day' . (floor($days) == 1 ? '' : 's');
			$seconds %= 86400;
		}

		$hours = floor($seconds / 3600);
		$seconds %= 3600;
		$minutes = floor($seconds / 60);
		$seconds %= 60;

		if (($hours > 0 || $minutes > 0 || $seconds > 0) && $days > 1)
			$result .= ', ';

		if ($hours == 0 && $minutes == 0 && $seconds > 0) {
			$unit = 'second' . ($seconds == 1 ? '' : 's');
			$result .= $seconds;
		} elseif ($hours == 0 && $minutes > 0) {
			$unit = 'minute' . ($minutes == 1 && $seconds == 0 ? '' : 's');
			if ($seconds > 0)
				$result .= $format_func($minutes) . ':' . $format_func($seconds);
			else
				$result .= $minutes;
		} elseif ($hours > 0) {
			$unit = 'hour' . ($hours == 1 && $minutes == 0 && $seconds == 0 ? '' : 's');
			$result .= ($minutes == 0 && $seconds == 0 ? $hours : $format_func($hours));
			$result .= ($minutes > 0 || $seconds > 0 ? ':' . $format_func($minutes) . ($seconds > 0 ? ':' . $format_func($seconds) : '') : '');
		}

		if (!empty ($unit))
			$result .= ' ' . $unit;

		parent::answer($nick . ' was last seen ' . $result . ' ago');
	}
}

?>