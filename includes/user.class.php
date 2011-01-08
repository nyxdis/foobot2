<?php
// TODO doc

class user
{
	public $id;
	public $name;
	public $level;
	public $nick;
	public $ident;
	public $host;
	public $title;
	public $location;
	public $tv_channels = "'prosieben.de','rtl.de','sat1.de'";

	public function __construct($nick, $ident, $host)
	{
		global $db;

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
