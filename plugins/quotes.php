<?php
/**
 * quotes
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class quotes extends plugin_interface
{
	public function load()
	{
		$this->register_event('command', '2q', 'pub_2q');
		$this->register_event('command', 'q');
		$this->register_event('command', 'aq');
		$this->register_event('command', 'dq');
		$this->register_event('command', 'iq');
		$this->register_event('command', 'sq');
		$this->register_event('command', 'tq');

		db::get_instance()->query('CREATE TABLE IF NOT EXISTS quotes (id integer primary key, text text, karma int)');
	}

	public function pub_2q($args)
	{
		$this->q(array('2'));
	}

	public function q($args)
	{
		$db = db::get_instance();

		$num = (int)$args[0];
		$quotes = $db->query('SELECT * FROM `quotes` WHERE `karma` > -3 ORDER BY RANDOM() LIMIT ' . $num);
		while ($quote = $quotes->fetchObject())
			parent::answer('#' . $quote->id . ' ' . $quote->text . ' (Karma: ' . $quote->karma . ')');
	}

	public function aq($args)
	{
		$db = db::get_instance();

		$quote = implode(' ', $args);
		$db->query('INSERT INTO `quotes` (`text`, `karma`) VALUES(' . $db->quote($quote) . ', 0)');
		parent::answer('Added quote ' . $db->lastInsertId());
	}

	public function dq($args)
	{
		$db = db::get_instance();

		$qid = (int)$args[0];
		$res = $db->query('DELETE FROM `quotes` WHERE `id` = ' . $qid);
		if ($res->rowCount() > 0)
			parent::answer('Deleted quote ' . $qid);
		else
			parent::answer('No quote with id ' . $qid . ' found');
	}

	public function iq($args)
	{
		$db = db::get_instance();

		$qid = (int)$args[0];
		$quote = $db->query('SELECT * FROM `quotes` WHERE `id` = ' . $qid);
		if ($quote = $quote->fetchObject())
			parent::answer($quote->text . ' (Karma: ' . $quote->karma . ')');
		else
			parent::answer('Can\'t fetch quote with id ' . $qid);
	}

	public function sq($args)
	{
		$db = db::get_instance();

		$text = implode(' ', $args);
		$quotes = $db->query('SELECT * FROM `quotes` WHERE `text` LIKE ' . str_replace(' ', '%', $db->quote('%' . $text . '%')) . ' ORDER BY RANDOM() LIMIT 3');
		while ($quote = $quotes->fetchObject())
			parent::answer('#' . $quote->id . ' ' . $quote->text . ' (Karma: ' . $quote->karma . ')');
	}

	public function tq($args)
	{
		$db = db::get_instance();

		if (empty ($args))
			$num = 3;
		else
			$num = (int)$args[0];

		if ($num > 5)
			$num = 5;
		elseif ($num < 1)
			$num = 1;
		$quotes = $db->query('SELECT * FROM `quotes` ORDER BY `karma` DESC LIMIT ' . $num);
		while ($quote = $quotes->fetchObject()) {
			parent::answer('#' . $quote->id . ' ' . $quote->text . ' (Karma: ' . $quote->karma . ')');
		}
	}
}

?>
