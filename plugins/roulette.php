<?php
/**
 * russian roulette
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class roulette extends plugin_interface
{
	private $lastplayer = array();
	private $last_game = array();
	private $deadly_chamber = array();
	private $roulette_mode = array();
	private $chamber = array();

	public function init()
	{
		$this->register_event('command', 'roulette', 'pub_roulette', 0);
		$this->register_event('command', 'roulette-chance', NULL, 0);
		$this->register_event('command', 'roulette-mode');
		$this->register_event('command', 'spin', NULL, 0);
		$this->register_event('command', 'stats', NULL, 0);

		db::get_instance()->query('CREATE TABLE IF NOT EXISTS roulette (nick varchar(25) unique, survivals integer default 0, deaths integer default 0)');
	}

	public function pub_roulette($args)
	{
		$bot = bot::get_instance();
		$db = db::get_instance();
		$channel = $bot->channel;
		$usr = $bot->usr;

		if (isset ($this->lastplayer[$channel]) && $this->lastplayer[$channel] == $usr->nick) {
			parent::answer('You\'re not going to play alone, are you?');
			return;
		}
		$this->lastplayer[$channel] = $usr->nick;
		$this->last_game[$channel] = time();

		if (!isset ($this->deadly_chamber[$channel]))
			$this->deadly_chamber[$channel] = rand(1, 6);

		if (isset ($this->roulette_mode[$channel]) && $this->roulette_mode[$channel] == 'spin') {
			if (rand(1, 6) == 1)
				$result = 'death';
			else
				$result = 'survival';

			$bot->act($channel, 'spins the cylinder');
		} else {
			if (isset ($this->chamber[$channel]))
				$this->chamber[$channel]++;
			else
				$this->chamber[$channel] = 1;
			if ($this->chamber[$channel] >= $this->deadly_chamber[$channel]) {
				$result = 'death';
				$this->deadly_chamber[$channel] = rand(1, 6);
				$this->chamber[$channel] = 0;
				$this->lastplayer[$channel] = false;
			} else
				$result = 'survival';
		}

		if ($result == 'death') {
			parent::answer('BANG!');
			$bot->act($channel, 'reloads the gun');
		} else {
			parent::answer('Click! You\'re lucky.');
		}

		if (!empty ($usr->name))
			$nick = $usr->name;
		else
			$nick = $usr->nick;
		$nick = $db->quote($nick);
		$cnt = $db->get_single_property('SELECT COUNT(`nick`) FROM `roulette` WHERE `nick` LIKE ' . $nick);
		if ($cnt > 0)
			$db->query('UPDATE `roulette` SET `' . $result . 's` = `' . $result . 's`+1 WHERE `nick` LIKE ' . $nick);
		else
			$db->query('INSERT INTO roulette (`nick`, `' . $result . 's`) VALUES(' . $nick . ', 1)');
	}

	public function roulette_chance($args)
	{
		$channel = bot::get_instance()->channel;

		if (isset ($this->roulette_mode[$channel]) && $this->roulette_mode[$channel] == 'spin') {
			parent::answer('Current probability of dying: 16.67 %');
		} else {
			if (!isset ($this->chamber[$channel]))
				$this->chamber[$channel] = 0;
			$chance = round(1 / (6 - $this->chamber[$channel]), 4)*100;
			parent::answer('Current probability of dying: ' . $chance . ' %');
		}
	}

	public function roulette_mode($args)
	{
		$channel = bot::get_instance()->channel;

		if (isset ($this->roulette_mode[$channel]) && $this->roulette_mode[$channel] == 'spin') {
			$this->roulette_mode[$channel] = 'dontspin';
			$this->deadly_chamber[$channel] = rand(1, 6);
		} else {
			$this->roulette_mode[$channel] = 'spin';
		}
		parent::answer('Switched roulette mode, ' . ($this->roulette_mode[$channel] == 'spin' ? '' : 'not ') . 'spinning before every shot now');
	}

	public function spin($args)
	{
		$bot = bot::get_instance();
		$channel = $bot->channel;

		if ($this->roulette_mode[$channel] == 'spin') {
			parent::answer('Command not available in this roulette mode');
			return;
		}

		if ($this->chamber[$channel] < 5 && $this->last_game[$channel] > (time() - 3600)) {
			parent::answer('Command not available at the moment');
			return;
		}

		$this->chamber[$channel] = 0;
		$this->deadly_chamber[$channel] = rand(1, 6);
		$this->lastplayer[$channel] = false;
		$bot->act($channel, 'spins the cylinder');
	}

	public function stats($args)
	{
		$usr = bot::get_instance()->usr;
		$db = db::get_instance();

		if (empty ($args)) {
			$stats = $db->query('SELECT nick, (survivals * 100 / (survivals + deaths)) AS survival_rate FROM roulette WHERE (survivals + deaths) >= 50 ORDER BY survival_rate DESC LIMIT 5');
			while ($stat = $stats->fetchObject()) {
				$top[$stat->nick] = $stat->survival_rate;
			}

			if (!isset ($top)) {
				parent::answer('Not enough games were played');
				return;
			}

			$i = 1;
			foreach ($top as $nick => $rate) {
				parent::answer('Top ' . $i . ': ' . $nick . ' (' . $rate . ' % survival rate)');
				$i++;
			}
		} else {
			$nick = $args[0];
			if ($nick == 'me')
				$nick = $usr->nick;
			if (isset ($usr->name) && $nick == $usr->nick)
				$nick = $usr->name;
			$stats = $db->query('SELECT * FROM roulette WHERE nick LIKE ' . $db->quote($nick))->fetchObject();
			if ($nick == $usr->nick || $nick == $usr->name)
				$nick = 'You';
			if (!$stats) {
				parent::answer($nick . ' didn\'t play Roulette yet');
				return;
			}
			$rate = ($stats->survivals / ($stats->survivals + $stats->deaths));
			parent::answer($nick . ' survived ' . $stats->survivals . ' game' . ($stats->survivals != 1 ? 's' : '') . ' and died in ' . $stats->deaths . ' game' . ($stats->deaths != 1 ? 's' : '') . ' (' . floor($rate*100) . ' % survival rate) . ');
		}
	}
}

?>
