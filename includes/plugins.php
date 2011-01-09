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
	/**
	 * This function is run when the plugin is loaded
	 **/
	abstract public function load();

	/**
	 * Convenience function that sends 'Nick: text' to the channel where
	 * the event originated
	 * @param string $text the text to send
	 **/
	protected function answer($text)
	{
		global $usr, $channel;

		$bot = bot::get_instance();
		$answer = $usr->nick . ': ' . $text;
		$bot->say($channel, $answer);
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
	 * @access private
	 **/
	private $loaded = array();

	/**
	 * Registered help texts
	 * @var array
	 * @access private
	 **/
	private $help = array();

	/**
	 * Registered events
	 * @var array
	 * @access private
	 **/
	private $events = array();

	/**
	 * This class' instance
	 * @var plugins
	 * @access private
	 **/
	private static $instance = NULL;

	/**
	 * @ignore
	 **/
	private function __construct() {}
	private function __clone() {}

	/**
	 * Use this to get an instance of this class
	 **/
	public static function get_instance()
	{
		if (self::$instance == NULL)
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Load a plugin
	 * @param string $plugin name of the plugin
	 **/
	public function load($plugin)
	{
		include 'plugins/' . $plugin . '.php';
		$plug = new $plugin();
		$plug ->load();
		$this->loaded[$plugin] = $plug;
	}

	/**
	 * Register an event
	 * @param string $plugin the registering plugin
	 * @param string $event what kind of event
	 * @param string $trigger optional trigger for the event (useful for text events)
	 * @param string $function method to call
	 **/
	public function register_event($plugin, $event, $trigger = NULL, $function = NULL)
	{
		if (!$function)
			$function = $event;

		$events = array('command', 'text', 'join');
		if (!in_array($event, $events))
			return false;

		if ($event == 'command' && !$trigger)
			return false;

		$this->events[$event][] = array('plugin' => $plugin, 'function' => $function, 'trigger' => $trigger);
	}

	/**
	 * Execute event
	 * @param string $event what kind of event happened
	 * @param string $trigger used trigger if available
	 * @param string $args arguments to the trigger
	 **/
	public function run_event($event, $trigger = NULL, $args = NULL)
	{
		foreach($this->events[$event] as $entry)
		{
			if ($entry['trigger'] &&
			  (substr($entry['trigger'], 0, 1) == '/' && !preg_match($entry['trigger'], $trigger, $preg_args)) &&
			    $entry['trigger'] != $trigger)
				continue;

			if (isset ($preg_args) && !empty ($preg_args))
				$args = $preg_args;
			elseif ($args)
				$args = explode(' ', $args);
			else
				$args = explode(' ', $trigger);

			$this->loaded[$entry['plugin']]->$entry['function']($args);
		}
	}

	/**
	 * Register help text
	 * @param string $command which command do we document here
	 * @param string $help the help text
	 **/
	public function register_help($command, $help)
	{
		$this->help[$command] = $help;
	}
}

?>
