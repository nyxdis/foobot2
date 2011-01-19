<?php
/**
 * unshorten.com plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class unshorten extends plugin_interface
{
	public function load()
	{
		$trigger = '/(?<url>(https?:\/\/|www\.)\S+)/';
		$this->register_event('text', $trigger, 'do_unshorten');
	}

	public function do_unshorten($args)
	{
		$short_url = trim($args['url']);
		$ch = curl_init();
		$url = 'http://www.unshorten.com/index.php';
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla');
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('url' => $short_url));
		$result = curl_exec($ch);
		preg_match('/The real location of ' . preg_quote($short_url, '/') . ' is:\s+<br\/>\s+<a href="(?<real_url>[^"]+)/', $result, $matches);
		if ($short_url != $matches['real_url']) {
			$bot = bot::get_instance();
			$bot->say($bot->channel, $short_url . ' => ' . $matches['real_url']);
		}
	}
}

?>
