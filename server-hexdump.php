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
		// parent process
		pcntl_wait($status,WNOHANG);
		return;
	}
	
	$read = '';
	printf( "[%s] Connected at port %d\n", $client->getAddress(), $client->getPort() );
	//echo "client: ".print_r($client,true)."\n";
	printf( "  PID   Addr    Hex-Values                   Characters\n");
	printf( "==============================================================\n");
	while( true ) {
		$read = $client->read();
		if( $read != '' ) {
			$client->send( '[' . date( DATE_RFC822 ) . '] ' . $read  );
		}
		else {
			break;
		}
		
		if( preg_replace( '/[^a-z]/', '', $read ) == 'exit' ) {
			break;
		}
		if( $read === null ) {
			printf( "[%s] Disconnected\n", $client->getAddress() );
			return false;
		}
		else {
			//printf( "%d [%s] recieved: %s\n", getmypid(), $client->getAddress(), $read );
			$len=strlen($read);
			printf( "%6d  recieved: %d (%04X) Bytes:\n", getmypid(),$len,$len);
			$adresse=0;
			for ($i=0; $i<$len+(8-($len%8)); $i++) {
				if ($i % 8 == 0)
					printf("%6d  %04X:  ",getmypid(),$adresse);
				if (($i % 4) ==0)
					echo " ";
				if ($i<$len) {
					$zeichen=substr($read,$i,1);
					printf("%02x ",ord($zeichen));
				} else {
					echo "   ";
				}
				if (($i+1)%8==0) {
					echo "   |";
					for ($j=$adresse; $j<$adresse+8; $j++) {
						if ($j>=$len) {
							echo " ";
							continue;
						}
						$zeichen=substr($read,$j,1);
						if (ctype_print($zeichen))
							echo $zeichen;
						else {
							$zeichen =  addcslashes($zeichen,"\n\r\t");
							if (ctype_print($zeichen))
								echo $zeichen;
							else
								echo ".";
						}
					}
					echo "|\n";
					$adresse += 8;
				}
				
			}
		}
	}
	$client->close();
	printf( "[%s] Disconnected\n", $client->getAddress() );
	
}

require "sock/SocketServer.php";

$server = new \Sock\SocketServer();
$server->init();
$server->setConnectionHandler( 'onConnect' );
$server->listen();
