<?php
/**
 * urls plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class urls extends plugin_interface
{
	public function load()
	{
		$trigger = '/(?<url>(https?:\/\/|www\.)\S+)/';
		$this->register_event('text', $trigger, 'urls_save');
		$this->register_event('command', 'urls', 'pub_urls');
	}

	public function urls_save($args)
	{
		$db = db::get_instance();
		$channel = bot::get_instance()->channel;

		$db->query('INSERT INTO urls (channel, url)
				VALUES(' . $db->quote($channel) . ',
					' . $db->quote($args['url']) . ')');
	}

	public function pub_urls($args)
	{
		$db = db::get_instance();
		$channel = bot::get_instance()->channel;

		$data = $db->query('SELECT * FROM urls
			WHERE channel=' . $db->quote($channel) . '
			ORDER BY id DESC
			LIMIT 5');
		$urls = 'Last 5 URLs: ';
		while ($row = $data->fetchObject())
			$urls .= $row->url . ', ';
		$urls = rtrim($urls,', ');
		parent::answer($urls);
	}
}

?>
