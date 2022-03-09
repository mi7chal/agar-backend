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


        if($pointDiff->f >= 0.3 && count($this->points) < 5000) {
            $this->lastPointAdded=$now;
            array_push($this->points, new Point(rand(-5000,5000), rand(-5000,5000)));
        }

        if($broadcastDiff->f>0.1){
            $this->lastBroadcast=$now;

            foreach ($this->clients as $client) {
                $players = $this->players;
                $player = $players[$client->resourceId];
                unset($players[$client->resourceId]);
                $data = (object)null;
                $points = array();


                foreach ($this->points as $key => $point){
                    if(abs( $point->x -$player->x) < $player->getSize()
                        && abs($player->y - $point->y) < $player->getSize()){
                        echo "EAT\n".count($this->players);

                        $player->eat($point->mass);
                        unset($this->points[$key]);
                    }

                    $width = $player->ctxWidth;
                    $height = $player->ctxHeight;

                    $ctxX = $point->getCtxX($player->x, $width);
                    $ctxY = $point->getCtxY($player->y, $height);

                    if($ctxX >= 0 - $width *0.1 && $ctxX <= $width * 1.1
                        && $ctxY >= 0 - $height * 0.1 && $ctxY <= $height * 1.1) {
                        $points[$key] = $point;
                    }
                }

                $data->points = array_values($points);
                $data->players = array_values($players);
                $data->player = $player;

                $client->send(json_encode($data));
            }
        }


    }


    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $this->players[$conn->resourceId]=new Player();
        $this->players[$conn->resourceId]->mass = 700;
    }

    public function onMessage(ConnectionInterface $from, $msg) {

        $player = json_decode($msg);

        if ($player->x && $player->y && $player->color && $player->mass && $player->ctxHeight && $player->ctxWidth){
            $id = $from->resourceId;

            $this->players[$id]->x = $player->x;
            $this->players[$id]->y = $player->y;
            $this->players[$id]->color = $player->color;
            $this->players[$id]->ctxHeight = $player->ctxHeight;
            $this->players[$id]->ctxWidth = $player->ctxWidth;

            $this->players[$id]->initialized = true;
        }
        else{
            throw new Exception("bad request");
        }

        $this->run();

    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->players[$conn->resourceId]);
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }




}