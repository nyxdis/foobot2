<?php
/**
 * bom plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * BOOM
 * @todo multi-channel awareness
 * @package foobot
 * @subpackage plugins
 */
class bomb extends plugin_interface
{
	private $nicks = array();
	private $target;
	private $defuse_color;
	private $kill_collor;
	private $colors;

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
		$this->nicks[$usr->nick] = time();

		if (empty($args))
			return;

		$arg = strtolower($args[0]);
		if (in_array($arg, $this->colors)) {
			$usr = bot::get_instance()->usr;

			if ($this->target != $usr->nick)
				return;

			$channel = bot::get_instance()->channel;
			$timeleft = $this->timer - time() + $this->start;
			if ($arg == $this->defuse_color) {
				parent::answer("Great job!  You defused the bomb with $timeleft seconds on the timer.");
				unset($this->target);
			} else if ($arg == $this->kill_color) {
				bot::get_instance()->send("KICK $channel {$usr->nick} :BOOM!  The $arg wire was a trap!");
				unset($this->target);
			} else {
				parent::answer("The $arg wire was a decoy.  The clock is still ticking.  Try again.");
				unset($this->colors[$arg]);
				bot::get_instance()->say($channel, "{$this->target}: The timer reads {$timeleft} seconds. Cut one of the following wires: " . implode(', ', $this->colors));
			}
		}
	}

	private function random_bomb()
       	{
		if (empty($this->nicks)) {
			return;
		}

		$nick = array_rand($this->nicks);

		$this->pub_bomb(array($nick));
	}

	public function random_bomb_timer($args)
	{
		if (mt_rand(0, 100) == 0)
			$this->random_bomb();
	}

	public function pub_bomb($args)
       	{
		if (isset($this->target)) {
			parent::answer("{$this->target} is holding a bomb already!");
			return;
		}

		$colors = array('black', 'blue', 'brown', 'gray', 'green', 'orange', 'red', 'white', 'yellow');

		if (empty($args)) {
			$this->random_bomb();
			return;
		}

		$this->target = $args[0];

		$tmp_colors = $colors;
		shuffle($tmp_colors);
		$num_colors = mt_rand(3, count($colors));

		$wire_colors = array();
		for ($i = 0; $i < $num_colors; $i++) {
			$color = array_pop($colors);
			$wire_colors[$color] = $color;
		}
		$this->colors = $wire_colors;

		$this->defuse_color = array_pop($wire_colors);
		$this->kill_color = array_pop($wire_colors);

		$this->timer = mt_rand(10, 60);
		$this->start = time();

		$bot = bot::get_instance();
		$channel = bot::get_instance()->channel;
		$bot->act($channel, "throws a bomb at {$this->target}");
		$bot->say($channel, "{$this->target}: The timer reads {$this->timer} seconds. Cut one of the following wires: " . implode(', ', $this->colors));
		$this->register_timed('kill', time() + $this->timer, $channel);
	}

	public function kill($channel)
	{
		$bot = bot::get_instance();
		bot::get_instance()->send("KICK $channel {$this->target} :BOOM! Time's up!");
	}

	public function remove_inactive($args)
	{
		if (empty ($this->nicks))
			return;

		foreach ($this->nicks as $nick => $time) {
			if ($time < time() - 60)
				unset($this->nicks[$nick]);
		}
	}
}

?>
