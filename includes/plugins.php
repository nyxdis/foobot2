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
		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;

		$bot = bot::get_instance();
		if ($usr->nick != $channel)
			$text = $usr->nick . ': ' . $text;
		$bot->say($channel, $text);
	}

	public function register_event($event, $trigger = NULL, $function = NULL, $level = 1)
	{
		plugins::get_instance()->register_event(get_class($this), $event, $trigger, $function, $level);
	}

	public function register_recurring($function, $interval, $args = NULL)
	{
		plugins::get_instance()->register_recurring(get_class($this), $function, $interval, $args);
	}

	public function register_timed($function, $time, $args = NULL)
	{
		plugins::get_instance()->register_timed(get_class($this), $function, $time, $args);
	}

	public function register_help($command, $help)
	{
		plugins::get_instance()->register_help(get_class($this), $command, $help);
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
	 * Check if a plugin is loaded
	 * @return bool
	 * @param string $plugin name of the plugin
	 **/
	public function is_loaded($plugin)
	{
		if (isset ($this->loaded[$plugin]))
			return true;
		return false;
	}

	/**
	 * Load a plugin
	 * @return bool success?
	 * @param string $plugin name of the plugin
	 **/
	public function load($plugin)
	{
		$path = 'plugins/' . $plugin . '.php';
		if (!file_exists($path))
			return false;

		include $path;
		if (!class_exists($plugin) || !method_exists($plugin, 'load'))
			return false;

		$plug = new $plugin();
		$plug->load();
		$this->loaded[$plugin] = $plug;
		return true;
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

		if ($event == 'command')
			$trigger = strtolower($trigger);

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
		$bot = bot::get_instance();

		$return = false;

		foreach ($this->events[$event] as $entry)
		{
			if ($entry['trigger'] &&
			  (($entry['trigger']{0} == '/' && !preg_match($entry['trigger'], $trigger, $preg_args)) ||
			    ($entry['trigger']{0} != '/' && $entry['trigger'] != $trigger)))
				continue;

			if ($entry['level'] > $bot->usr->level)
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
				$bot->log_cmd($bot->usr->name, $bot->channel, $entry['trigger'], $args);

			$this->loaded[$entry['plugin']]->$entry['function']($args);
		}

		return $return;
	}

	/**
	 * Register a recurring event
	 * @param string $plugin the registering plugin
	 * @param string $function method to call
	 * @param int $interval interval in seconds
	 * @todo save to db
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
	 * @param mixed $args args passed to the callback function
	 **/
	public function register_timed($plugin, $function, $time, $args = NULL, $id = 0)
	{
		if ($id == 0) {
			$db = db::get_instance();

			$db->query('INSERT INTO `timed_events` (`plugin`, `function`, `time`, `args`)
					VALUES(' . $db->quote($plugin) . ',
						' . $db->quote($function) . ',
						' . (int)$time . ',
						' . $db->quote(serialize($args)) . ')');

			$id = $db->lastInsertId();
		}

		$this->timed[] = array('plugin' => $plugin,
				'function' => $function,
				'time' => $time,
				'args' => $args,
				'id' => $id);
	}

	/**
	 * Load timed events from db and register them
	 **/
	public function load_timed()
	{
		$events = db::get_instance()->query('SELECT * FROM `timed_events`');
		while ($event = $events->fetchObject())
			$this->register_timed($event->plugin, $event->function, $event->time, unserialize($event->args), $event->id);
	}

	/**
	 * Run timed events
	 **/
	public function run_timed()
	{
		foreach ($this->timed as $id => $entry) {
			if ($entry['time'] <= time()) {
				$this->loaded[$entry['plugin']]->$entry['function']($entry['args']);
				db::get_instance()->query('DELETE FROM `timed_events` WHERE `id` = ' . (int)$entry['id']);
				unset ($this->timed[$id]);
			}
		}
	}

	/**
	 * Register help text
	 * @param string $plugin documented plugin
	 * @param string $command documented function
	 * @param string $help the help text
	 **/
	public function register_help($plugin, $command, $help)
	{
		$this->help[$plugin][strtolower($command)] = $help;
	}

	/**
	 * Return help strings
	 * @return mixed array of available plugins/functions or help string or false if not found
	 * @param string $plugin which plugin (returns all available plugins if empty)
	 * @param string $function which function (Returns all available functions if empty)
	 **/
	public function get_help($plugin = NULL, $function = NULL)
	{
		if (!$plugin)
			return array_keys($this->help);

		if (!$function) {
			if (isset ($this->help[$plugin]))
				return array_keys($this->help[$plugin]);
			else
				return false;
		}

		if (isset ($this->help[$plugin][strtolower($function)]))
			return $this->help[$plugin][strtolower($function)];
		else
			return false;
	}
}

?>
