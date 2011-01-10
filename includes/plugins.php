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
		if ($usr->nick != $channel)
			$text = $usr->nick . ': ' . $text;
		$bot->say($channel, $text);
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
	 * Registered recurring events
	 * @var array
	 * @access private
	 **/
	private $recurring = array();

	/**
	 * Registered timed events
	 * @var array
	 * @access private
	 **/
	private $timed = array();

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
	public function register_event($plugin, $event, $trigger = NULL, $function = NULL, $level = 1)
	{
		if (!$function)
			$function = str_replace('-', '_', $trigger);

		$events = array('command', 'text', 'join');
		if (!in_array($event, $events))
			return false;

		if ($event == 'command' && !$trigger)
			return false;

		$this->events[$event][] = array('plugin' => $plugin,
				'function' => $function,
				'trigger' => $trigger,
				'level' => $level);
	}

	/**
	 * Execute event
	 * @return bool did the event match anything?
	 * @param string $event what kind of event happened
	 * @param string $trigger used trigger if available
	 * @param string $argv arguments to the trigger
	 **/
	public function run_event($event, $trigger = NULL, $argv = NULL)
	{
		global $usr, $bot, $channel;

		$return = false;

		foreach ($this->events[$event] as $entry)
		{
			if ($entry['trigger'] &&
			  (($entry['trigger']{0} == '/' && !preg_match($entry['trigger'], $trigger, $preg_args)) ||
			    ($entry['trigger']{0} != '/' && $entry['trigger'] != $trigger)))
				continue;

			if ($entry['level'] > $usr->level)
				continue;

			$return = true;

			if (isset ($preg_args) && !empty ($preg_args))
				$args = $preg_args;
			elseif ($argv && !is_array($argv))
				$args = explode(' ', $argv);
			elseif (!$argv && $event != 'command')
				$args = explode(' ', $trigger);
			else
				$args = $argv;

			if ($event == 'command')
				$bot->log_cmd($usr->name, $channel, $entry['trigger'], $args);

			$this->loaded[$entry['plugin']]->$entry['function']($args);
		}

		return $return;
	}

	/**
	 * Register a recurring event
	 * @param string $plugin the registering plugin
	 * @param string $function method to call
	 * @param int $interval interval in seconds
	 **/
	public function register_recurring($plugin, $function, $interval, $args = NULL)
	{
		$this->recurring[] = array('plugin' => $plugin,
				'function' => $function,
				'interval' => $interval,
				'args' => $args,
				'last_execution' => 0);
	}

	/**
	 * Run recurring events
	 **/
	public function run_recurring()
	{
		foreach ($this->recurring as $id => $entry) {
			if (($entry['last_execution'] + $entry['interval']) <= time()) {
				$this->loaded[$entry['plugin']]->$entry['function']($entry['args']);
				$this->recurring[$id]['last_execution'] = time();
			}
		}
	}

	/**
	 * Register a timed event
	 * @param string $plugin the registering plugin
	 * @param string $function method to call
	 * @param int $interval interval in seconds
	 **/
	public function register_timed($plugin, $function, $time, $args = NULL)
	{
		$this->timed[] = array('plugin' => $plugin,
				'function' => $function,
				'time' => $time,
				'args' => $args);
	}

	/**
	 * Run timed events
	 **/
	public function run_timed()
	{
		foreach ($this->timed as $id => $entry) {
			if ($entry['time'] <= time()) {
				$this->loaded[$entry['plugin']]->$entry['function']($entry['args']);
				unset($this->timed[$id]);
			}
		}
	}

	/**
	 * Register help text
	 * @param string $command which command do we document here
	 * @param string $help the help text
	 **/
	public function register_help($plugin, $command, $help)
	{
		$this->help[$plugin][$command] = $help;
	}
}

?>
