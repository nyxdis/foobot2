<?php
/**
 * snack plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class snack extends plugin_interface
{
	public function load()
	{
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'command', 'snack', 'pub_snack');
	}

	public function pub_snack($args)
	{
		$bot = bot::get_instance();
		$bot->act($bot->channel, 'munches ' . $bot->usr->nick . '\'s snack');
	}
}

?>
