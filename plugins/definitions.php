<?php
/**
 * definitions plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class definitions extends plugin_interface
{
	public function init()
	{
		$trigger = '/' . preg_quote(settings::$command_char, '/') . '(?<item>\S+)(\?| is (?<definition>.+))$/';
		$this->register_event('text', $trigger, 'define');
		$this->register_event('command', 'forget');

		db::get_instance()->query('CREATE TABLE IF NOT EXISTS definitions (item varchar(50) unique, description text)');
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
