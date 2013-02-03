<?php

namespace Sock;

require_once "SocketException.php";
require_once "SocketClient.php";

class SocketServer {
	
	private $sockServer;
	private $address;
	private $port;
	private $_listenLoop;
	private $connectionHandler;
	
	public function __construct( $port = 4444, $address = '127.0.0.1' ) {
		$this->address = $address;
		$this->port = $port;
		$this->_listenLoop = false;
	}
	
	public function init() {
		$this->_createSocket();
		$this->_bindSocket();
	}
	
	private function _createSocket() {
		$this->sockServer = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if( $this->sockServer === false ) {
			throw new SocketException( 
				SocketException::CANT_CREATE_SOCKET, 
				socket_strerror(socket_last_error()) );
		}
		
		socket_set_option($this->sockServer, SOL_SOCKET, SO_REUSEADDR, 1);
	}
	
	private function _bindSocket() {
		if( socket_bind($this->sockServer, $this->address, $this->port) === false ) {
			throw new SocketException( 
				SocketException::CANT_BIND_SOCKET, 
				socket_strerror(socket_last_error( $this->sockServer ) ) );
		}
	}
	
	public function setConnectionHandler( $handler ) {
		$this->connectionHandler = $handler;
	}
	
	public function listen() {
		if( socket_listen($this->sockServer, 5) === false) {
			throw new SocketException( 
				SocketException::CANT_BIND_SOCKET, 
				socket_strerror(socket_last_error( $this->sockServer ) ) );
		}

		printf( "Listening on %s:%d...\n", $this->address, $this->port );
		
		$this->_listenLoop = true;
		
		while( $this->_listenLoop ) {
			if( ( $client = socket_accept( $this->sockServer ) ) === false ) {
				throw new SocketException( 
					SocketException::CANT_ACCEPT, 
					socket_strerror(socket_last_error( $this->sockServer ) ) );
			}
			
			$socketClient = new SocketClient( $client );
			
			if( is_array( $this->connectionHandler ) ) {
				$object = $this->connectionHandler[0];
				$method = $this->connectionHandler[1];
				$object->$method( $socketClient );
			}
			else {
				$function = $this->connectionHandler;
				$function( $socketClient );
			}
		}
		
		socket_close( $this->sockServer );
	}

}

