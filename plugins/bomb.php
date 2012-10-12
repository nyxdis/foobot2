<?php
/**
 * bom plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * BOOM
 * @package foobot
 * @subpackage plugins
 */
class bomb extends plugin_interface
{
	private $nicks = array(array());
	private $target = array();
	private $defuse_color = array();
	private $kill_collor = array();
	private $colors = array();

	public function init()
	{
		$this->register_event('text', NULL, 'nick_save');
		$this->register_event('command', 'bomb', 'pub_bomb');
		$this->register_recurring('remove_inactive', 60);
		$this->register_recurring('random_bomb_timer', 300);
		$this->register_help('bomb', 'BOOM');
	}

	public function nick_save($args)
	{
		$usr = bot::get_instance()->usr;
		$channel = bot::get_instance()->channel;
		$this->nicks[$channel][$usr->nick] = time();

		if (empty($args))
			return;

		$arg = strtolower($args[0]);
		if (isset($this->target[$channel]) && in_array($arg, $this->colors[$channel])) {
			$usr = bot::get_instance()->usr;

			if ($this->target[$channel] != $usr->nick)
				return;

			$timeleft = $this->timer[$channel] - time() + $this->start[$channel];
			if ($arg == $this->defuse_color[$channel]) {
				parent::answer("Great job!  You defused the bomb with $timeleft seconds on the timer.");
				unset($this->target[$channel]);
			} else if ($arg == $this->kill_color[$channel]) {
				bot::get_instance()->send("KICK $channel {$usr->nick} :BOOM!  The $arg wire was a trap!");
				unset($this->target[$channel]);
			} else {
				parent::answer("The $arg wire was a decoy.  The clock is still ticking.  Try again.");
				unset($this->colors[$channel][$arg]);
				bot::get_instance()->say($channel, "{$this->target[$channel]}: The timer reads {$timeleft} seconds. Cut one of the following wires: " . implode(', ', $this->colors[$channel]));
			}
		}
	}

	private function random_bomb()
       	{
		$channel = bot::get_instance()->channel;

		if (empty($this->nicks[$channel])) {
			return;
		}

		$nick = array_rand($this->nicks[$channel]);

		$this->pub_bomb(array($nick));
	}

	public function random_bomb_timer($args)
	{
		if (mt_rand(0, 100) == 0)
			$this->random_bomb();
	}

	public function pub_bomb($args)
       	{
		$channel = bot::get_instance()->channel;

		if (isset($this->target[$channel])) {
			parent::answer("{$this->target[$channel]} is holding a bomb already!");
			return;
		}

		$colors = array('black', 'blue', 'brown', 'gray', 'green', 'orange', 'red', 'white', 'yellow');

		if (empty($args)) {
			$this->random_bomb();
			return;
		}

		$this->target[$channel] = $args[0];

		$tmp_colors = $colors;
		shuffle($tmp_colors);
		$num_colors = mt_rand(3, count($colors));

		$wire_colors = array();
		for ($i = 0; $i < $num_colors; $i++) {
			$color = array_pop($colors);
			$wire_colors[$color] = $color;
		}
		$this->colors[$channel] = $wire_colors;

		$this->defuse_color[$channel] = array_pop($wire_colors);
		$this->kill_color[$channel] = array_pop($wire_colors);

		$this->timer[$channel] = mt_rand(10, 60);
		$this->start[$channel] = time();

		$bot = bot::get_instance();
		$bot->act($channel, "throws a bomb at {$this->target[$channel]}");
		$bot->say($channel, "{$this->target[$channel]}: The timer reads {$this->timer[$channel]} seconds. Cut one of the following wires: " . implode(', ', $this->colors[$channel]));
		$this->register_timed('kill', time() + $this->timer[$channel], $channel);
	}

	public function kill($channel)
	{
		if (isset($this->target[$channel]))
			bot::get_instance()->send("KICK $channel {$this->target[$channel]} :BOOM! Time's up!");
	}

	public function remove_inactive($args)
	{
		$channel = bot::get_instance()->channel;

		if (empty ($this->nicks[$channel]))
			return;

		foreach ($this->nicks[$channel] as $nick => $time) {
			if ($time < time() - 60)
				unset($this->nicks[$channel][$nick]);
		}
	}
}

?>
