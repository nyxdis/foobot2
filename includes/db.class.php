<?php
/**
 * database management
 *
 * This class provides a singleton to use for the bot's database
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * database management
 * @package foobot
 * @subpackage classes
 */
class db extends PDO
{
	/**
	 * The class' instance
	 * @access private
	 * @var db
	 */
	private static $instance = NULL;

	/**
	 * Class constructor, initializes the database
	 * @access private
	 */
	public function __construct()
	{
		parent::__construct('sqlite:foobot-' . strtolower(settings::$network) . '.db');
		parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->initialize();
	}

	/**
	 * Use this function to get an instance
	 */
	public static function get_instance()
	{
		if (self::$instance == NULL)
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Execute a database query
	 * @return mixed PDO result or false
	 * @param string $sql the query
	 */
	public function query($sql, $catch_exception = true)
	{
		$bot = bot::get_instance();

		$args = func_get_args();
		$sql = array_shift($args);
		if (isset($args[0]) && is_bool($args[0]))
			$catch_exception = array_shift($args);

		$sth = parent::prepare($sql);

		if ($catch_exception) {
			try {
				$sth->execute($args);
			} catch (PDOException $err) {
				if (!empty (settings::$debug_channel))
					$bot->say(settings::$debug_channel, $err->getMessage());
				return false;
			}
		} else {
			$sth->execute($args);
		}

		return $sth;
	}

	/**
	 * Get a single property
	 * @return mixed the property or false
	 * @param string $sql the query
	 */
	public function get_single_property()
	{
		if (func_num_args() == 1) {
			$r = $this->query($sql)->fetch(PDO::FETCH_NUM);
		} elseif (func_num_args() > 1) {
			$args = func_get_args();
			$sql = array_shift($args);
			$sth = $this->prepare($sql);
			$sth->execute($args);
			$r = $sth->fetch(PDO::FETCH_NUM);
		} else {
			trigger_error('db::get_single_property() expects at least one argument');
			$r = false;
		}

		if (!$r)
			return false;
		return $r[0];
	}

	/**
	 * Initialize a new database
	 * @access private
	 */
	private function initialize()
	{
		$this->query('CREATE TABLE IF NOT EXISTS users (id integer primary key, username varchar(25) unique, title varchar(25), ulvl integer, userdata varchar(150))');
		$this->query('CREATE TABLE IF NOT EXISTS hosts (usrid integer, ident varchar(10), host varchar(50))');
		$this->query('CREATE TABLE IF NOT EXISTS timed_events (id integer primary key, plugin varchar(25), function varchar(25), time int(11), args varchar(255))');
		$this->query('CREATE TABLE IF NOT EXISTS aliases (id integer primary key, alias varchar(50), function varchar(50), args varchar(250))');
	}
}

?>
