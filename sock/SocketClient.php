<?php

namespace Sock;

class SocketClient {

	private $connection;
	private $address;
	private $port;
	
	private $peer_address;
	private $peer_port;

	public function __construct( $connection ) {
		$address = ''; 
		$port = '';
		socket_getsockname($connection, $address, $port);
		$this->address = $address;
		$this->port = $port;
		$this->connection = $connection;
		
		socket_getpeername($connection, $address, $port);
		$this->peer_address	=$address;
		$this->peer_port	=$port;
	}
	
	public function send( $message ) {	
		socket_write($this->connection, $message, strlen($message));
	}
	
	public function read($len = 1024) {
		if ( ( $buf = @socket_read( $this->connection, $len, PHP_BINARY_READ  ) ) === false ) {
				return null;
		}
		
		return $buf;
	}

	public function getAddress() {
		return $this->address;
	}
	
	public function getPort() {
		return $this->port;
	}
	
	public function getPeerAddress() {
		return $this->peer_address;
	}
	
	public function getPeerPort() {
		return $this->peer_port;
	}
	
	public function close() {
		socket_shutdown( $this->connection );
		socket_close( $this->connection );
	}
}
