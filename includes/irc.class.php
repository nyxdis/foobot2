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
	 * @see bot::connect()
	 **/
	public function connect()
	{
		$this->send('USER ' . settings::$username . ' +i * :' . settings::$realname);
		$this->send('NICK ' . settings::$nick);
		for (;;) {
			$buf = $this->bot->read();
			if (strstr($buf, '001 ' . settings::$nick . ' :')) {
				$this->bot->log(DEBUG, 'Connected');
				$this->bot->connected = true;
				return true;
			}
		}
		return false;
	}

	/**
	 * @see bot::post_connect()
	 **/
	public function post_connect()
	{
		if (!empty (settings::$authpass)) {
			$this->bot->log(DEBUG, 'Authenticating');

			$authserv = settings::$authserv;
			$cmd = settings::$authcmd . ' ';

			if (!empty (settings::$authuser))
				$cmd .= settings::$authuser;

			$cmd .= settings::$authpass;
			$this->bot->say($authserv, $cmd);
		}
	}

	/**
	 * @see bot::join()
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
