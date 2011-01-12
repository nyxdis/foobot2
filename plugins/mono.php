<?php
/**
 * mono plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class mono extends plugin_interface
{
	private $mono = array();

	public function load()
	{
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'text', NULL, 'mono_save');
		$plugins->register_event(__CLASS__, 'command', 'mono', 'pub_mono');
	}

	public function mono_save($args)
	{
		global $settings;
		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;

		$text = implode(' ', $args);
		if ($text{0} == $settings['command_char'])
			return;

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
