<?php

interface communication
{
	public function connect();
	public function post_connect();
	public function join($channel, $key);
	public function send($raw);
	public function say($target, $text);
}

?>
