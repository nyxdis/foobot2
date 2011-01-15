<?php
/**
 * tv plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class tv extends plugin_interface
{
	public function load()
	{
		$this->register_event('command', 'tv', 'pub_tv');
	}

	public function pub_tv($args)
	{
		$usr = bot::get_instance()->usr;

		$tvdb = new SQLite3('xmltv.db');
		if (empty ($args))
			$args = array('');
		switch($args[0]) {
		case 'next':
			$tv = $tvdb->query('select * from (select channelid, display_name, title, start from programme, channels where channelid=id and start>strftime(\'%s\', \'now\') and channelid in (' . $usr->tv_channels . ') order by start desc) group by channelid');
			$next = '';
			while ($programme = $tv->fetchArray()) {
				$next .= $programme['display_name'] . ': ' . date('H:i', $programme['start']) . ' ' . $programme['title'] . ', ';
			}
			if (!empty ($next))
				self::answer(rtrim($next, ', '));
			else
				self::answer('No EPG data available');
			break;
		case 'chanlist':
			$tv_channels = $tvdb->query('SELECT display_name FROM channels GROUP BY id');
			$chanlist = 'I know these channels: ';
			while ($chan = $tv_channels->fetchArray()) {
				$chanlist .= $chan['display_name'] . ', ';
			}
			self::answer(trim($chanlist, ', '));
			break;
		case 'set':
			array_shift($args);
			$list = strtolower(implode('\', \'', str_replace(', ', '', $args)));
			$tv_channels = $tvdb->query('SELECT * FROM channels WHERE lower(display_name) IN (\'' . $list . '\')');
			while ($chan = $tv_channels->fetchArray()) {
				$chanlist .= '\'' . $chan['id'] . '\', ';
				$display_names .= $chan['display_name'] . ', ';
			}
			$usr->tv_channels = rtrim($chanlist, ', ');
			self::answer('Set your personal channels to: ' . rtrim($display_names, ', '));
			break;
		case 'unset':
			$usr->tv_channels = "'prosieben.de', 'rtl.de', 'sat1.de'";
			self::answer('Reset your channels to default');
			break;
		case 'search':
			array_shift($args);
			$keywords = implode('%', $args);
			$programmes = $tvdb->query('SELECT display_name, title, start FROM programme, channels WHERE channelid=id AND start>' . (int)time() . ' AND title LIKE \'%' . $tvdb->escapeString($keywords) . '%\' GROUP BY channelid ORDER BY start ASC LIMIT 3');
			$next = '';
			while ($programme = $programmes->fetchArray()) {
				if (date('Ymd', $programme['start']) == date('Ymd'))
					$dateformat = 'H:i';
				else
					$dateformat = 'j.n. H:i';
				$next .= $programme['display_name'] . ': ' . date($dateformat, $programme['start']) . ' ' . $programme['title'] . ', ';
			}
			if (!empty ($next))
				self::answer(rtrim($next, ', '));
			else
				self::answer('No EPG data available');
			break;
		default:
			if (preg_match('/(?<hour>\d\d)(:|\.)?(?<minute>\d\d)/', $args[0], $matches)) {	 /* !tv HHMM */
				$time = mktime($matches['hour'], $matches['minute'], 0);
				if (($time+7200) < time())
					$time += 86400;
				if (isset ($args[1]))
					$tv_channels = $tvdb->escapeString($tvdb->querySingle('SELECT `id`
											       FROM `channels`
											       WHERE `display_name` LIKE \'' . $tvdb->escapeString($args[1]) . '\'
											       LIMIT 1'));
				else
					$tv_channels = $usr->tv_channels;
				$tv = $tvdb->query('SELECT display_name, title, start FROM programme, channels WHERE channelid=id AND channelid IN (' . $tv_channels . ') AND start<=' . (int)$time . ' AND stop>' . (int)$time . ' GROUP BY channelid');
				$onair = '';
				while ($programme = $tv->fetchArray()) {
					$onair .= $programme['display_name'] . ': ' . date('H:i', $programme['start']) . ' ' . $programme['title'] . ', ';
				}
				if (!empty ($onair))
					self::answer(rtrim($onair, ', '));
				else
					self::answer('No EPG data available');
				break;
			}
			if (!empty ($args[0])) {	/* !tv <chan> */
				$cid = $tvdb->querySingle('SELECT id FROM channels WHERE display_name LIKE \'' . $tvdb->escapeString($args[0]) . '\'');
				if (!$cid) {
					self::answer('Channel not found');
					return;
				}
				if (isset ($args[1])) {
					preg_match('/(?<hour>\d\d?)(:|\.)?(?<minute>\d\d)/', $args[1], $timeinfo);
					$ts = mktime($timeinfo['hour'], $timeinfo['minute'], 0);
				} else {
					$ts = time();
				}
				$data = $tvdb->query('SELECT channelid, title, start FROM programme WHERE stop>' . $ts . ' AND channelid=\'' . $tvdb->escapeString($cid) . '\' ORDER BY start ASC LIMIT 2');
				$onair = '';
				while ($oa = $data->fetchArray()) {
					$onair .= date('H:i', $oa['start']) . ' ' . $oa['title'] . ', ';
					$display_name = $oa['channelid'];
				}
				$display_name = $tvdb->querySingle('SELECT display_name FROM channels WHERE id=\'' . $display_name . '\'');
				$onair = $display_name . ': ' . rtrim($onair, ', ');
				if ($onair)
					self::answer($onair);
				else
					self::answer('No EPG data available');
				break;
			}
			$tv = $tvdb->query('SELECT display_name, title, start FROM programme, channels WHERE channelid=id AND channelid IN (' . $usr->tv_channels . ') AND start<=' . (int)time() . ' AND stop>' . (int)time() . ' GROUP BY channelid');
			$onair = '';
			while ($programme = $tv->fetchArray()) {
				$onair .= $programme['display_name'] . ': ' . date('H:i', $programme['start']) . ' ' . $programme['title'] . ', ';
			}
			if (!empty ($onair))
				self::answer(rtrim($onair, ', '));
			else
				self::answer('No EPG data available');
			break;
		}
	}
}

?>
