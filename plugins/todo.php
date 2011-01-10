<?php
/**
 * todo plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class todo extends plugin_interface
{
	public function load()
	{
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'command', 'done');
		$plugins->register_event(__CLASS__, 'command', 'delete');
		$plugins->register_event(__CLASS__, 'command', 'randomtodo');
		$plugins->register_event(__CLASS__, 'command', 'feature');
		$plugins->register_event(__CLASS__, 'command', 'todo', 'pub_todo');
	}

	public function done($args)
	{
		global $usr, $db;

		if (empty ($args)) {
			parent::answer('Need a TODO id or search string');
			return;
		}

		$id = $args[0];
		$todolist = unserialize($db->get_single_property('SELECT `todo` FROM `todo` WHERE `nick` = ' . $db->quote($usr->name)));
		if (!is_numeric($id)) {
			foreach ($todolist as $tid => $text) {
				if (preg_match('/' . $id . '/i', $text))
					$matches[] = $tid;
			}
			if (!isset ($matches)) {
				parent::answer('No TODOs matching your search string found');
				return;
			} elseif (count($matches) == 1) {
				$id = $matches[0];
			} else {
				parent::answer('Search string is not unique');
				return;
			}
		}

		if (isset ($todolist[$id]))
			parent::answer('Deleted TODO #' . (int)$id . ': ' . $todolist[$id]);
		else
			parent::answer('No TODO with id #' . (int)$id . ' found');
		unset($todolist[$id]);
		$db->query('UPDATE `todo` SET `todo` = ' . $db->quote(serialize($todolist)) . ' WHERE `nick` = ' . $db->quote($usr->name));
	}

	public function delete($args)
	{
		$this->done($args);
	}

	public function randomtodo($args)
	{
		global $usr, $db;

		$todo = $db->get_single_property('SELECT `todo` FROM `todo` WHERE `nick` = ' . $db->quote($usr->name));
		if (!$todo) {
			parent::answer('Nothing TODO for you');
			return;
		}
		$todo = unserialize($todo);
		$tid = 0;
		while ($tid == 0)
			$tid = array_rand($todo);
		parent::answer('#' . $tid . ' ' . $todo[$tid]);
	}

	public function feature($args)
	{
		$args = implode(' ', $args);
		$args = 'for angelos' . $args;
		$args = explode(' ', $args);
		$this->pub_todo($args);
	}

	public function pub_todo($args)
	{
		global $usr, $db;

		if (!empty ($args)) {
			$text = implode(' ', $args);
			preg_match('/^for (?<nick>[^ :]+):? ?(?<text>.*)/', $text, $matches);
		}
		if (isset ($matches['nick']) && isset ($bot->userlist[$matches['nick']]['usr'])) {
			$nick = $bot->userlist[$matches['nick']]['usr']->name;
		} elseif (isset ($matches['nick'])) {
			$nick = $matches['nick'];

			if (!$db->get_single_property('SELECT `id` FROM `users` WHERE `username` = ' . $db->quote($nick))) {
				parent::answer('No user named ' . $nick . ' found');
				return;
			}
		} else {
			$nick = $usr->name;
		}

		if (empty ($args) || (!empty ($matches['nick']) && empty ($matches['text']))) {
			$td = $db->get_single_property('SELECT `todo` FROM `todo` WHERE `nick` LIKE ' . $db->quote($nick));
			if (!$td)
				$nick = 'you';
			if (!$td || count(unserialize($td)) == 1) {
				parent::answer('Nothing TODO for ' . $nick);
				return;
			}
			$td = unserialize($td);
			$todolist = '';
			foreach ($td as $id => $item) {
				if ($id == 0)
					continue;
				$todolist .= '#' . $id . ' ' . $item . ' | ';
			}
			$todolist = rtrim($todolist, ' | ');
			if ($nick != 'you' && $nick != $usr->name)
				parent::answer('TODO for ' . $nick . ': ' . $todolist);
			else
				parent::answer($todolist);
		} else {
			if (!empty ($matches)) {
				$text = $matches['text'];
			}

			$todo = $db->get_single_property('SELECT `todo` FROM `todo` WHERE `nick` LIKE ' . $db->quote($nick));
			if ($nick != $usr->name)
				$text .= ' (by ' . $usr->name . ')';
			if (!$todo) {
				$todolist[0] = '';
				$todolist[] = $text;
				$db->query('INSERT INTO `todo` (`nick`, `todo`) VALUES(' . $db->quote($nick) . ', ' . $db->quote(serialize($todolist)) . ')');
			} else {
				$todolist = unserialize($todo);
				$todolist[] = $text;
				$db->query('UPDATE `todo` SET `todo` = ' . $db->quote(serialize($todolist)) . ' WHERE `nick` = ' . $db->quote($nick));
			}
			end($todolist);
			parent::answer('Added TODO #' . key($todolist));
		}
	}
}

?>