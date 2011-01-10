<?php
/**
 * user management
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * user management
 * @package foobot
 * @subpackage classes
 **/
class user
{
	/**
	 * Userid
	 * @var int
	 **/
	public $id;

	/**
	 * Username
	 * @var string
	 **/
	public $name;

	/**
	 * Userlevel
	 * @var int
	 **/
	public $level;

	/**
	 * Current nickname
	 * @var string
	 **/
	public $nick;

	/**
	 * Current ident
	 * @var string
	 **/
	public $ident;

	/**
	 * Current hostname
	 * @var string
	 **/
	public $host;

	/**
	 * Title
	 * @var string
	 **/
	public $title;

	/**
	 * Location (TODO move to google plugin)
	 * @var string
	 **/
	public $location;

	/**
	 * Personal TV channel list (TODO move to tv plugin)
	 * @var string
	 **/
	public $tv_channels = "'prosieben.de','rtl.de','sat1.de'";

	/**
	 * Class constructor
	 * @param string $nick Current nick
	 * @param string $ident Current ident
	 * @param string $host Current hostname
	 **/
	public function __construct($nick = '', $ident = '', $host = '')
	{
		global $db;

		if (empty ($nick))
			return;

		$this->nick = $nick;
		$this->ident = $ident;
		$this->host = $host;

		$user = $db->query('SELECT *
				    FROM `users`
				    WHERE `id`=(SELECT `usrid`
						FROM `hosts`
						WHERE ' . $db->quote($ident) . ' LIKE `ident` AND
						' . $db->quote($host) . ' LIKE `host` LIMIT 1) LIMIT 1')->fetchObject();
		if (!$user)
			return false;
		$this->id = $user->id;
		$this->level = $user->ulvl;
		$this->name = $user->username;
		$this->title = $user->title;
		$userdata = unserialize($user->userdata);
		if (isset ($userdata['location']))
			$this->location = $userdata['location'];
		if (isset ($userdata['tv_channels']))
			$this->tv_channels = $userdata['tv_channels'];
	}

	/**
	 * Change userdata
	 * @param string $key
	 * @param string $value
	 **/
	function update_userdata($key, $value)
	{
		global $db;

		if ($this->$key == $value)
			return;
		$userdata[$key] = $value;
		$this->$key = $value;
		$db->query('UPDATE `users` SET `userdata`=' . $db->quote(serialize($userdata)) . ' WHERE `id`=' . (int)$this->id);
	}
}

?>
