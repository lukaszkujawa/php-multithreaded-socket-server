<?php

/**
 * Check dependencies
 */
if( ! extension_loaded('sockets' ) ) {
	echo "This example requires sockets extension (http://www.php.net/manual/en/sockets.installation.php)\n";
	exit(-1);
}

if( ! extension_loaded('pcntl' ) ) {
	echo "This example requires PCNTL extension (http://www.php.net/manual/en/pcntl.installation.php)\n";
	exit(-1);
}

/**
 * Connection handler
 */
function onConnect( $client ) {
	$pid = pcntl_fork();
	
	if ($pid == -1) {
		 die('could not fork');
	} else if ($pid) {
		return $pid;
	}
	
	printf( "[%s] Connected at port %d\n", $client->getAddress(), $client->getPort() );
	
	$client->connected();
	
	$read = '';
	while( true ) {
		
		$read = $client->read();
		if( $read == '' ) {
			break;
		}
		$client->sendBroadcast( $read );
	}
	
	$client->disconnected();
	
	printf( "[%s] Disconnected\n", $client->getAddress() );
}

require "sock/SocketServerBroadcast.php";

$server = new \Sock\SocketServerBroadcast();
$server->init();
$server->setConnectionHandler( 'onConnect' );
$server->listen();
