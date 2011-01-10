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
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'command', 'bofh', 'pub_bofh');
		$plugins->register_event(__CLASS__, 'command', 'addlart');
		$plugins->register_event(__CLASS__, 'command', 'lart');
	}

	public function pub_bofh($args)
	{
		$excuse = exec('/usr/bin/fortune bofh-excuses');
		parent::answer($excuse);
	}

	public function addlart($args)
	{
		global $db;

		$lart = implode(' ', $args);
                $db->query('INSERT INTO `larts` VALUES(' . $db->quote($lart) . ')');
		parent::answer('Added LART');
	}

	public function lart($args)
	{
		global $channel, $bot, $db, $usr;

		if (empty ($args))
			$nick = $usr->nick;
		else
			$nick = $args[0];

		$lart = $db->get_single_property('SELECT `lart` FROM `larts` ORDER BY RANDOM() LIMIT 1');
		$bot->act($channel, 'slaps ' . $nick . ' with ' . $lart);
	}
}

?>
