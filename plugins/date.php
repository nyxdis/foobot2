<?php
/**
 * foobot date plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class date extends plugin_interface
{
	/**
	 * Plugin initialization
	 * @see plugins::register_event()
	 */
	public function init()
	{
		$this->register_event('command', 'date', 'pub_date');

		$this->register_help('date', 'Current date');
	}

	public function pub_date($dummy)
	{
		$date = date('r');
		parent::answer($date);
	}
}

?>
