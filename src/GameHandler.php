<?php
namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use MyApp\Templates\Player as Player;
use MyApp\Templates\Point as Point;
use Ratchet\RFC6455\Messaging\DataInterface;
use Ratchet\Wamp\Exception;

class GameHandler implements MessageComponentInterface {

    protected mixed $clients;
    private array $players;
    private array $points;
    private \DateTime $lastPointAdded;
    private \DateTime $lastBroadcast;


    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->players = array();
        $this->points = array();
        $this->lastPointAdded = new \DateTime();
        $this->lastBroadcast = new \DateTime();

        for ($i = 0; $i<2000 ; $i++) {
            array_push($this->points, new Point(rand(-5000,5000), rand(-5000,5000)));
        }

    }

    function run(){
        $now = new \DateTime();

        $pointDiff = date_diff($this->lastPointAdded, $now);
        $broadcastDiff = date_diff($this->lastBroadcast, $now);






        if($pointDiff->f >= 0.5 && count($this->points) < 5000) {
            $this->lastPointAdded=$now;
            array_push($this->points, new Point(rand(-5000,5000), rand(-5000,5000)));
        }

        if($broadcastDiff->f>0.2){
            $this->lastBroadcast=$now;

            foreach ($this->clients as $client) {
                $players = $this->players;
                $player = $players[$client->resourceId];
                unset($players[$client->resourceId]);
                $data = (object)null;
                $points = $this->points;

                foreach ($points as $key => $point){
                    if(abs($player->x-$point->x)<$player->getSize()
                        && abs($player->y - $point->y<$player->getSize())){

                        $player->eat($point->mass);
                        unset($points[$key]);
                        unset($this->points[$key]);
                    }
                }

                $points = array_values($points);
                $this->points = array_values($this->points);
                $players = array_values($players);

                $data->points = $points;
                $data->players = $players;
                $data->player = $player;

                $client->send(json_encode($data));
            }
        }


    }


    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->players[$conn->resourceId]=new Player();
    }

    public function onMessage(ConnectionInterface $from, $msg) {

        $player = json_decode($msg);

        if ($player->x && $player->y && $player->color&&$player->mass){
            $id = $from->resourceId;

            $this->players[$id]->x = $player->x;
            $this->players[$id]->y = $player->y;
            $this->players[$id]->mass = $player->mass;
            $this->players[$id]->color = $player->color;
            $this->players[$id]->initialized = true;
        }
        else{
            throw new Exception("bad request");
        }



        $this->run();

    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }




}