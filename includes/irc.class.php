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
	private $bot;

	public function __construct()
	{
		$this->bot = bot::get_instance();
	}

	public function connect()
	{
		$this->bot->send('USER ' . $settings['username'] . ' +i * :' . $settings['realname']);
		$this->bot->send('NICK ' . $settings['nick']);
		for (;;) {
			$buf = $this->bot->read();
			if (strstr($buf, '001 ' . $settings['nick'] . ' :')) {
				$this->bot->log(DEBUG, 'Connected');
				$this->bot->connected = true;
				return true;
			}
		}
	}

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
			$this->bot->write($authserv, $cmd);
		}

		if (isset ($settings['operpass'])) {
			$cmd = 'OPER ';
			if (isset ($settings['operuser']))
				$cmd .= $settings['operuser'] . ' ';
			$cmd .= $settings['operpass'];
		}
	}

	public function join($channel, $key)
	{
		$cmd = 'JOIN ' . $channel;
		if ($key)
			$cmd .= ' ' . $key;
		$this->bot->send($cmd);
		$this->bot->send('WHO ' . $channel);
	}
}

?>
