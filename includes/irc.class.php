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
	 * Message queue
	 * @access private
	 * @var array
	 */
	private $msg_queue = array();

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
			if (!strncmp($buf, 'PING :', 6))
			       $this->send('PONG ' . strstr($buf, ':'));
			if (strstr($buf, '001 ' . settings::$nick . ' :'))
				return true;
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
	 * @see communication::tick()
	 */
	public function tick() {
		$data = array_shift($this->msg_queue);
		$this->send($data);
	}

	/**
	 * @see bot::part()
	 */
	public function part($channel)
	{
		$this->send("PART $channel");
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
		$this->msg_queue[] = 'NOTICE ' . $target . ' :' . $text;
	}

	/**
	 * @see bot::say()
	 */
	public function say($target, $text, $prefix = "")
	{
		// TODO: properly calculate max length
		if (strlen($text) > 400) {
			$lines = wordwrap($text, 400, "\n", true);
			$lines = explode("\n", $lines);
			foreach ($lines as $line) {
				$this->say($target, $line, $prefix);
			}
		} else {
			$this->msg_queue[] = "PRIVMSG $target :$prefix$text";
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
