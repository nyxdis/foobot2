<?php
/**
 * user management
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * user management
 * @package foobot
 * @subpackage classes
 */
class user
{
	/**
	 * Userid
	 * @var int
	 */
	public $id;

	/**
	 * Username
	 * @var string
	 */
	public $name;

	/**
	 * Userlevel
	 * @var int
	 */
	public $level;

	/**
	 * Current nickname
	 * @var string
	 */
	public $nick;

	/**
	 * Current ident
	 * @var string
	 */
	public $ident;

	/**
	 * Current hostname
	 * @var string
	 */
	public $host;

	/**
	 * Real name
	 * @var string
	 */
	public $realname;

	/**
	 * Title
	 * @var string
	 */
	public $title;

	/**
	 * Userdata for plugins
	 * @var array
	 */
	private $userdata = array();

	/**
	 * Class constructor
	 * @param string $nick Current nick
	 * @param string $ident Current ident
	 * @param string $host Current hostname
	 */
	public function __construct($nick = '', $ident = '', $host = '')
	{
		$db = db::get_instance();

		if (empty ($nick))
			return;

		$this->nick = $nick;
		$this->ident = $ident;
		$this->host = $host;

		$user = $db->query('SELECT *
				    FROM `users`
				    WHERE `id`=(SELECT `usrid`
						FROM `hosts`
						WHERE ? LIKE `ident` AND
						? LIKE `host` LIMIT 1)
				    LIMIT 1', $ident, $host);
		$user = $user->fetchObject();

		if (!$user)
			return false;
		$this->id = $user->id;
		$this->level = $user->ulvl;
		$this->name = $user->username;
		$this->title = $user->title;
		$this->userdata = unserialize($user->userdata);
	}

	/**
	 * Magic function that returns userdata values
	 * @param string $key
	 * @return string the value
	 */
	public function __get($prop)
	{
		if (isset ($this->userdata[$prop]))
			return $this->userdata[$prop];
		else
			return NULL;
	}

	/**
	 * Magic function that changes userdata values
	 * @param string $key
	 * @param string $value
	 */
	public function __set($key, $value)
	{
		$db = db::get_instance();

		if ($this->$key == $value)
			return;
		$this->userdata[$key] = $value;
		$db->query('UPDATE `users` SET `userdata` = ? WHERE `id` = ?', serialize($this->userdata), $this->id);
	}
}

?>
