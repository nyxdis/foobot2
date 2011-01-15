<?php
/**
 * sed plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class sed extends plugin_interface
{
	private $lastline = array();

	public function load()
	{
		$trigger = '/^s(?<match>\/.*)\/(?<replace>.*)(?<opts>\/i?)(?<global>g?)/';
		$this->register_event('text', $trigger, 'sed_parse');
		$this->register_event('text', NULL, 'sed_save');
	}

	public function sed_parse($args)
	{
		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;

		$match = $args['match'] . $args['opts'];
		if (!empty ($args['global']))
			$ll = $this->lastline[$channel][0];
		else
			$ll = $this->lastline[$channel][$usr->nick];
		$ll = preg_replace($match, $args['replace'], $ll);
		parent::answer($ll);
	}

	public function sed_save($args)
	{
		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;

		$text = implode(' ', $args);
		$this->lastline[$channel][$usr->nick] = $text;
		$this->lastline[$channel][0] = $text;
	}
}

?>
