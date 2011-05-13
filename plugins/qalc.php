<?php
/**
 * qalc plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class qalc extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'qalc', 'pub_qalc');

		$this->register_help('qalc', 'calculate using libqalc');
	}

	public function pub_qalc($args)
	{
		$term = escapeshellcmd(implode(' ', $args));
                parent::answer(exec('/usr/bin/qalc "' . $term . '"'));
	}
}

?>
