<?php
/**
 * qalc plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class qalc extends plugin_interface
{
	public function load()
	{
		$this->register_event('command', 'qalc', 'pub_qalc');
	}

	public function pub_qalc($args)
	{
		$term = implode(' ', $args);
                parent::answer(exec('/usr/bin/qalc "' . $term . '"'));
	}
}

?>