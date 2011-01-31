<?php
/**
 * foobot plugin demo
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class demo extends plugin_interface
{
	/**
	 * Plugin initialization
	 * @see plugins::register_event()
	 */
	public function init()
	{
		$this->register_event('command', 'ping', 'pub_ping');

		// Register help for the plugin's command 'ping'
		$this->register_help('ping', 'Simple ping command');
	}

	/**
	 * Ping function
	 * @param mixed $dummy unused
	 */
	public function pub_ping($dummy)
	{
		parent::answer('pong');
	}
}

?>
