<?php
/**
 * foobot core functions
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class core extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', "\001VERSION\001", 'ctcp_version');
		$this->register_event('command', "\001CHAT\001", 'ctcp_chat');
		$this->register_event('command', 'addhost');
		$this->register_event('command', 'adduser', NULL, 100);
		$this->register_event('command', 'alias', NULL, 5);
		$this->register_event('command', 'chlvl', NULL, 100);
		$this->register_event('command', 'gettz', NULL, 1);
		$this->register_event('command', 'getuserdata', NULL, 10);
		$this->register_event('command', 'help');
		$this->register_event('command', 'hi', NULL, 0);
		$this->register_event('command', 'ignore', NULL, 100);
		$this->register_event('command', 'unignore', NULL, 100);
		$this->register_event('command', 'join', NULL, 10);
		$this->register_event('command', 'enable' , 'pub_enable', 10);
		$this->register_event('command', 'disable' , 'pub_disable', 10);
		$this->register_event('command', 'load' , 'pub_load', 10);
		$this->register_event('command', 'merge', NULL, 100);
		$this->register_event('command', 'part', NULL, 10);
		$this->register_event('command', 'raw', NULL, 1000);
		$this->register_event('command', 'reboot', NULL, 100);
		$this->register_event('command', 'reload', NULL, 100);
		$this->register_event('command', 'settz', NULL, 1);
		$this->register_event('command', 'shutdown', NULL, 1000);
		$this->register_event('command', 'sql', NULL, 1000);
		$this->register_event('command', 'unalias', NULL, 5);
		$this->register_event('command', 'update', NULL, 1000);
		$this->register_event('command', 'version');
		$this->register_event('command', 'who');
		$this->register_event('command', 'whoami', NULL, 0);
		$this->register_event('command', 'whois');

		$this->register_help('addhost', 'add a host to your account');
		$this->register_help('adduser', 'add a user to the bot');
		$this->register_help('alias', 'alias <alias-name> <string> - add a command alias');
		$this->register_help('chlvl', 'change the level of some user');
		$this->register_help('getuserdata', 'retrieve the plugin user data');
		$this->register_help('gettz', 'show current timezone');
		$this->register_help('help', 'this help');
		$this->register_help('ignore', 'ignore all messages from a specific nick');
		$this->register_help('unignore', 'remove the ignore for the specified nick');
		$this->register_help('join', 'join a channel');
		$this->register_help('load', 'load a plugin');
		$this->register_help('merge', 'merge <username> <nickname> - add nickname\'s host to username');
		$this->register_help('part', 'part a channel');
		$this->register_help('raw', 'send raw IRC commands');
		$this->register_help('reboot', 'reboot the bot');
		$this->register_help('reload', 'reload settings');
		$this->register_help('settz', 'set your timezone');
		$this->register_help('shutdown', 'shut the bot down');
		$this->register_help('sql', 'send raw SQL commands');
		$this->register_help('unalias', 'remove aliases');
		$this->register_help('update', 'update the bot (requires git)');
		$this->register_help('version', 'print the bot version');
		$this->register_help('who', 'send a WHO command');
		$this->register_help('whoami', 'print information about your account');
		$this->register_help('whois', 'print information about some account');
	}

	public function ctcp_version($args)
	{
		$bot = bot::get_instance();
		$channel = $bot->channel;

		$version = BOT_VERSION;
		if (defined('GIT_REV'))
			$version .= '-' . GIT_REV;
		$bot->send('NOTICE ' . $channel . ' :' . chr(1) . 'VERSION foobot v' . $version . chr(1));
	}

	public function ctcp_chat($args)
	{
		$ip_addr = ip2long(gethostbyname(settings::$listen_addr));
		$port = settings::$dcc_port;

		parent::answer("\001DCC CHAT CHAT $ip_addr $port\001");
	}

	public function addhost($args)
	{
		$db = db::get_instance();
		$usr = bot::get_instance()->usr;

		if (isset ($args[1])) {
			$usrid = $db->get_single_property('SELECT `id` FROM `users` WHERE `username` = ?', $args[0]);
			if (!$usrid) {
				parent::answer('Unknown user');
				return;
			}
			$hostmask = $args[1];
		} else {
			$usrid = $usr->id;
			$hostmask = $args[0];
		}

		if (!preg_match('/\w+@.+/', $hostmask)) {
			parent::answer('Invalid format, use addhost ident@host');
			return;
		}
		$hostmask = explode('@', $hostmask);
		$db->query('INSERT INTO `hosts` (`usrid`, `ident`, `host`) VALUES(?, ?, ?)', (int)$usrid, $hostmask[0], $hostmask[1]);
		parent::answer('Added host');
	}

	public function adduser($args)
	{
		$bot = bot::get_instance();
		$db = db::get_instance();

		$nick = $args[0];
		$db->query('INSERT INTO `users` (`username`, `ulvl`) VALUES(?, 1)', $nick);
		$usrid = $db->lastInsertId();
		$user = $bot->get_userlist($nick);
		if (!$user->nick) {
			parent::answer('Unknown nick');
			return;
		}
		$db->query('INSERT INTO `hosts` (`usrid`, `ident`, `host`) VALUES(?, ?, ?)', (int)$usrid, $user->ident, $user->host);
		$bot->send('WHO ' . $user->nick);
		parent::answer('Added user ' . $usrid . ' identified by ' . $user->ident . '@' . $user->host);
	}

	public function alias($args)
	{
		$alias = strtolower(array_shift($args));
		$function = strtolower(array_shift($args));
		bot::get_instance()->register_alias($alias, $function, $args);
		parent::answer('Okay.');
	}

	public function chlvl($args)
	{
		$db = db::get_instance();
		$usr = bot::get_instance()->usr;

		if (count($args) != 2) {
			parent::answer('Usage: chlvl username/uid level');
			return;
		}

		if (!is_numeric($args[0])) {
			$user = $db->query('SELECT `id`, `ulvl` FROM `users` WHERE `username` = ?', $args[0])->fetchObject();
			if (!$user) {
				parent::answer('No such user');
				return;
			}
			$uid = $user->id;
			$ulvl = $user->ulvl;
		} else {
			$ulvl = $db->get_single_property('SELECT `ulvl` FROM `users` WHERE `id` = ?', (int)$args[0]);
			if (!$ulvl) {
				parent::answer('No such user');
				return;
			}
			$uid = $args[0];
		}

		if ($uid == $usr->id) {
			parent::answer('Unable to change your own level');
			return;
		}

		if ($ulvl >= $usr->level || $args[1] >= $usr->level) {
			parent::answer('Permission denied');
			return;
		}

		$db->query('UPDATE `users` SET `ulvl` = ? WHERE `id` = ?', (int)$args[1], (int)$uid);
		parent::answer('Okay');
	}

	public function gettz($args)
	{
		parent::answer(date_default_timezone_get());
	}

	public function getuserdata($args)
	{
		$db = db::get_instance();

		$username = $args[0];
		$info = $db->get_single_property('SELECT `userdata` FROM `users` WHERE `username` = ?', $username);
		if (!$info) {
			parent::answer('No such user');
			return;
		}
		$userdata = 'Userdata for ' . $username . ': ';
		$info = unserialize($info);
		foreach ($info as $key => $value)
			$userdata .= $key . ' = "' . $value . '"; ';

		$userdata = rtrim($userdata, '; ');
		parent::answer($userdata);
	}

	public function help($args)
	{
		if (empty ($args)) {
			$text = 'You can get help for the following plugins via "' . settings::$command_char . 'help <plugin>": ';
			$text .= implode(', ', $this->get_help());
		} elseif (count($args) == 1) {
			$help = $this->get_help($args[0]);
			if (!$help) {
				$text = 'No help found for plugin ' . $args[0];
			} else {
				$text = 'You can get help for the following functions via "' . settings::$command_char . 'help <plugin> <function>": ';
				$text .= implode(', ', $this->get_help($args[0]));
			}
		} else {
			$help = $this->get_help($args[0], $args[1]);
			if (!$help)
				$text = 'No help found for function ' . $args[1] . ' in ' . $args[0];
			else
				$text = $args[1] . ': ' . $this->get_help($args[0], $args[1]);
		}

		parent::answer($text);
	}

	public function hi($args)
	{
		$db = db::get_instance();
		$usr = bot::get_instance()->usr;

		$users = $db->get_single_property('SELECT COUNT(id) FROM users');
		if ($users > 0)
			return;
		$db->query('INSERT INTO `users` (`username`, `ulvl`) VALUES(?, 1000)', $usr->nick);
		$db->query('INSERT INTO `hosts` VALUES(?, ?, ?)', $db->lastInsertId(), $usr->ident, $usr->host);
		parent::answer('Hi, you are now my owner, recognized by ' . $usr->ident . '@' . $usr->host . '.');
	}

	public function ignore($args) {
		if (empty($args)) {
			parent::answer("Ignore who?");
		} else {
			bot::get_instance()->add_ignore($args[0]);
			parent::answer("Okay.");
		}
	}

	public function unignore($args) {
		if (empty($args)) {
			parent::answer("Unignore who?");
		} else {
			bot::get_instance()->remove_ignore($args[0]);
			parent::answer("Okay.");
		}
	}

	public function join($args)
	{
		$bot = bot::get_instance();

		$channel = $args[0];
		if (count($args) > 1)
			$key = $args[1];
		else
			$key = NULL;
		$bot->join($channel, $key);
	}

	public function merge($args)
	{
		$bot = bot::get_instance();
		$db = db::get_instance();
		$usr = $bot->usr;

		if (!isset ($args[1])) {
			parent::answer('Usage: merge username nickname');
			return;
		}

		$user = $bot->get_userlist($args[1]);
		if (!$user->nick) {
			parent::answer('Unknown nick');
			return;
		}

		$new_user = $db->query('SELECT `id`, `ulvl` FROM `users` WHERE `username` LIKE ?', $args[0])->fetchObject();
		if (!$new_user) {
			parent::answer('Unknown user');
			return;
		}

		if ($usr->level <= $new_user->ulvl) {
			parent::answer('Permission denied');
			return;
		}

		$db->query('INSERT INTO `hosts` VALUES(?, ?, ?)', $new_user->id, $user->ident, $user->host);
		$bot->send('WHO ' . $args[1]);
		parent::answer('Users merged');
	}

	public function part($args)
	{
		$bot = bot::get_instance();

		if (count($args) > 0)
			$channel = $args[0];
		else
			$channel = $bot->channel;
		$bot->part($channel);
	}

	public function raw($args)
	{
		$bot = bot::get_instance();

		$raw = implode(' ', $args);
		$bot->send($raw);
	}

	public function reboot($args)
	{
		global $argv;
		$bot = bot::get_instance();
		$usr = $bot->usr;

		exec('/usr/bin/env php ' . $_SERVER['PHP_SELF'] . ' ' . $argv[1] . ' >/dev/null &');
		$bot->shutdown('Rebooting as requested by ' . $usr->name);
	}

	public function reload($args) {
		$bot = bot::get_instance();

		settings::reload();
		$bot->send('NICK ' . settings::$nick);
		$bot->post_connect();

		parent::answer("Done.");
	}

	public function settz($args)
	{
		$usr = bot::get_instance()->usr;

		if (empty ($args)) {
			$tz = settings::$timezone;
		} else {
			$tz = timezone_name_from_abbr(implode(', ', $args));

			if ($tz === false) {
				parent::answer("Invalid timezone.");
				return;
			}
		}

		$usr->timezone = $tz;
		parent::answer("Done.");
	}

	public function shutdown($args)
	{
		$bot = bot::get_instance();
		$usr = $bot->usr;

		$bot->shutdown('Shutting down as requested by ' . $usr->name);
	}

	public function sql($args)
	{
		$db = db::get_instance();

		$sql = implode(' ', $args);
		try {
			$res = $db->query($sql, false);
		} catch(PDOException $err) {
			parent::answer('PDO exception: ' . $err->getMessage());
			return;
		}
		if (!strncasecmp($sql, 'SELECT ', 7)) {
			$headers = '';
			while ($result = $res->fetch(PDO::FETCH_ASSOC)) {
				$row = '';
				foreach ($result as $header => $field) {
					if (!isset ($headers_sent)) {
						$headers .= $header . ' | ';
					}
					$row .= $field . ' | ';
				}
				if (!isset ($headers_sent)) {
					trim($headers, ' | ');
					parent::answer($headers);
					$headers_sent = true;
				}
				trim($row, ' | ');
				parent::answer($row);
			}
		} elseif (!strncasecmp($sql, 'INSERT ', 7)) {
			parent::answer('Query executed, last insert id: ' . $db->lastInsertId());
		} else {
			parent::answer('Query executed');
		}
	}

	public function unalias($args)
	{
		$alias = strtolower($args[0]);
		bot::get_instance()->remove_alias($alias);
		parent::answer('Okay.');
	}

	public function update($args)
	{
		if (!file_exists('.git')) {
			parent::answer('Update without git currently not supported');
			return;
		}

		system('git pull', $ret);

		if ($ret != 0)
			parent::answer('Update failed');
		else
			parent::answer('Done. Don\'t forget to ' . settings::$command_char . 'reboot');
	}

	public function version($args)
	{
		$version = BOT_VERSION;
		if (defined('GIT_REV'))
			$version .= '-' . GIT_REV;
		parent::answer('This is foobot v' . $version);
	}

	public function who($args)
	{
		$bot = bot::get_instance();

		$nick = $args[0];
		if (settings::$protocol != 'irc') {
			parent::answer('Function not supported for current protocol');
			return;
		}
		$bot->send('WHO ' . $nick);
		parent::answer('Okay');
	}

	public function whoami($args)
	{
		$usr = bot::get_instance()->usr;

		if (isset ($usr->name)) {
			$string = 'You are ';
			if (isset ($usr->title))
				$string .= $usr->title . ' ';
			$string .= $usr->name . ', level ' . $usr->level;
		} else
			$string = 'You are unknown';
		parent::answer($string);
	}

	public function whois($args)
	{
		$bot = bot::get_instance();

		$nick = $args[0];
		$user = $bot->get_userlist($nick);
		if (!$user->id) {
			parent::answer($nick . ' is unknown');
			return;
		}

		$string = $nick . ' is ';
		if (isset ($user->title))
			$string .= $user->title . ' ';
		$string .= $user->name . ', level ' . $user->level;
		parent::answer($string);
	}

	public function pub_disable($args)
	{
		$plug = $args[0];

		if (!plugins::is_loaded($plug)) {
			parent::answer('Plugin is not loaded');
			return;
		}

		plugins::disable($plug);
		parent::answer('Disabled ' . $plug);
	}

	public function pub_enable($args)
	{
		$plug = $args[0];

		if (!plugins::is_loaded($plug)) {
			parent::answer('Plugin is not loaded');
			return;
		}

		plugins::enable($plug);
		parent::answer('Enabled ' . $plug);
	}

	public function pub_load($args)
	{
		$plug = $args[0];

		if (plugins::is_loaded($plug)) {
			parent::answer($plug . ' already loaded');
			return;
		}

		if (plugins::load($plug))
			parent::answer($plug . ' loaded');
		else
			parent::answer($plug . ' not found');
	}
}

?>
