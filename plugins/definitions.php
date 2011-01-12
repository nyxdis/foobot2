<?php
/**
 * definitions plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class definitions extends plugin_interface
{
	public function load()
	{
		global $settings;

		$plugins = plugins::get_instance();

		$trigger = '/' . $settings['command_char'] . '(?<item>.+)(\?| is (?<definition>.+))/';
		$plugins->register_event(__CLASS__, 'text', $trigger, 'define');
		$plugins->register_event(__CLASS__, 'command', 'forget');
	}

	public function define($args)
	{
		$db = db::get_instance();

		$def = $db->query('SELECT * FROM `definitions` WHERE `item` LIKE ' . $db->quote($args['item']))->fetchObject();
		if (isset ($args['definition'])) {
			if (!$def)
				$db->query('INSERT INTO `definitions` VALUES(' . $db->quote($args['item']) . ', ' . $db->quote($args['definition']) . ')');
			else
				$db->query('UPDATE `definitions` SET `description` = ' . $db->quote($args['definition']) . ' WHERE `item` LIKE ' . $db->quote($args['item']));
			parent::answer('Okay.');
			return;
		}
		if (!$def)
			parent::answer($args['item'] . ' is undefined');
		else
			parent::answer($def->item . ' is ' . $def->description);
	}

	public function forget($args)
	{
		$db = db::get_instance();

		if (empty ($args)) {
			parent::answer('Forget what?');
			return;
		}
		$db->query('DELETE FROM `definitions` WHERE `item` LIKE ' . $db->quote($args[0]));
		self::answer('Okay');
	}
}

?>
