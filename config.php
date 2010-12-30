<?php
/* Public stuff, change this */
$settings['command_char'] = '!';

/* The bot's protocol */
$settings['protocol'] = 'irc';

/* The bot's nick */
$settings['nick'] = 'foobot';

/* The server we're connecting to */
$settings['server'] = 'localhost';

/* Network, important for the database */
$settings['network'] = '';

/* Array with channels we want to join, format:
 * '#channel' => 'key'
 * Note: Channels without key need the '#channel' => '' format
 */
$settings['channels'] = array('#channel' => '');

/* Authtentication settings */
/* Password to use */
#$settings['authpass'] = '';
/* Login name */
#$settings['authnick'] = '';
/* The service's nick, usually NickServ */
#$settings['authserv'] = 'NickServ';
/* The command we're supposed to use, usually identify or id */
#$settings['authcmd'] = 'id';

/* Debug mode */
$settings['debug_mode'] = true;

/* Debug channel */
$settings['debug_channel'] = '#foobot2-debug';

/* External access via UNIX domain socket */
#$settings['control_socket'] = '';

/* Main channel */
$settings['main_channel'] = $settings['channels'][0];
?>
