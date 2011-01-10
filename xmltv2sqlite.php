<?php
error_reporting(E_ALL);
date_default_timezone_set('Europe/Berlin');
$db = new PDO('sqlite:'.__DIR__.'/xmltv.db');

$tv = simplexml_load_file(__DIR__.'/tv.xml');
$db->beginTransaction();

$db->query('CREATE TABLE IF NOT EXISTS channels (id varchar(40), display_name varchar(20))');
$db->query('CREATE TABLE IF NOT EXISTS programme (channelid varchar(40), title varchar(30), start int(11), stop int(11))');

#$db->query('DELETE FROM channels');
$db->query('DELETE FROM programme');

/*
foreach($tv->channel as $channel) {
	$attributes = $channel->attributes();
	$id = $attributes['id'];
	$name = str_replace(' ','',$channel->{'display-name'}[0]);
	$db->exec('INSERT INTO channels (id, display_name) VALUES('.$db->quote($id).','.$db->quote($name).')');
}
*/

foreach($tv->programme as $programme) {
	$attributes = $programme->attributes();
	$channelid = $attributes['channel'];
	$start = strtotime($attributes['start']);
	$stop = strtotime($attributes['stop']);
	$title = $programme->title;
	$db->exec('INSERT INTO programme (channelid, title, start, stop) VALUES('.$db->quote($channelid).','.$db->quote($title).','.(int)$start.','.(int)$stop.')');
}

$db->commit();
?>
