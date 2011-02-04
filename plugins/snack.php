<?php
/**
 * snack plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class snack extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'snack', 'pub_snack');
		$this->register_event('snack', 'feed the bot');
	}

	public function pub_snack($args)
	{
		$bot = bot::get_instance();
		$bot->act($bot->channel, 'munches ' . $bot->usr->nick . '\'s snack');
	}
}

?>
