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
define('BOT_VERSION', '0.99.0');
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
