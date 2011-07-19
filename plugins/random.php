<?php
/**
 * random plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class random extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'new', 'pub_new');
		$this->register_event('command', 'gimme');

		$this->register_help('new', 'add new item (new <type> <item>)');
		$this->register_help('gimme', 'return random <type>');

		db::get_instance()->query('CREATE TABLE IF NOT EXISTS random (type varchar(25), item varchar(250))');
	}

	public function pub_new($args)
	{
		$db = db::get_instance();

		$type = array_shift($args);
		$item = implode(' ', $args);
		$db->query('INSERT INTO `random` VALUES(?, ?)', $type, $item);
		parent::answer('Added item');
	}

	public function gimme($args)
	{
		$db = db::get_instance();

		if (empty ($args)) {
			parent::answer('Gimme what?');
			return;
		}

		$item = $db->get_single_property('SELECT `item` FROM `random` WHERE `type` like ? ORDER BY RANDOM() LIMIT 1', $args[0]);
		if (empty ($item))
			parent::answer('No idea.');
		else
			parent::answer('How about some ' . $item . '?');
	}
}

?>
