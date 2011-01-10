<?php
/**
 * IRC protocol support
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * IRC class
 * @package foobot
 * @subpackage classes
 **/
class irc implements communication
{
	/**
	 * Instance of the bot class
	 * @access private
	 **/
	private $bot;

	/**
	 * Constructor, get a bot instance
	 **/
	public function __construct()
	{
		$this->bot = bot::get_instance();
	}

	/**
	 * Connect to IRC server
	 * @return bool
	 **/
	public function connect()
	{
		global $settings;

		$this->send('USER ' . $settings['username'] . ' +i * :' . $settings['realname']);
		$this->send('NICK ' . $settings['nick']);
		for (;;) {
			$buf = $this->bot->read();
			if (strstr($buf, '001 ' . $settings['nick'] . ' :')) {
				$this->bot->log(DEBUG, 'Connected');
				$this->bot->connected = true;
				return true;
			}
		}
		return false;
	}

	/**
	 * Commands to execute after successful connection
	 **/
	public function post_connect()
	{
		global $settings;

		if (isset ($settings['authpass'])) {
			$this->bot->log(DEBUG, 'Authenticating');

			if (!isset ($settings['authserv']))
				$authserv = 'NickServ';
			else
				$authserv = $settings['authserv'];

			if (!isset ($settings['authcmd']))
				$cmd = 'identify ';
			else
				$cmd = $settings['authcmd'] . ' ';

			if (isset ($settings['authuser']))
				$cmd .= $settings['authuser'];

			$cmd .= $settings['authpass'];
			$this->bot->say($authserv, $cmd);
		}

		if (isset ($settings['operpass'])) {
			$cmd = 'OPER ';
			if (isset ($settings['operuser']))
				$cmd .= $settings['operuser'] . ' ';
			$cmd .= $settings['operpass'];
		}
	}

	/**
	 * Join a channel
	 * @param string $channel the channel to join
	 * @param string $key key of the channel
	 **/
	public function join($channel, $key)
	{
		$cmd = 'JOIN ' . $channel;
		if ($key)
			$cmd .= ' ' . $key;
		$this->send($cmd);
		$this->send('WHO ' . $channel);
	}

	/**
	 * @see bot::send()
	 **/
	public function send($raw)
	{
		$this->bot->write($raw . LF);
	}

	/**
	 * @see bot::say()
	 **/
	public function say($target, $text)
	{
		$send = 'PRIVMSG ' . $target . ' :' . $text;
		if (strlen($send) > 412) {
			$text = str_split($text, 402 - strlen($channel));
			foreach ($text as $t)
				$this->say($target, $t);
		} else {
			$this->send($send);
		}
	}

	/**
	 * @see bot::act()
	 **/
	public function act($target, $text)
	{
		$this->say($target, "\001ACTION $text\001");
	}
}

?>
