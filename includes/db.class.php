<?php
/**
 * database management
 *
 * This class provides a singleton to use for the bot's database
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * database management
 * @package foobot
 * @subpackage classes
 **/
class db extends PDO
{
	/**
	 * The class' instance
	 * @access private
	 * @var db
	 **/
	private static $instance = NULL;

	/**
	 * Class constructor, initializes the database
	 * @access private
	 **/
	public function __construct()
	{
		parent::__construct('sqlite:foobot-' . strtolower(settings::$network) . '.db');
		parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->initialize();
	}

	/**
	 * Use this function to get an instance
	 **/
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
	 **/
	public function query($sql)
	{
		$bot = bot::get_instance();

		try {
			$ret = parent::query($sql);
		} catch (PDOException $err) {
			if (!empty (settings::$debug_channel))
				$bot->say(settings::$debug_channel, $err->getMessage());
			return false;
		}
		return $ret;
	}

	/**
	 * Get a single property
	 * @return mixed the property or false
	 * @param string $sql the query
	 **/
	public function get_single_property($sql)
	{
		$r = $this->query($sql);
		if (!$r)
			return false;
		$r = $r->fetch(PDO::FETCH_NUM);
		return $r[0];
	}

	/**
	 * Initialize a new database
	 * @access private
	 **/
	private function initialize()
	{
		$this->query('CREATE TABLE IF NOT EXISTS users (id integer primary key, username varchar(25) unique, title varchar(25), ulvl integer, userdata varchar(150))');
		$this->query('CREATE TABLE IF NOT EXISTS hosts (usrid integer, ident varchar(10), host varchar(50))');
		$this->query('CREATE TABLE IF NOT EXISTS timed_events (id integer primary key, plugin varchar(25), function varchar(25), time int(11), args varchar(255))');
		$this->query('CREATE TABLE IF NOT EXISTS aliases (alias varchar(50), function varchar(50), args varchar(250))');
	}
}

?>
