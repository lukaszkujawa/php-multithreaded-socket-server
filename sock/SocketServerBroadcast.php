<?php

namespace Sock;

require_once "SocketServer.php";
require_once "SocketClientBroadcast.php";

class SocketServerBroadcast extends SocketServer {

	const PIPENAME = '/tmp/broadcastserver.pid';
	
	protected $pid;
	public $pipe;
	
	private $connections = array();
	
	public function __construct( $port = 4444, $address = '127.0.0.1' ) {
		parent::__construct( $port, $address );
		$this->pid = posix_getpid();
		if(!file_exists(self::PIPENAME)) {
			umask(0);
			if( ! posix_mkfifo(self::PIPENAME, 0666 ) ) {
				die('Cant create a pipe: "' . self::PIPENAME . '"');
			}
		}
		$this->pipe = fopen(self::PIPENAME, 'r+');
	}
	
	public function handleProcess() {
		$header = fread($this->pipe, 4);
		$len = $this->bytesToInt( $header );
		
		$message = unserialize( fread( $this->pipe, $len ) );
		
		if( $message['type'] == 'msg' ) {
			$client = $this->connections[ $message['pid'] ];
			$msg = sprintf('[%s] (%d):%s', $client->getAddress(), $message['pid'], $message['data'] );
			printf( "Broadcast: %s", $msg );
			foreach( $this->connections as $pid => $conn ) {
				if( $pid == $message['pid'] ) {
					continue;
				}
				
				$conn->send( $msg );
			}
		}
		else if( $message['type'] == 'disc' ) {
			unset( $this->connections[ $message['pid'] ] );
		}
	}
	
	public function bytesToInt($char) {
		$num = ord($char[0]);
		$num += ord($char[1]) << 8;
		$num += ord($char[2]) << 16;
		$num += ord($char[3]) << 24;
		return $num;
	}
	
	protected function beforeServerLoop() {
		parent::beforeServerLoop();
		socket_set_nonblock( $this->sockServer );
		pcntl_signal(SIGUSR1, array($this, 'handleProcess'), true);
	}
	
	protected function serverLoop() {
		while( $this->_listenLoop ) {
			if( ( $client = @socket_accept( $this->sockServer ) ) === false ) {
				$info = array();
				if( pcntl_sigtimedwait(array(SIGUSR1),$info,1) > 0 ) {
					if( $info['signo'] == SIGUSR1 ) {
						$this->handleProcess();
					}
				}
				continue;
			}
				
			$socketClient = new SocketClientBroadcast( $client, $this );
			
			if( is_array( $this->connectionHandler ) ) {
				$object = $this->connectionHandler[0];
				$method = $this->connectionHandler[1];
				$childPid = $object->$method( $socketClient );
			}
			else {
				$function = $this->connectionHandler;
				$childPid = $function( $socketClient );
			}
			
			if( ! $childPid ) {
				// force child process to exit from loop
				return;		
			}
			
			$this->connections[ $childPid ] = $socketClient;
		}
		
	}
	
	public function broadcast( Array $msg ) {
		$msg['pid'] = posix_getpid();
		$message = serialize( $msg );
		$f = fopen(self::PIPENAME, 'w+');
		if( !$f ) {
			echo "ERROR: Can't open PIPE for writting\n";
			return;
		}
		fwrite($f, $this->strlenInBytes($message) . $message );
		fclose($f);
		posix_kill($this->pid, SIGUSR1);
	}

	protected function strlenInBytes($str) {
		$len = strlen($str);
		$chars = chr( $len & 0xFF );
		$chars .= chr( ($len >> 8 ) & 0xFF );
		$chars .= chr( ($len >> 16 ) & 0xFF );
		$chars .= chr( ($len >> 24 ) & 0xFF );
		return $chars;
	}
}
