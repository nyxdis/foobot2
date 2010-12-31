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

	protected function answer($text)
	{
		$bot = bot::get_instance();
	}
}

/**
 * Plugin management
 *
 * @package foobot
 * @subpackage classes
 **/
class plugins
{
	/**
	 * Array of loaded plugins
	 * @var array
	 **/
	private $loaded = array();

	private $help = array();
	private $commands = array();

	private static $instance = NULL;

	private function __construct() {}
	private function __clone() {}
	public static function get_instance()
	{
		if (self::$instance == NULL)
			self::$instance = new self;
		return self::$instance;
	}

	public function load($plugin)
	{
		include 'plugins/' . $plugin . '.php';
		$plug = new $plugin();
		$plug ->load();
	}

	public function register_command($plugin, $command, $function = NULL)
	{
		if (!$function)
			$function = $command;
		$this->commands[$command] = array('plugin' => $plugin, 'function' => $function);
	}

	public function register_help($command, $help)
	{
		$this->help[$command] = $help;
	}
}

?>
