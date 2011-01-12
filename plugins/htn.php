<?php
/**
 * HackTheNet control plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 **/
class htn extends plugin_interface
{
	/**
	 * Plugin initialization
	 * @see plugins::register_event()
	 **/
	public function load()
	{
		$plugins = plugins::get_instance();

		$plugins->register_event(__CLASS__, 'command', 'bug');
	}

	public function bug($args)
	{
		$id = (int)$args[0];
		$url = 'http://hackthenet.org/_htn.php/bugtracker/showbug?output=json&id=' . $id;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$result = json_decode($result);
		if (!$result) {
			parent::answer('Bug nicht gefunden!');
			return;
		}
		$response = 'http://hackthenet.org/_htn.php/bugtracker/showbug?id=' . (int)$result->id . ' "' . $result->subject . '"; state: ' . $result->state . '; user: ' . $result->user_name . '->' . $result->admin;
		parent::answer($response);
	}
}

?>
