<?php
/**
 * votekick plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

class votekick extends plugin_interface
{
	private $votes = array();

	public function init()
	{
		$this->register_event('command', 'votekick', 'pub_votekick');
		$this->register_help('votekick', 'vote to kick someone or show all current votes');
	}

	public function pub_votekick($args)
	{
		$channel = bot::get_instance()->channel;
		$name = bot::get_instance()->usr->name;

		$maxvotes = 5;
		$expiry = 60 * 60 * 24;

		if (count($args) > 0) {
			$target = $args[0];
			if (isset ($this->votes[$channel][$target]) && $this->votes[$channel][$target]['lastvote'] > (time() - $expiry)) {
				if (in_array($name, $this->votes[$channel][$target]['voters'])) {
					parent::answer('You\'ve already voted.');
					return;
				}
				$this->votes[$channel][$target]['count']++;
				$this->votes[$channel][$target]['voters'][] = $name;
			} else {
				$this->votes[$channel][$target] = array('count' => 1,
						'lastvote' => time(),
						'voters' => array($name));
			}
			if ($this->votes[$channel][$target]['count'] < $maxvotes) {
				parent::answer('Vote counted.');
			} else {
				bot::get_instance()->send('KICK ' . $channel . ' ' . $target . ' :It\'s not because I don\'t like you...');
				$this->votes[$channel][$target] = array();
			}
		} else {
			$votes = array();

			if (isset ($this->votes[$channel])) {
				foreach ($this->votes[$channel] as $target => $params)
					if ($params['lastvote'] > (time() - $expiry))
						$votes[$target] = $params['count'];
			}

			if (count($votes) == 0) {
				parent::answer('No current votes');
			} else {
				parent::answer('Current votes:');
				foreach ($votes as $target => $count)
					parent::answer($count . ' votes for ' . $target);
			}
		}
	}
}

?>
