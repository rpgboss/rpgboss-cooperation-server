<?php
namespace RpgbossCoopServer;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $lastMessages = array();

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    protected function sendToAll($from, $message)
    {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($message);
            }
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        //echo "New connection! ({$conn->resourceId})\n";
        $conn->send('5<>'.base64_encode(implode('^^^',$this->lastMessages)));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        //echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
        //    , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        $split = explode('<>', $msg);
        if(count($split)==1) {
            $type = 'msg';
            $message = $msg;
        } else {
            $type = $split[0];
            $message = $split[1];
        }


        switch($type) {
            case 'msg':
                $message .= ';'.time();
                $this->sendToAll($from, '1<>'.$message);
                $from->send('1<>'.$message);
                $this->lastMessages[] = '1<>'.$message;
                if(count($this->lastMessages) > 20) {
                    array_shift($this->lastMessages);
                }
                break;
            case 'me':
                if($message=='get-users') {
                    $namesarray = array();
                    foreach($this->clients as $client) {
                        $namesarray[] = $client->username;
                    }
                    $namesarray = array_unique($namesarray);
                    $from->send('2<>'.count($namesarray));
                }
                if($message=='get-usernames') {
                    $namesarray = array();
                    foreach($this->clients as $client) {
                        $namesarray[] = $client->username;
                    }
                    $namesarray = array_unique($namesarray);
                    $from->send('4<>'.implode(',',$namesarray));
                }
                if(preg_match('#add-user#i',$message)) {
                    $split = explode(':', $message);
                    $from->username = $split[1];
                    $from->send('3<>ok');
                }
                break;
        }


    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        $conn = null;

        //echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        //echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}