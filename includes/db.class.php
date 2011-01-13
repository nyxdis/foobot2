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
				$bot->write(settings::$debug_channel, $err->getMessage());
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
		$this->query('CREATE TABLE IF NOT EXISTS roulette (nick varchar(25) unique, survivals integer default 0, deaths integer default 0)');
		$this->query('CREATE TABLE IF NOT EXISTS karma (item varchar(50) unique, value integer default 0)');
		$this->query('CREATE TABLE IF NOT EXISTS karma_comments (item varchar(50), karma varchar(4), comment varchar(150))');
		$this->query('CREATE TABLE IF NOT EXISTS quotes (id integer primary key, text text, karma int)');
		$this->query('CREATE TABLE IF NOT EXISTS reminders (id integer primary key, target varchar(25), time int(11), text varchar(255))');
		$this->query('CREATE TABLE IF NOT EXISTS todo (nick varchar(25) unique, todo text)');
		$this->query('CREATE TABLE IF NOT EXISTS seen (nick varchar(25), ts int(11))');
		$this->query('CREATE TABLE IF NOT EXISTS events (id integer primary key, name varchar(50), date date)');
		$this->query('CREATE TABLE IF NOT EXISTS definitions (item varchar(50) unique, description text)');
		$this->query('CREATE TABLE IF NOT EXISTS larts (lart varchar(50))');
		$this->query('CREATE TABLE IF NOT EXISTS aliases (alias varchar(50), function varchar(50), args varchar(250))');
		$this->query('CREATE TABLE IF NOT EXISTS urls (id integer primary key, channel varchar(50), url varchar(250))');
	}
}

?>
