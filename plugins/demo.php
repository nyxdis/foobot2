<?php
/**
 * foobot plugin demo
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class demo extends plugin_interface
{
	/**
	 * Plugin initialization
	 * @see plugin_interface::register_command()
	 **/
	public function load()
	{
		$this->register_command('ping');
	}

	/**
	 * Help texts for available functions
	 * @see plugin_interface::register_help()
	 **/
	public function help()
	{
		// Register help for the plugin
		$this->register_help('demo', 'Plugin demonstration');
		// Register help for the plugin's command 'ping'
		$this->register_help('ping', 'Simple ping command');
	}
}

?>
