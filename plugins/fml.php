<?php
/**
 * Fuck My Life plugin
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class fml extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'fml', 'pub_fml');
		$this->register_event('command', 'fmsl');

		$this->register_help('fml', 'get random FML quotes');
		$this->register_help('fmsl', 'get random FMSL quotes (broken)');
	}

	public function pub_fml($args)
	{
                $url = 'http://www.fmylife.com/random';

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $result = curl_exec($ch);
                preg_match('/<a href="\S+" class="fmllink">(?<text>Today, .* FML)<\/a>/', $result, $fml);
                $text = html_entity_decode(strip_tags($fml['text']));
                parent::answer($text);
	}

	public function fmsl($args)
	{
		parent::answer('FMSL currently broken');
	}
}

?>
