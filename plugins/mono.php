<?php
/**
 * mono plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class mono extends plugin_interface
{
	private $mono = array();

	public function init()
	{
		$this->register_event('text', '/^[^' . preg_quote(settings::$command_char, '/') . ']+/', 'mono_save');
		$this->register_event('command', 'mono', 'pub_mono');

		$this->register_help('mono', 'display monologue length');
	}

	public function mono_save($args)
	{
		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;

		if (!isset ($this->mono[$channel]) || $this->mono[$channel]['nick'] != $usr->name) {
			$this->mono[$channel]['nick'] = $usr->name;
			$this->mono[$channel]['count'] = 1;
		} else {
			$this->mono[$channel]['count']++;
		}
	}

	public function pub_mono($args)
	{
		$channel = bot::get_instance()->channel;

		if (isset ($this->mono[$channel]) && $this->mono[$channel]['count'] > 1)
			parent::answer($this->mono[$channel]['nick'] . ' had a monologue over ' . $this->mono[$channel]['count'] . ' lines until now');
		else
			parent::answer('Nobody has a monologue at the moment');
	}
}

?>
