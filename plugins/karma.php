<?php
/**
 * karma plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class karma extends plugin_interface
{
	public function load()
	{
		$plugins = plugins::get_instance();

		$trigger = '/(?<item>.*)(?<karma>[+-]{2})($| ?# ?(?<comment>.*))/';
		$plugins->register_event(__CLASS__, 'text', $trigger, 'karmachange');
		$plugins->register_event(__CLASS__, 'command', 'karma-top5');
		$plugins->register_event(__CLASS__, 'command', 'karma-bottom5');
		$plugins->register_event(__CLASS__, 'command', 'karma-whydown');
		$plugins->register_event(__CLASS__, 'command', 'karma-whyup');
		$plugins->register_event(__CLASS__, 'command', 'karma', 'pub_karma');
	}

	public function karmachange($args)
	{
		$db = db::get_instance();

		$item = strtolower($args['item']);
		if ($item{0} == settings::$command_char)
			$item = substr($item, 1);
		$item = $db->quote($item);

		if ($args['karma'] == '++') {
			$kc = '+';
			$karmachange = 'up';
		} else {
			$kc = '-';
			$karmachange = 'down';
		}

		if (is_numeric($args['item'])) {
			$karma = $db->query('SELECT `karma` FROM `quotes` WHERE `id`=' . (int)$args['item'])->fetchObject();
			if ($karma === false) {
				parent::answer('Quote not found');
				return;
			}
			eval ('$newkarma = $karma->karma ' . $kc . ' 1;');
			if ($newkarma < -4) {
				$db->query('DELETE FROM `quotes` WHERE `id`=' . (int)$args['item']);
				$newkarma .= ' (auto-deleted)';
			} else {
				$db->query('UPDATE `quotes` SET `karma` = `karma`' . $kc . '1 WHERE `id`=' . (int)$args['item']);
			}
			parent::answer('Karma of quote #' . (int)$args['item'] . ' is now ' . $newkarma);
		} else {
			$oldkarma = $db->query('SELECT `value` FROM `karma` WHERE `item`=' . $item)->fetchObject();
			if (!$oldkarma) {
				eval ('$karma = ' . $kc . '1;');
				$db->query('INSERT INTO `karma` (`item`, `value`) VALUES(' . $item . ', 1)');
			} else {
				eval ('$karma = $oldkarma->value' . $kc . '1;');
				$db->query('UPDATE `karma` SET `value` = `value`' . $kc . '1 WHERE `item`=' . $item);
			}
			if (!empty ($args['comment']))
				$db->query('INSERT INTO `karma_comments` (`item`, `karma`, `comment`)
						VALUES(' . $item . ',' . $db->quote($karmachange) . ',' . $db->quote($args['comment']) . ')');
			parent::answer('Karma of ' . $item . ' is now ' . $karma);
		}
	}

	public function karma_top5($args)
	{
		$db = db::get_instance();

		$data = $db->query('SELECT * FROM `karma` ORDER BY `value` DESC LIMIT 5');
		$reply = 'Karma top 5: ';
		while ($row = $data->fetchObject())
			$reply .= $row->item . ' (' . $row->value . '), ';
		$reply = rtrim($reply, ', ');
		parent::answer($reply);
	}

	public function karma_bottom5($args)
	{
		$db = db::get_instance();

		$data = $db->query('SELECT * FROM `karma` ORDER BY `value` ASC LIMIT 5');
		$reply = 'Karma bottom 5: ';
		while ($row = $data->fetchObject())
			$reply .= $row->item . ' (' . $row->value . '), ';
		$reply = rtrim($reply, ', ');
		parent::answer($reply);
	}

	private function karma_why($item, $karma)
	{
		$db = db::get_instance();

		$reasons = $db->query('SELECT `comment` FROM `karma_comments` WHERE `item` LIKE ' . $db->quote($item) . ' AND `karma`=' . $db->quote($karma));
		$string = 'Reasons for ' . $item . ': ';
		while ($reason = $reasons->fetchObject())
			$string .= $reason->comment.', ';
		$string = rtrim($string,', ');
		if (strlen($string) > strlen('Reasons for ' . $item . ': '))
			parent::answer($string);
		else
			parent::answer('No comments for ' . $item);
	}

	public function karma_whydown($args)
	{
		if (empty ($args)) {
			parent::answer('Comments for what?');
			return;
		}
		$this->karma_why($args[0], 'down');
	}

	public function karma_whyup($args)
	{
		if (empty ($args)) {
			parent::answer('Comments for what?');
			return;
		}
		$this->karma_why($args[0], 'up');
	}

	public function pub_karma($args)
	{
		$db = db::get_instance();

		if (empty ($args)) {
			parent::answer('Karma of what?');
			return;
		}
		$item = $args[0];
		$value = $db->query('SELECT `value` FROM `karma` WHERE `item`=' . $db->quote($item))->fetchObject();
		if (!$value)
			parent::answer('Karma of ' . $item . ' is 0');
		else
			parent::answer('Karma of ' . $item . ' is ' . $value->value);
	}
}


?>
