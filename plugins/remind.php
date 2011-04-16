<?php
/**
 * remind plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class remind extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'remind', 'pub_remind');

		$this->register_help('remind', 'simple reminder, syntax: remind (me|nick) about something (in|at) time');
	}

	public function pub_remind($args)
	{
		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;

		$args = implode(' ', $args);
		preg_match('/(?<who>.+?) about (?<what>.+) (in (?<time1>\d+)(?<unit>.+)|at (?<time2>\d+[:.]\d\d))/', $args, $matches);
		if (!isset ($matches['who']) || !isset ($matches['what']) || ((!isset ($matches['time1']) && !isset ($matches['unit'])) && !isset ($matches['time2']))) {
			self::answer('Bad format, use \'remind (me|nick) about something (in|at) time\'');
			return;
		}

		$event['target'] = $channel;

		if ($matches['who'] == 'me')
			$event['text'] = $usr->nick;
		else
			$event['text'] = $matches['who'];
		$event['text'] .= ': '.$matches['what'];

		if (!empty ($matches['time1'])) { /* in x minutes */
			$unit = trim($matches['unit']);
			if ($unit == 'seconds' || $unit == 'sec' || $unit == 's')
				$time = time() + ($matches['time1']);
			elseif ($unit == 'minutes' || $unit == 'min' || $unit == 'm')
				$time = time() + ($matches['time1'] * 60);
			elseif ($unit == 'hours' || $unit == 'hr' || $unit == 'h')
				$time = time() + ($matches['time1'] * 3600);
			else {
				self::answer('Bad format, use sec/min/h');
				return;
			}
		} else {			/* at xx:xx */
			$time2 = explode(':', $matches['time2']);
			$time = mktime($time2[0], $time2[1], 0);
			if ($time < time())
				$time = mktime($time2[0], $time2[1], 0, date('n'), date('j')+1);
			if ($time < time()) {
				self::answer('Invalid time');
				return;
			}
		}

		$this->register_timed('do_remind', $time, $event);
		parent::answer('Roger.');
	}

	public function do_remind($args)
	{
		$bot = bot::get_instance();

		$bot->say($args['target'], $args['text']);
	}
}

?>
