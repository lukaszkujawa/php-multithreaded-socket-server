<?php

namespace Sock;

class SocketException extends \Exception {

	const CANT_CREATE_SOCKET = 1;
	const CANT_BIND_SOCKET = 2;
	const CANT_LISTEN = 3;
	const CANT_ACCEPT = 4;
	
	public $messages = array(
		self::CANT_CREATE_SOCKET => 'Can\'t create socket: "%s"',
		self::CANT_BIND_SOCKET => 'Can\'t bind socket: "%s"',
		self::CANT_LISTEN => 'Can\'t listen: "%s"',
		self::CANT_ACCEPT => 'Can\'t accept connections: "%s"',
	);
	
	public function __construct( $code, $params = false ) {
		if( $params ) {
			$args = array( $this->messages[ $code ], $params );
			$message = call_user_func_array('sprintf', $args );
		}
		else {
			$message = $this->messages[ $code ];
		}
		
		parent::__construct( $message, $code );
	}
}