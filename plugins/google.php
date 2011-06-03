<?php
/**
 * google plugin
 *
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Implementation of plugin_interface
 * @package foobot
 * @subpackage plugins
 */
class google extends plugin_interface
{
	public function init()
	{
		$this->register_event('command', 'google', 'pub_google');
		$this->register_event('command', 'weather');
		$this->register_event('command', 'suggest');
		$this->register_event('command', 'translate');

		$this->register_help('google', 'Search google');
		$this->register_help('weather', 'Show the current weather (your location is saved)');
		$this->register_help('suggest', 'Let google suggest different keywords');
		$this->register_help('translate', 'Google Translate - translate from to text');
	}

	public function pub_google($args)
	{
		$ch = curl_init();
		$keyword = implode(' ', $args);
		$searchurl = 'https://www.googleapis.com/customsearch/v1?key=AIzaSyAYZXx03UHrabnRNR9v-dHKmo9AyaPNNKI&cx=013874739653622873177:ofobjgneups&q=' . urlencode($keyword);
		curl_setopt($ch, CURLOPT_URL, $searchurl);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$ret = json_decode(curl_exec($ch));
		$results = 'Results: ';
		for ($i = 0; $i < 3; $i++) {
			$item = $ret->items[$i];
			if (!$item)
				break;
			$results .= ($i + 1) . '. ' . $item->title . ' (' . $item->link . '), ';
		}
		$results = rtrim($results, ', ');
		parent::answer($results);
	}

	public function weather($args)
	{
		$usr = bot::get_instance()->usr;

		if (count($args) > 0)
			$city = $args[0];
		else
			$city = $usr->location;

		if (!$city) {
			parent::answer('There is no saved location for you, try !weather <location>');
			return;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/ig/api?weather=' . $city);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$weather = simplexml_load_string(utf8_encode(curl_exec($ch)))->weather;
		if (!isset ($weather->forecast_information)) {
			parent::answer('Unknown location');
			return;
		}
		$info = $weather->forecast_information;
		$weather = $weather->current_conditions;

		/* parsed info */
		$postal_code = (string)$info->postal_code['data'];
		$usr->location = $postal_code;
		$city = $info->city['data'];
		$time = date('G:i', strtotime($info->current_date_time['data']));
		$temp_metric = $weather->temp_c['data'];
		$temp_imperial = $weather->temp_f['data'];
		$humidity = explode(' ', $weather->humidity['data']);
		$humidity = $humidity[1];
		$condition = strtolower($weather->condition['data']);
		$wind = explode(' ', $weather->wind_condition['data']);
		$wind_direction = $wind[1];
		$wind_speed_imperial = $wind[3];
		$wind_speed_metric = round($wind_speed_imperial * 0.44704);

		parent::answer($city . ' at ' . $time . ': The temperature is ' . $temp_metric . ' °C (' . $temp_imperial . ' °F) with ' . $humidity . ' humidity. The weather is ' . $condition . ' with wind from ' . $wind_direction . ' at ' . $wind_speed_metric . ' m/s (' . $wind_speed_imperial . ' mph)');
	}

	public function suggest($args)
	{
		$keyword = implode(' ', $args);
		$ch = curl_init();
		if (substr($keyword, 0, 3) == 'de '){
			$lang = 'de';
			$dunno = 'Keine Ahnung.';
			$dym = 'Meinten Sie';
			$keyword = substr($keyword, 3);
		} else {
			$lang = 'en';
			$dunno = 'No idea.';
			$dym = 'Did you mean';
			if (substr($keyword, 0, 3) == 'en ')
				$keyword = substr($keyword, 3);
		}
		$suggest = 'http://clients1.google.de/complete/search?hl=' . urlencode($lang) . '&q=' . urlencode($keyword) . '&cp=' . strlen($keyword);

		curl_setopt($ch, CURLOPT_URL, $suggest);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$result = curl_exec($ch);

		preg_match('/^window.google.ac.h\((.*)\)$/', $result, $matches);
		$result = json_decode($matches[1]);
		if (!$result) {
			parent::answer($dunno);
			return;
		}
		$result = $result[1];
		$result = $result[array_rand($result)][0];
		parent::answer($dym . ': ' . $result);
	}

	public function translate($args)
	{
		$text = implode(' ', $args);
		if (preg_match('/^(?<from>..) (?<to>..) (?<text>.*)/', $text, $trans)) {
			$ch = curl_init();
			$gtrans = 'http://ajax.googleapis.com/ajax/services/language/translate?v=1.0';
			$gtrans .= '&langpair=' . $trans['from'] . '|' . $trans['to'];
			$gtrans .= '&q=' . urlencode($trans['text']);
			curl_setopt($ch, CURLOPT_URL, $gtrans);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$response = json_decode(curl_exec($ch));
			parent::answer($response->responseData->translatedText);
		} else {
			parent::answer('Usage: translate <from> <to> <text>');
		}
	}
}

?>
