<?php
namespace Sock;

require_once "SocketException.php";
require_once "SocketClient.php";

class SocketClientBroadcast extends SocketClient {
	
	protected $server;
	
	public function __construct( $connection, SocketServerBroadcast $server ) {
		parent::__construct( $connection );
		$this->server = $server;
	}
	
	public function sendBroadcast($message) {
		$this->server->broadcast( array( 'data' => $message, 'type' => 'msg' ) );
	}
	
	public function disconnected() {
		$this->server->broadcast( array( 'type' => 'disc' ) );
		$this->close();
	}
	
	public function connected() {
		// don't need this file open in child processes
		unset($this->server->pipe);
		$this->server->broadcast( array( 'data' => "Connected\n", 'type' => 'msg' ) );
	}
	
}
