<?php
/**
 * 8ball plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class eightball extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', '8ball', 'pub_8ball');
		$this->register_event('command', 'decide');
	}

	public function pub_8ball($args = NULL)
	{
		$answers[] = "As I see it, yes";
		$answers[] = "It is certain";
		$answers[] = "It is decidedly so";
		$answers[] = "Most likely";
		$answers[] = "Outlook good";
		$answers[] = "Signs point to yes";
		$answers[] = "Without a doubt";
		$answers[] = "Yes";
		$answers[] = "Yes - definitely";
		$answers[] = "You may rely on it";
		$answers[] = "Reply hazy, try again";
		$answers[] = "Ask again later";
		$answers[] = "Better not tell you now";
		$answers[] = "Cannot predict now";
		$answers[] = "Concentrate and ask again";
		$answers[] = "Don't count on it";
		$answers[] = "My reply is no";
		$answers[] = "My sources say no";
		$answers[] = "Outlook not so good";
		$answers[] = "Very doubtful";

		parent::answer($answers[array_rand($answers)]);
	}

	public function decide($args)
	{
		if (count($args) < 2) {
			$this->pub_8ball();
			return;
		}
		parent::answer($args[array_rand($args)]);
	}
}

?>
