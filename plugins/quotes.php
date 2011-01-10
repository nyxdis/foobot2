<?php
/**
 * quotes
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class quotes extends plugin_interface
{
	public function load()
	{
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'command', '2q', 'pub_2q');
		$plugins->register_event(__CLASS__, 'command', 'q');
		$plugins->register_event(__CLASS__, 'command', 'aq');
		$plugins->register_event(__CLASS__, 'command', 'dq');
		$plugins->register_event(__CLASS__, 'command', 'iq');
		$plugins->register_event(__CLASS__, 'command', 'sq');
		$plugins->register_event(__CLASS__, 'command', 'tq');
	}

	public function pub_2q($args)
	{
		$this->q(array('2'));
	}

	public function q($args)
	{
		global $db;

		$num = (int)$args[0];
		$quotes = $db->query('SELECT * FROM `quotes` WHERE `karma` > -3 ORDER BY RANDOM() LIMIT ' . $num);
		while ($quote = $quotes->fetchObject())
			parent::answer('#' . $quote->id . ' ' . $quote->text . ' (Karma: ' . $quote->karma . ')');
	}

	public function aq($args)
	{
		global $db;

		$quote = implode(' ', $args);
		$db->query('INSERT INTO `quotes` (`text`, `karma`) VALUES(' . $db->quote($quote) . ', 0)');
		parent::answer('Added quote ' . $db->lastInsertId());
	}

	public function dq($args)
	{
		global $db;

		$qid = (int)$args[0];
		$res = $db->query('DELETE FROM `quotes` WHERE `id` = ' . $qid);
		if ($res->rowCount() > 0)
			parent::answer('Deleted quote ' . $qid);
		else
			parent::answer('No quote with id ' . $qid . ' found');
	}

	public function iq($args)
	{
		global $db;

		$qid = (int)$args[0];
		$quote = $db->query('SELECT * FROM `quotes` WHERE `id` = ' . $qid);
		if ($quote = $quote->fetchObject())
			parent::answer($quote->text . ' (Karma: ' . $quote->karma . ')');
		else
			parent::answer('Can\'t fetch quote with id ' . $qid);
	}

	public function sq($args)
	{
		global $db;

		$text = implode(' ', $args);
		$quotes = $db->query('SELECT * FROM `quotes` WHERE `text` LIKE ' . str_replace(' ', '%', $db->quote('%' . $text . '%')) . ' ORDER BY RANDOM() LIMIT 3');
		while ($quote = $quotes->fetchObject())
			parent::answer('#' . $quote->id . ' ' . $quote->text . ' (Karma: ' . $quote->karma . ')');
	}

	public function tq($args)
	{
		global $db;

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
