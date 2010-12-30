<?php
/**
 * Plugin management
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Abstract class used by plugins
 *
 * @package foobot
 * @subpackage classes
 **/
abstract class plugin_interface
{
	abstract public function load();
}

/**
 * Array of loaded plugins
 * @global array $plugs
 **/
$plugs = array();

/**
 * Function to load plugins
 * @param string $plugin name of the plugin to load
 **/
function load_plugin($plugin)
{
	global $plugs;

	include 'includes/' . $plugin . '.php';
	$plug = new $plugin();
	$plugs[$plugin] = $plug->load();
}

?>
