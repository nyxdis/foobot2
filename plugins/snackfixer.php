<?php
/**
 * snack typo fixer
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class snackfixer extends plugin_interface
{
	/**
	 * Plugin initialization
	 * @see plugins::register_event()
	 */
	public function init()
	{
		$this->register_event('command', '/(?<garbage>[snacmkl]{5})/', 'fix');
	}

	public function fix($args)
	{
		$str = $args['garbage'];
		if (strtolower($str) == "snack")
			return;
		if (substr_count($str, 's') == 1 &&
			substr_count($str, 'a') == 1 &&
			substr_count($str, 'c') == 1 &&
			((substr_count($str, 'n') == 1 ||
			substr_count($str, 'm') == 1) &&
			(substr_count($str, 'k') == 1 ||
			substr_count($str, 'l') == 1)))
			plugins::run_event('command', 'q', array());
	}
}

?>
