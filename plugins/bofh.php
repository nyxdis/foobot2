<?php
/**
 * bofh plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class bofh extends plugin_interface
{
	public function load()
	{
		$this->register_event('command', 'bofh', 'pub_bofh');
		$this->register_event('command', 'addlart');
		$this->register_event('command', 'lart');

		db::get_instance()->query('CREATE TABLE IF NOT EXISTS larts (lart varchar(50))');
	}

	public function pub_bofh($args)
	{
		$excuse = exec('/usr/bin/fortune bofh-excuses');
		parent::answer($excuse);
	}

	public function addlart($args)
	{
		$db = db::get_instance();

		$lart = implode(' ', $args);
		$db->query('INSERT INTO `larts` VALUES(' . $db->quote($lart) . ')');
		parent::answer('Added LART');
	}

	public function lart($args)
	{
		$bot = bot::get_instance();
		$db = db::get_instance();
		$channel = $bot->chanel;
		$usr = $bot->usr;

		if (empty ($args))
			$nick = $usr->nick;
		else
			$nick = $args[0];

		$lart = $db->get_single_property('SELECT `lart` FROM `larts` ORDER BY RANDOM() LIMIT 1');
		$bot->act($channel, 'slaps ' . $nick . ' with ' . $lart);
	}
}

?>
