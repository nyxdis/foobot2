<?php
/**
 * urban plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
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
			$urban = 'http://api.urbandictionary.com/v0/random';
		else
			$urban = 'http://api.urbandictionary.com/v0/define?term=' . urlencode(implode(' ', $args));

		curl_setopt($ch, CURLOPT_URL, $urban);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$result = json_decode(curl_exec($ch));

		if ($result->result_type == "no_results") {
			parent::answer('No definition found');
		} else {
			$result = $result->list[0];
			$def = explode("\n", $result->definition, 2)[0];
			$def = substr($def, 0, 250) . '...';
			parent::answer($result->word . ' - ' . $def . ' <' . $result->permalink . '>');
		}
	}
}

?>
