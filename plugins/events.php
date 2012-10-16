<?php
/**
 * events plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class events extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'events', 'pub_events');
		$this->register_event('command', 'addevent');
		$this->register_event('command', 'delevent');

		$this->register_help('events', 'show upcoming events');
		$this->register_help('addevent', 'add an event');
		$this->register_help('delevent', 'remove an event');
	}

	public function pub_events($args)
	{
		$db = db::get_instance();

		if (empty ($args))
			$name = NULL;
		else
			$name = $args[0];
		$events = 'SELECT name, date FROM events WHERE date>=date(\'now\', \'localtime\')';
		if ($name) {
			$events .= ' AND `name` LIKE ?';
			$args = array('%' . $name . '%');
		} else {
			$args = array();
		}
		$events .= ' ORDER BY date ASC LIMIT 2';
		$events = $db->query($events, $args);
		$reply = 'Next up: ';
		while ($next = $events->fetchObject()) {
			$date = explode('-', $next->date);
			$reply .= $next->name . ' (';
			$days = floor((mktime(date('H'), date('i'), date('s'), $date[1], $date[2], $date[0]) - time()) / 86400);
			if ($days <= 0)
				$days = 'today';
			elseif ($days == 1)
				$days = 'tomorrow';
			else
				$days = $days . ' days';
			$reply .= $days . '), ';
		}
		if ($reply == 'Next up: ') {
			parent::answer('No events coming up');
			return;
		}
		$reply = rtrim($reply, ', ');
		parent::answer($reply);
	}

	public function addevent($args)
	{
		$db = db::get_instance();

		$args = implode(' ', $args);
		if (!preg_match('/(?<name>.*) (?<year>\d{4})-?(?<month>\d{2})-?(?<day>\d{2})/', $args, $matches)) {
			parent::answer('Invalid format, use <name> YYYY-MM-DD');
			return;
		}
		$db->query('INSERT INTO `events` (`name`, `date`) VALUES(?, ?, ?, ?)',
			$matches['name'], $matches['year'], $matches['month'], $matches['day']);
		$id = $db->lastInsertId();
		$this->register_timed('announce', mktime(0, 0, 0, $matches['month'], $matches['day'], $matches['year']), $id);
		$this->register_timed('announce', mktime(6, 0, 0, $matches['month'], $matches['day'], $matches['year']), $id);
		$this->register_timed('announce', mktime(12, 0, 0, $matches['month'], $matches['day'], $matches['year']), $id);
		$this->register_timed('announce', mktime(18, 0, 0, $matches['month'], $matches['day'], $matches['year']), $id);
		parent::answer('Roger.');
	}

	public function announce($id)
	{
		$db = db::get_instance();
		$bot = bot::get_instance();

		$name = $db->get_single_property('SELECT `name` FROM `events` WHERE `id` = ?', (int)$id);
		$bot->say(settings::$main_channel['channel'], 'Event happening today: ' . $name);
	}

	public function delevent($args)
	{
		$db = db::get_instance();

		$name = $args[0];
		$cnt = $db->get_single_property('SELECT COUNT(*) FROM `events` WHERE `name` LIKE ?', '%' . str_replace(' ', '%', $name) . '%');
		if ($cnt > 1) {
			parent::answer('Search string not unique');
			return;
		} elseif ($cnt == 0) {
			parent::answer('Search string doesn\'t match any events');
			return;
		}
		$db->query('DELETE FROM `events` WHERE `name` LIKE ?', '%' . str_replace(' ', '%', $name) . '%');
		parent::answer('Deleted event');
	}
}

?>
