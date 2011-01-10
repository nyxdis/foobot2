<?php
/**
 * events plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class events extends plugin_interface
{
	public function load()
	{
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'command', 'events', 'pub_events');
		$plugins->register_event(__CLASS__, 'command', 'addevent');
		$plugins->register_event(__CLASS__, 'command', 'delevent');
	}

	public function pub_events($args)
	{
		global $db;

		if (empty ($args))
			$name = NULL;
		else
			$name = $args[0];
		$events = 'SELECT name, date FROM events WHERE date>=date(\'now\', \'localtime\')';
		if ($name)
			$events .= ' AND name LIKE ' . $db->quote('%' . $name . '%');
		$events .= ' ORDER BY date ASC LIMIT 2';
		$events = $db->query($events);
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
		global $db;

		$args = implode(' ', $args);
		if (!preg_match('/(?<name>.*) (?<year>\d{4})-?(?<month>\d{2})-?(?<day>\d{2})/', $args, $matches)) {
			parent::answer('Invalid format, use <name> YYYY-MM-DD');
			return;
		}
		$db->query('INSERT INTO events (name, date) VALUES(' . $db->quote($matches['name']) . ', \'' . $matches['year'] . '-' . $matches['month'] . '-' . $matches['day'] . '\')');
		$id = $db->lastInsertId();
		$plugins = plugins::get_instance();
		$plugins->register_timed(__CLASS__, 'announce', mktime(0, 0, 0, $matches['month'], $matches['day'], $matches['year']), $id);
		$plugins->register_timed(__CLASS__, 'announce', mktime(6, 0, 0, $matches['month'], $matches['day'], $matches['year']), $id);
		$plugins->register_timed(__CLASS__, 'announce', mktime(12, 0, 0, $matches['month'], $matches['day'], $matches['year']), $id);
		$plugins->register_timed(__CLASS__, 'announce', mktime(18, 0, 0, $matches['month'], $matches['day'], $matches['year']), $id);
		parent::answer('Roger.');
	}

	public function announce($id)
	{
		global $bot, $db, $settings;

		$name = $db->get_single_property('SELECT `name` FROM `events` WHERE `id` = ' . (int)$id);
		$bot->say($settings['main_channel'], 'Event happening today: ' . $name);
	}

	public function delevent($args)
	{
		global $db;

		$name = $args[0];
		$cnt = $db->get_single_property('SELECT COUNT(*) FROM events WHERE name LIKE ' . $db->quote('%' . str_replace(' ', '%', $name) . '%'));
		if ($cnt > 1) {
			parent::answer('Search string not unique');
			return;
		} elseif ($cnt == 0) {
			parent::answer('Search string doesn\'t match any events');
			return;
		}
		$db->query('DELETE FROM events WHERE name LIKE ' . $db->quote('%' . str_replace(' ', '%', $name) . '%'));
		parent::answer('Deleted event');
	}
}

?>
