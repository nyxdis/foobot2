<?php
/**
 * qalc plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
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
		$result = exec_timeout('/usr/bin/qalc', implode(' ', $args));
		if ($result === false)
			parent::answer('Oops');
		else
			parent::answer($result);
	}
}

?>
