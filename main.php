<?php
/**
 * Main file
 *
 * This file calls all other functions required to run the bot
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 */

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
	die ('This script needs PHP 5.3 or higher!');

require_once 'includes/settings.php';
require_once 'includes/misc_functions.php';
require_once 'includes/plugins.php';

// Load settings
settings::load($argc, $argv);

// Load plugins
$plugins = plugins::get_instance();
foreach (glob('plugins/*.php') as $file) {
	$file = basename($file);
	$file = substr($file, 0, -4);
	$plugins->load($file);
}
$plugins->load_timed();
unset ($plugins);

$bot = bot::get_instance();
$bot->load_aliases();
$bot->usr = new user();
$bot->connect();
if (!$bot->connected)
	die ('Failed to connect');
$bot->post_connect();
unset ($bot);

for (;;)
	bot::get_instance()->wait();

?>
