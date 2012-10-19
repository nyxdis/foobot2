<?php
/**
 * Communication interface
 *
 * This interface defines the commands required for the bot to communicate
 * over any given protocol.
 * @author Christoph Mende <mende.christoph@gmail.com>
 * @package foobot
 */

/**
 * Communication interface
 * @package foobot
 * @subpackage classes
 */
interface communication
{
	/**
	 * @see bot::connect()
	 */
	public function connect();
	/**
	 * @see bot::post_connect()
	 */
	public function post_connect();
	/**
	 * @see bot::join()
	 */
	public function join($channel, $key);
	/**
	 * @see bot::send()
	 */
	public function send($raw);
	/**
	 * @see bot::say()
	 */
	public function say($target, $text);
	/**
	 * @see bot::notice()
	 */
	public function notice($target, $text);
	/**
	 * @see bot::act()
	 */
	public function act($target, $text);
	/**
	 * @see bot::quit()
	 */
	public function quit($msg);
	/**
	 * Executed on every loop beginning
	 */
	public function tick();
}

?>
