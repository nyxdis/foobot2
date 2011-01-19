<?php
/**
 * Enemy Territory plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class et extends plugin_interface
{
	private $host;
	private $rcon_pass;

	public function load()
	{
		$this->register_event('command', 'et', 'pub_et');
		$this->register_event('command', 'etplayers');
		$this->register_event('command', 'etvar');
		$this->register_event('command', 'rcon', NULL, 10);
	}

	public function pub_et($args)
	{
		$et = $this->retrieve_server_info();
		if ((int)$et > 0) {
			parent::answer(socket_strerror($et));
			return;
		}
		$hostname = $et['info']['hostname'];
		$map = $et['info']['mapname'];
		$players = count($et['players']) - $et['status']['omnibot_playing'];
		$bots = $et['status']['omnibot_playing'];
		parent::answer($hostname . ' - Running ' . $map . ' (' . $players . ' human players, ' . $bots . ' bots)');
	}

	public function etplayers($args)
	{
		$et = $this->retrieve_server_info();
		if ((int)$et > 0) {
			parent::answer(socket_strerror($et));
			return;
		}
		$reply = 'Current players: ';
		foreach ($et['players'] as $player) {
			$reply .= $player['name'] . ', ';
		}
		parent::answer($reply);
	}

	public function etvar($args)
	{
		$var = $args[0];
		$et = $this->retrieve_server_info();
		if ((int)$et > 0) {
			parent::answer(socket_strerror($et));
			return;
		}
		$vars = array_merge($et['info'], $et['status']);
		if (isset ($vars[$var])) {
			parent::answer($var . ' = ' . $vars[$var]);
		} else {
			parent::answer($var . ' not set');
		}
	}

	public function rcon($args)
	{
		$cmd = implode(' ', $args);
		$et = $this->send_et_command('rcon ' . $this->rcon_pass . ' ' . $cmd);
		if ((int)$et > 0) {
			parent::answer(socket_strerror($et));
			return;
		}
		parent::answer('Okay');
	}

	private function send_et_command($cmd, $port = 27960) {
		$etsrv = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option($etsrv, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>1, 'usec'=>0));
		if (!socket_connect($etsrv, $this->host, $port))
			return socket_last_error();

		socket_write($etsrv, "\xFF\xFF\xFF\xFF$cmd\x00\r\n");
		$reply = socket_read($etsrv, 2048);
		socket_close($etsrv);
		return $reply;
	}

	private function retrieve_server_info($port = 27960) {
		$etsrv = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_set_option($etsrv, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>1, 'usec'=>0));
		if (!socket_connect($etsrv, $this->host, $port))
			return socket_last_error();

		socket_write($etsrv, "\xFF\xFF\xFF\xFFgetinfo\x00\r\n");
		$info = explode("\n", socket_read($etsrv, 2048));
		$info = $info[1];
		socket_write($etsrv, "\xFF\xFF\xFF\xFFgetstatus\x00\r\n");
		$players = explode("\n", trim(socket_read($etsrv, 2048)));
		array_shift($players);
		$status = $players[0];
		array_shift($players);
		socket_close($etsrv);

		$q3colors = array('^0', '^1', '^2', '^3', '^4', '^5', '^6', '^7');
		$irccolors = array("\00314", "\0034", "\0033", "\0038", "\0032", "\00312", "\0036", "\0030");
		foreach ($players as $id => $player) {
			$playerinfo = explode(' ', $player);
			$playername = substr(substr($playerinfo[2], 0, -1), 1);
			$playername = str_replace($q3colors, $irccolors, $playername)."\003";
			$players[$id] = array('scores' => $playerinfo[0], 'ping' => $playerinfo[1], 'name' => $playername);
		}

		eval ('$info = array('.rtrim(preg_replace('/\\\\([^\\\\]+)\\\\([^\\\\]+)/', '\'$1\'=>\'$2\',', $info), ',').');');
		eval ('$status = array('.rtrim(preg_replace('/\\\\([^\\\\]+)\\\\([^\\\\]+)/', '\'$1\'=>\'$2\',', $status), ',').');');
		return array('info' => $info, 'status' => $status, 'players' => $players);
	}
}

?>
