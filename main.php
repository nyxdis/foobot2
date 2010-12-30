<?php
/**
 * Main file
 *
 * This file calls all other functions required to run the bot
 *
 * @author Christoph Mende <angelos@unkreativ.org>
 * @package foobot
 **/

if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
	die ('This script needs PHP 5.3 or higher!');

require_once 'includes/settings.php';
require_once 'includes/misc_functions.php';

$bot = bot::get_instance();
$bot->connect();
if(!$bot->connected)
	die('Failed to connect');
$bot->post_connect();

for (;;)
	$bot->wait();

?>
