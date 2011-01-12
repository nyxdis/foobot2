<?php
/**
 * foobot core functions
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class core extends plugin_interface
{
	public function load()
	{
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'command', "\001VERSION\001", 'ctcp_version');
		$plugins->register_event(__CLASS__, 'command', 'addhost');
		$plugins->register_event(__CLASS__, 'command', 'adduser', NULL, 100);
		$plugins->register_event(__CLASS__, 'command', 'alias', NULL, 5);
		$plugins->register_event(__CLASS__, 'command', 'chlvl', NULL, 100);
		$plugins->register_event(__CLASS__, 'command', 'getuserdata', NULL, 10);
		$plugins->register_event(__CLASS__, 'command', 'help');
		$plugins->register_event(__CLASS__, 'command', 'hi', NULL, 0);
		$plugins->register_event(__CLASS__, 'command', 'join', NULL, 10);
		$plugins->register_event(__CLASS__, 'command', 'load' , 'pub_load', 10);
		$plugins->register_event(__CLASS__, 'command', 'merge', NULL, 100);
		$plugins->register_event(__CLASS__, 'command', 'raw', NULL, 1000);
		$plugins->register_event(__CLASS__, 'command', 'reboot', NULL, 100);
		$plugins->register_event(__CLASS__, 'command', 'shutdown', NULL, 1000);
		$plugins->register_event(__CLASS__, 'command', 'sql', NULL, 1000);
		$plugins->register_event(__CLASS__, 'command', 'unalias', NULL, 5);
		$plugins->register_event(__CLASS__, 'command', 'version');
		$plugins->register_event(__CLASS__, 'command', 'who');
		$plugins->register_event(__CLASS__, 'command', 'whoami', NULL, 0);
		$plugins->register_event(__CLASS__, 'command', 'whois');
	}

	public function ctcp_version($args)
	{
		global $bot, $channel;

		$version = BOT_VERSION;
		if (file_exists('.git')) {
			$rev = file_get_contents('.git/refs/heads/master');
			$rev = substr($rev, 0, 7);
			$version .= '-' . $rev;
		}
		$bot->send('NOTICE ' . $channel . ' :' . chr(1) . 'VERSION foobot v' . $version . chr(1));
	}

	public function addhost($args)
	{
		global $usr, $db;

		if (isset ($args[1])) {
			$usrid = $db->get_single_property('SELECT `id` FROM `users` WHERE `username` = ' . $db->quote($args[0]));
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
		$db->query('INSERT INTO `hosts` (`usrid`, `ident`, `host`) VALUES(' . (int)$usrid . ', ' . $db->quote($hostmask[0]) . ', ' . $db->quote($hostmask[1]) . ')');
		parent::answer('Added host');
	}

	public function adduser($args)
	{
		global $bot, $db;

		$nick = $args[0];
		$db->query('INSERT INTO `users` (`username`, `ulvl`) VALUES(' . $db->quote($nick) . ', 1)');
		$usrid = $db->lastInsertId();
		$db->query('INSERT INTO `hosts` (`usrid`, `ident`, `host`) VALUES(' . (int)$usrid . ', ' . $db->quote($bot->userlist[$nick]['ident']) . ', ' . $db->quote($bot->userlist[$nick]['host']) . ')');
		$bot->userlist[$nick]['usr'] = new user($nick, $bot->userlist[$nick]['ident'], $bot->userlist[$nick]['host']);
		parent::answer('Added user ' . $usrid . ' identified by ' . $bot->userlist[$nick]['ident'] . '@' . $bot->userlist[$nick]['host']);
	}

	public function alias($args)
	{
		global $alias;

		// TODO
		parent::answer('Not yet implemented');
		return;
		$args = explode(' ', str_replace('-', '_', strtolower($args)));
		if (!isset ($args[1])) {
			parent::answer('wat');
			return;
		}
		if (method_exists('bot_functions', 'pub_' . $args[0]) && method_exists('bot_functions', 'pub_' . $args[1])) {
			parent::answer('Cannot overwrite function names');
			return;
		}
		if (!method_exists('bot_functions', 'pub_' . $args[0]) && !method_exists('bot_functions', 'pub_' . $args[1])) {
			parent::answer('Unknown function');
			return;
		}

		if (method_exists('bot_functions', 'pub_' . $args[0])) {
			$newalias = $args[1];
			$function = $args[0];
		} else {
			$newalias = $args[0];
			$function = $args[1];
		}
		if (substr($newalias, -2) == '++' || substr($newalias, -2) == '--' || substr($newalias, -1) == '?') {
			parent::answer('Nope.');
			return;
		}
		array_shift($args);
		array_shift($args);
		$args = implode(' ', $args);

		if (isset ($alias[$newalias])) {
			$db->query('UPDATE aliases SET function=' . $db->quote($function) . ', args=' . $db->quote($args) . ' WHERE alias=' . $db->quote($newalias));
			$reply = 'Alias overwritten';
		} else {
			$db->query('INSERT INTO aliases VALUES(' . $db->quote($newalias) . ', ' . $db->quote($function) . ', ' . $db->quote($args) . ')');
			$reply = 'Alias added';
		}
		$alias[$newalias]['function'] = $function;
		$alias[$newalias]['args'] = $args;
		parent::answer($reply);
	}

	public function chlvl($args)
	{
		global $usr, $db;

		if (count($args) != 2) {
			parent::answer('Usage: chlvl username/uid level');
			return;
		}

		if (!is_numeric($args[0])) {
			$user = $db->query('SELECT `id`, `ulvl` FROM `users` WHERE `username` = ' . $db->quote($args[0]))->fetchObject();
			if (!$user) {
				parent::answer('No such user');
				return;
			}
			$uid = $user->id;
			$ulvl = $user->ulvl;
		} else {
			$ulvl = $db->get_single_property('SELECT `ulvl` FROM `users` WHERE `id` = ' . (int)$args[0]);
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

		$db->query('UPDATE `users` SET `ulvl` = ' . (int)$args[1] . ' WHERE `id` = ' . (int)$uid);
		parent::answer('Okay');
	}

	public function getuserdata($args)
	{
		global $db;

		$username = $args[0];
		$info = $db->get_single_property('SELECT `userdata` FROM `users` WHERE `username` = ' . $db->quote($username));
		if (!$info) {
			parent::answer('No such user');
			return;
		}
		$userdata = 'Userdata for ' . $username . ': ';
		$info = unserialize($info);
		foreach ($info as $key => $value)
			$userdata .= $key . '=' . $value . '; ';

		$userdata = rtrim($userdata, '; ');
		parent::answer($userdata);
	}

	public function help($args)
	{
		global $settings;

		$plugins = plugins::get_instance();

		if (empty ($args)) {
			$text = 'You can get help for the following plugins via "' . $settings['command_char'] . 'help <plugin>": ';
			$text .= implode(', ', $plugins->get_help());
		} elseif (count($args) == 1) {
			$help = $plugins->get_help($args[0]);
			if (!$help) {
				$text = 'No help found for plugin ' . $args[0];
			} else {
				$text = 'You can get help for the following functions via "' . $settings['command_char'] . 'help <plugin> <function>": ';
				$text .= implode(', ', $plugins->get_help($args[0]));
			}
		} else {
			$help = $plugins->get_help($args[0], $args[1]);
			if (!$help)
				$text = 'No help found for function ' . $args[1] . ' in ' . $args[0];
			else
				$text = $args[1] . ': ' . $plugins->get_help($args[0], $args[1]);
		}

		parent::answer($text);
	}

	public function hi($args)
	{
		global $usr, $db;

		$users = $db->get_single_property('SELECT COUNT(id) FROM users');
		if ($users > 0)
			return;
		$db->query('INSERT INTO `users` (`username`, `ulvl`) VALUES(\'' . $usr->nick . '\', 1000)');
		$db->query('INSERT INTO `hosts` VALUES(' . $db->lastInsertID() . ', \'' . $usr->ident . '\', \'' . $usr->host . '\')');
		parent::answer('Hi, you are now my owner, recognized by ' . $usr->ident . '@' . $usr->host . '.');
	}

	public function join($args)
	{
		global $bot;

		$channel = $args[0];
		$bot->join($channel);
	}

	public function merge($args)
	{
		global $bot, $db;

		if (!isset ($args[1])) {
			parent::answer('Usage: merge username nickname');
			return;
		}

		if (!isset ($bot->userlist[$args[1]])) {
			parent::answer('Unknown nick');
			return;
		}

		$usrid = $db->get_single_property('SELECT `id` FROM `users` WHERE `username` LIKE ' . $db->quote($args[0]));
		if (!$usrid) {
			parent::answer('Unknown user');
			return;
		}

		$db->query('INSERT INTO `hosts` VALUES(' . $usrid . ', ' . $db->quote($bot->userlist[$args[1]]['ident']) . ', ' . $db->quote($bot->userlist[$args[1]]['host']) . ')');
		$bot->send('WHO ' . $args[1]);
		parent::answer('Users merged');
	}

	public function raw($args)
	{
		global $bot;

		$raw = implode(' ', $args);
		$bot->send($raw);
	}

	public function reboot($args)
	{
		global $argv, $bot, $usr;

		$bot->shutdown('Rebooting as requested by ' . $usr->name);
		exec('/usr/bin/env php ' . $_SERVER['PHP_SELF'] . ' ' . $argv[1] . ' >/dev/null &');
		exit;
	}

	public function shutdown($args)
	{
		global $bot, $usr;

		$bot->shutdown('Shutting down as requested by ' . $usr->name);
		exit;
	}

	public function sql($args)
	{
		global $db;

		$sql = implode(' ', $args);
		try {
			$res = $db->query($sql);
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
		global $alias;

		// TODO
		parent::answer('Not yet implemented');
		return;
		$a = str_replace('-', '_', $a);
		if (!isset ($alias[$a])) {
			parent::answer('No such alias');
			return;
		}
		unset ($alias[$a]);
		$db->query('DELETE FROM aliases WHERE alias=' . $db->quote($a));
		parent::answer('Alias removed');
	}

	public function version($args)
	{
		$version = BOT_VERSION;
		if (file_exists('.git')) {
			$rev = file_get_contents('.git/refs/heads/master');
			$rev = substr($rev, 0, 7);
			$version .= '-' . $rev;
		}
		parent::answer('This is foobot v' . $version);
	}

	public function who($args)
	{
		global $bot, $settings;

		$nick = $args[0];
		if ($settings['protocol'] != 'irc') {
			parent::answer('Function not supported for current protocol');
			return;
		}
		$bot->send('WHO ' . $nick);
		parent::answer('Okay');
	}

	public function whoami($args)
	{
		global $usr;

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
		global $bot;

		$nick = $args[0];
		if (!$bot->userlist[$nick]['usr']->name) {
			parent::answer($nick . ' is unknown');
			return;
		}
		$user = $bot->userlist[$nick]['usr'];

		$string = $nick . ' is ';
		if (isset ($user->title))
			$string .= $user->title . ' ';
		$string .= $user->name . ', level ' . $user->level;
		parent::answer($string);
	}

	public function pub_load($args)
	{
		$plug = $args[0];

		$plugins = plugins::get_instance();
		if ($plugins->is_loaded($plug)) {
			parent::answer($plug . ' already loaded');
			return;
		}

		if ($plugins->load($plug))
			parent::answer($plug . ' loaded');
		else
			parent::answer($plug . ' not found');
	}
}

?>
