<?php
/**
 * Created by PhpStorm.
 * User: hendrikweiler
 * Date: 20.02.15
 * Time: 17:47
 */

namespace RpgbossCoopServer;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use RpgbossCoopServer\Config;
use Simplon\Mysql\Mysql;


class Commander implements MessageComponentInterface {
    protected $clients;

    protected $config;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->config = Config::Load();

        $this->db = new Mysql(
            $this->config['mysql']['host'],
            $this->config['mysql']['username'],
            $this->config['mysql']['password'],
            $this->config['mysql']['database']
        );
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

    }

    public function onMessage(ConnectionInterface $from, $msg) {

        print $msg;

        $split = explode(';', $msg);
        if(count($split)==3) {
            $from->mode = $split[1];
            $command = $split[0];
            $value = json_decode($split[2]);

            switch($command) {
                case 'set':

                    print $from->mode;

                    $result = $this->db->fetchRow('SELECT * FROM user WHERE login_hash = :login_hash', array('login_hash' => $value->value));
                    if($result!=false) {
                        print " have user\n";
                        $from->user = $result;
                    }
                    break;
                case 'command':
                    foreach ($this->clients as $value2) {
                        $obj = $this->clients->current(); // current object

                        if(is_array($obj->user)) {
                            if($obj->user['id']==$from->user['id']
                            && $obj->mode != $from->mode) {
                                $obj->send("command;".$from->mode.";".json_encode($value));
                            }

                        }
                    }
                    break;
                case 'user':
                    $from->send("user;".$from->mode.";".json_encode(array('value'=>$this->clients->count())));
                    break;
            }

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

    public function __destruct()
    {
        $this->clients = null;

        $this->config = null;
        $this->db = null;
    }
}