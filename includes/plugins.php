<?php
/**
 * Plugin management
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Abstract class used by plugins
 *
 * @package foobot
 * @subpackage classes
 */
abstract class plugin_interface extends plugins
{
	/**
	 * This function is run when the plugin is loaded
	 */
	abstract public function init();

	/**
	 * Convenience function that sends 'Nick: text' to the channel where
	 * the event originated
	 * @param string $text the text to send
	 */
	protected final function answer($text)
	{
		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;

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
 */
class plugins
{
	/**
	 * Array of loaded plugins
	 * @var array
	 * @access private
	 */
	private static $loaded = array();

	/**
	 * Registered help texts
	 * @var array
	 * @access private
	 */
	private static $help = array();

	/**
	 * Registered events
	 * @var array
	 * @access private
	 */
	private static $events = array();

	/**
	 * Registered recurring events
	 * @var array
	 * @access private
	 */
	private static $recurring = array();

	/**
	 * Registered timed events
	 * @var array
	 * @access private
	 */
	private static $timed = array();

	/**
	 * Check if a plugin is loaded
	 * @return bool
	 * @param string $plugin name of the plugin
	 */
	public static final function is_loaded($plugin)
	{
		return (isset (self::$loaded[$plugin]));
	}

	/**
	 * Load a plugin
	 * @return bool success?
	 * @param string $plugin name of the plugin
	 */
	public static final function load($plugin)
	{
		$path = 'plugins/' . $plugin . '.php';
		if (!file_exists($path))
			return false;

		include $path;
		if (!class_exists($plugin) || !method_exists($plugin, 'load'))
			return false;

		$plug = new $plugin();
		$plug->init();
		self::$loaded[$plugin] = $plug;
		return true;
	}

	/**
	 * Enable a plugin (execute its init function), this is only needed
	 * after a plugin was disabled
	 * @param string $plugin name of the plugin
	 **/
	public static final function enable($plugin)
	{
		self::$loaded[$plugin]->init();
	}

	/**
	 * Disable a plugin (remove all of its registered events)
	 * @param string $plugin name of the plugin
	 * @todo set the state of the plugin instead of removing events
	 **/
	public static final function disable($plugin)
	{
		unset (self::$help[$plugin]);

		foreach (self::$events as $type => $event) {
			foreach ($event as $id => $data) {
				if ($data['plugin'] == $plugin) {
					unset (self::$events[$type][$id]);
				}
			}
		}

		foreach (self::$recurring as $id => $event)
			if ($event['plugin'] == $plugin)
				unset (self::$recurring[$id]);

		foreach (self::$timed as $id => $event)
			if ($event['plugin'] == $plugin)
				unset (self::$timed[$id]);
	}

	/**
	 * Register an event
	 * @param string $event what kind of event
	 * @param string $trigger optional trigger for the event (useful for text events)
	 * @param string $function method to call
	 */
	protected final function register_event($event, $trigger = NULL, $function = NULL, $level = 1)
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

		self::$events[$event][] = array('plugin' => get_class($this),
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
	 */
	public static final function run_event($event, $trigger = NULL, $argv = NULL)
	{
		$bot = bot::get_instance();

		$return = false;

		foreach (self::$events[$event] as $entry)
		{
			if ($entry['trigger'] &&
			  (($entry['trigger']{0} == '/' && !preg_match_all($entry['trigger'], $trigger, $preg_args, PREG_SET_ORDER)) ||
			    ($entry['trigger']{0} != '/' && $entry['trigger'] != $trigger)))
				continue;

			if ($entry['level'] > $bot->usr->level)
				continue;

			$return = true;

			$preg_match = false;
			if (isset ($preg_args) && !empty ($preg_args))
				$preg_match = true;
			elseif ($argv && !is_array($argv))
				$args = explode(' ', $argv);
			elseif (!$argv && $event != 'command')
				$args = explode(' ', $trigger);
			else
				$args = $argv;

			if ($event == 'command')
				$bot->log_cmd($bot->usr->name, $bot->channel, $entry['trigger'], $args);

			if ($preg_match) {
				foreach ($preg_args as $args)
					self::$loaded[$entry['plugin']]->$entry['function']($args);
			} else {
				self::$loaded[$entry['plugin']]->$entry['function']($args);
			}
		}

		return $return;
	}

	/**
	 * Register a recurring event
	 * @param string $function method to call
	 * @param int $interval interval in seconds
	 */
	protected final function register_recurring($function, $interval, $args = NULL)
	{
		self::$recurring[] = array('plugin' => get_class($this),
				'function' => $function,
				'interval' => $interval,
				'args' => $args,
				'last_execution' => 0);
	}

	/**
	 * Run recurring events
	 */
	public static final function run_recurring()
	{
		foreach (self::$recurring as $id => $entry) {
			if (($entry['last_execution'] + $entry['interval']) <= time()) {
				self::$loaded[$entry['plugin']]->$entry['function']($entry['args']);
				self::$recurring[$id]['last_execution'] = time();
			}
		}
	}

	/**
	 * Register a timed event
	 * @param string $function method to call
	 * @param int $interval interval in seconds
	 * @param mixed $args args passed to the callback function
	 */
	protected final function register_timed($function, $time, $args = NULL)
	{
		$db = db::get_instance();

		$sth = $db->prepare('INSERT INTO `timed_events`
					(`plugin`, `function`, `time`, `args`)
					VALUES(?, ?, ?, ?)');
		$sth->execute(array(get_class($this), $function, $time, serialize($args)));

		$id = $db->lastInsertId();

		self::$timed[] = array('plugin' => get_class($this),
				'function' => $function,
				'time' => $time,
				'args' => $args,
				'id' => $id);
	}

	/**
	 * Load timed events from db and register them
	 */
	public static final function load_timed()
	{
		$events = db::get_instance()->query('SELECT * FROM `timed_events`');
		while ($event = $events->fetchObject())
			self::$timed[] = array('plugin' => $event->plugin,
					'function' => $event->function,
					'time' => $event->time,
					'args' => unserialize($event->args),
					'id' => $event->id);
	}

	/**
	 * Run timed events
	 */
	public static final function run_timed()
	{
		foreach (self::$timed as $id => $entry) {
			if ($entry['time'] <= time()) {
				self::$loaded[$entry['plugin']]->$entry['function']($entry['args']);
				$sth = db::get_instance()->prepare('DELETE FROM `timed_events` WHERE `id` = ?');
				$sth->execute(array($entry['id']));
				unset (self::$timed[$id]);
			}
		}
	}

	/**
	 * Register help text
	 * @param string $command documented function
	 * @param string $help the help text
	 */
	protected final function register_help($command, $help)
	{
		self::$help[get_class($this)][strtolower($command)] = $help;
	}

	/**
	 * Return help strings
	 * @return mixed array of available plugins/functions or help string or false if not found
	 * @param string $plugin which plugin (returns all available plugins if empty)
	 * @param string $function which function (Returns all available functions if empty)
	 */
	public static final function get_help($plugin = NULL, $function = NULL)
	{
		if (!$plugin)
			return array_keys(self::$help);

		if (!$function) {
			if (isset (self::$help[$plugin]))
				return array_keys(self::$help[$plugin]);
			else
				return false;
		}

		if (isset (self::$help[$plugin][strtolower($function)]))
			return self::$help[$plugin][strtolower($function)];
		else
			return false;
	}
}

?>
