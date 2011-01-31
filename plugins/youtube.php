<?php
/**
 * YouTube plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class youtube extends plugin_interface
{
	/**
	 * Plugin initialization
	 * @see plugins::register_event()
	 */
	public function init()
	{
		$trigger = '/https?:\/\/(www\.)?youtube\.(com|de)\/watch\?.*v=(?<videoid>[\w0-9_]+)/';
		$this->register_event('text', $trigger, 'youtube_parse');
	}

	/**
	 * Ping function
	 * @param mixed $dummy unused
	 */
	public function youtube_parse($args)
	{
		$ch = curl_init();
		$url = 'http://www.youtube.com/oembed?url=http%3A//www.youtube.com/watch?v%3D' . $args['videoid'] . '&format=json';
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		$result = json_decode($result);
		if (!$result)
			return;
		parent::answer($result->provider_name . ' - ' . $result->title);
	}
}

?>
