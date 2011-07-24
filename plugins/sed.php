<?php
/**
 * sed plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class sed extends plugin_interface
{
	private $lastline = array();

	public function init()
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
		if (empty ($args['global'])) {
			$ll = &$this->lastline[$channel][$usr->nick];
			$tmp = preg_replace($match, $args['replace'], $ll);
		}

		if (!empty ($args['global']) || $tmp == $ll) {
			$ll = &$this->lastline[$channel][0];
			$tmp = preg_replace($match, $args['replace'], $ll);
		}

		if ($tmp != $ll) {
			$tmp = substr($tmp, 0, 500);
			$ll = $tmp;
			parent::answer($ll);
		}
	}

	public function sed_save($args)
	{
		$text = implode(' ', $args);

		if (preg_match('/^s(?<match>\/.*)\/(?<replace>.*)(?<opts>\/i?)(?<global>g?)/', $text))
			return;

		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;

		$this->lastline[$channel][$usr->nick] = $text;
		$this->lastline[$channel][0] = $text;
	}
}

?>
