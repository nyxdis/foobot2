<?php
/**
 * urban plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class urban extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'urban', 'pub_urban');

		$this->register_help('urban', 'urban dictionary');
	}

	public function pub_urban($args)
	{
		$ch = curl_init();
		if (empty ($args))
			$urban = 'http://www.urbandictionary.com/random.php';
		else
			$urban = 'http://www.urbandictionary.com/define.php?term=' . urlencode(implode(' ', $args));

		curl_setopt($ch, CURLOPT_URL, $urban);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$result = curl_exec($ch);
		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		if (preg_match('/<meta content=\'(?<description>.*)\' property=\'og:description\' \/>/', $result, $match)) {
			$description = $match['description'];
			$description = str_replace('&apos;', '\'', $description);
			preg_match('/<meta content=\'(?<title>.*)\' property=\'og:title\' \/>/', $result, $match);
			$title = $match['title'];
			parent::answer(html_entity_decode($title) . ' - ' . html_entity_decode($description) . ' (' . $url . ')');
		} else {
			parent::answer('No definition found');
		}
	}
}

?>
