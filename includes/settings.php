<?php
/**
 * Settings management
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

/**
 * @ignore
 **/
define('BOT_VERSION_ID', 9900);		// PHP bug? 009900 won't work
define('LF', "\n");

if ($argc != 2)
	require 'config.php';
else
	require $argv[1];

$required_settings[] = 'nick';
$required_settings[] = 'network';
$required_settings[] = 'server';
$required_settings[] = 'channels';

foreach ($required_settings as $setting)
	if (!isset ($settings[$setting]))
		die ('Required setting \'' . $setting . '\' missing!');

// Generate BOT_VERSION from BOT_VERSION_ID
$major = floor(BOT_VERSION_ID / 10000);
$minor = floor((BOT_VERSION_ID - ($major * 10000)) / 100);
$micro = BOT_VERSION_ID - ($major * 10000) - ($minor * 100);
define('BOT_VERSION', $major . '.' . $minor . '.' . $micro);
