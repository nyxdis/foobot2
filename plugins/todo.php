<?php
/**
 * todo plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class todo extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'done');
		$this->register_event('command', 'delete');
		$this->register_event('command', 'randomtodo');
		$this->register_event('command', 'feature');
		$this->register_event('command', 'todo', 'pub_todo');

		$this->register_help('done', 'mark a task as done (remove it from list)');
		$this->register_help('delete', 'delete a task');
		$this->register_help('randomtodo', 'get a random task');
		$this->register_help('feature', 'put a task on angelos\' todo list');
		$this->register_help('todo', 'put something on your or someone else\'s todo list, syntax: todo for <nick>');

		db::get_instance()->query('CREATE TABLE IF NOT EXISTS todo (nick varchar(25) unique, todo text)');
	}

	public function done($args)
	{
		$db = db::get_instance();
		$usr = bot::get_instance()->usr;

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
		unset ($todolist[$id]);
		$db->query('UPDATE `todo` SET `todo` = ' . $db->quote(serialize($todolist)) . ' WHERE `nick` = ' . $db->quote($usr->name));
	}

	public function delete($args)
	{
		$this->done($args);
	}

	public function randomtodo($args)
	{
		$usr = bot::get_instance()->usr;
		$db = db::get_instance();

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
		$args = 'for angelos ' . $args;
		$args = explode(' ', $args);
		$this->pub_todo($args);
	}

	public function pub_todo($args)
	{
		$usr = bot::get_instance()->usr;
		$db = db::get_instance();
		$bot = bot::get_instance();

		if (!empty ($args)) {
			$text = implode(' ', $args);
			preg_match('/^for (?<nick>[^ :]+):? ?(?<text>.*)/', $text, $matches);
		}
		if (isset ($matches['nick']) && $bot->get_userlist($matches['nick'])) {
			$nick = $bot->get_userlist($matches['nick'])->name;
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
