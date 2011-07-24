<?php
/**
 * IRC protocol support
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * IRC class
 * @package foobot
 * @subpackage classes
 */
class irc implements communication
{
	/**
	 * Instance of the bot class
	 * @access private
	 */
	private $bot;

	/**
	 * Constructor, get a bot instance
	 */
	public function __construct()
	{
		$this->bot = bot::get_instance();
	}

	/**
	 * @see bot::connect()
	 */
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
	 */
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
	 */
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
	 */
	public function send($raw)
	{
		$this->bot->write($raw . LF);
	}

	/**
	 * @see bot::notice()
	 * @todo remove redundandy
	 */
	public function notice($target, $text)
	{
		$this->send('NOTICE ' . $target . ' :' . $text);
	}

	/**
	 * @see bot::say()
	 */
	public function say($target, $text)
	{
		if (strlen($text) > 500) {
			$lines = wordwrap($text, 500, "\n", true);
			$lines = explode("\n", $lines);
			foreach ($lines as $line) {
				$this->say($target, $line);
			}
		} else {
			$this->send('PRIVMSG ' . $target . ' :' . $text);
		}
	}

	/**
	 * @see bot::act()
	 */
	public function act($target, $text)
	{
		$this->say($target, "\001ACTION $text\001");
	}

	/**
	 * @see bot::quit()
	 */
	public function quit($msg)
	{
		$this->send('QUIT :' . $msg);
	}
}

?>
