<?php
/**
 * seen plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class seen extends plugin_interface
{
	private $seen = array();

	public function init()
	{
		$this->register_event('text', NULL, 'seen_save');
		$this->register_event('command', 'seen', 'pub_seen');
		$this->register_event('shutdown', NULL, 'save');
		$this->register_recurring('save', 3600);
		$this->register_help('seen', 'when was a specific user online? only saves information about known users');

		db::get_instance()->query('CREATE TABLE IF NOT EXISTS seen (nick varchar(25), ts int(11))');

		$seen = db::get_instance()->query('SELECT * FROM seen');
		while($entry = $seen->fetchObject())
				$this->seen[$entry->nick] = $entry->ts;
	}

	public function seen_save($args)
	{
		$usr = bot::get_instance()->usr;

		$this->seen[$usr->name] = time();
		$this->seen[$usr->nick] = time();
	}

	public function pub_seen($args)
	{
		$usr = bot::get_instance()->usr;

		if (!isset ($args[0])) {
			parent::answer('Seen who?');
			return;
		}
		$nick = $args[0];
		if ($nick == $usr->nick || $nick == $usr->name) {
			parent::answer('That\'s you!');
			return;
		}
		if ($nick == settings::$nick) {
			parent::answer('That\'s me!');
			return;
		}
		if (!isset ($this->seen[$nick])) {
			parent::answer('I\'ve never seen ' . $nick);
			return;
		}

		$secs = (time() - $this->seen[$nick]);

		// Christian Stigen Larsen's secs_to_h() -- http://csl.sublevel3.org/php-secs-to-human-text/
		$units = array(
			"week"	 => 7*24*3600,
			"day"	 =>   24*3600,
			"hour"	 =>	 3600,
			"minute" =>	   60,
			"second" =>	    1,
		);

		// specifically handle zero
		if ($secs < 60) {
			parent::answer("$nick is online right now.");
			return;
		}

		$s = "";

		foreach ($units as $name => $divisor) {
			if ($quot = intval($secs / $divisor)) {
				$s .= "$quot $name";
				$s .= (abs($quot) > 1 ? "s" : "") . ", ";
				$secs -= $quot * $divisor;
			}
		}

		$result = substr($s, 0, -2);


		parent::answer($nick . ' was last seen ' . $result . ' ago');
	}

	public function save($args)
	{
		if (empty ($this->seen))
			return;

		$db = db::get_instance();

		$db->query('DELETE FROM seen');
		foreach ($this->seen as $nick => $time)
			$db->query('INSERT INTO `seen` (`nick`, `ts`) VALUES(?, ?)', $nick, $time);
	}
}

?>
