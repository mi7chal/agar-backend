<?php
namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use MyApp\Templates\Player as Player;
use MyApp\Templates\Point as Point;
use Ratchet\RFC6455\Messaging\DataInterface;
use Ratchet\Wamp\Exception;

/**
 * silnik gry, implementuje websocket z Ratchet
 */
class GameHandler implements MessageComponentInterface {

    /**
     * @var mixed|\SplObjectStorage klienci
     */
    protected mixed $clients;

    /**
     * @var array lista graczy (tez klientow, ale zawiera inne dane,
     * na niej operuje silnik, a z $clients tylko pobiera dane)
     */
    private array $players;

    /**
     * @var array lista punktow
     */
    private array $points;

    /**
     * @var \DateTime kiedy dodano ostatni punkt
     */
    private \DateTime $lastPointAdded;

    /**
     * @var \DateTime kiedy ostatni raz wyslano broadcast z informacjami o grze
     */
    private \DateTime $lastBroadcast;

    /**
     * inicjalizuje zmienne, losuje 2000 punktow na plansze na start
     */
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

    /**
     * @return void glowna metoda, dodaje punkt, wysyla broadcast,
     * usuwa zjedzone punktu, decyduje, czy wykonac kazda z tych operacji
     */
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
                if(!$player->initialized){
                    continue;
                }

                $points = array();

                foreach ($this->players as $key => $playerIterator){
                    if($key == $client->resourceId){
                        continue;
                    }

                    if(!$playerIterator->initialized){
                        continue;
                    }

                    if(abs( $playerIterator->x-$player->x) < $player->getSize()
                        && abs($playerIterator->y - $player->y) < $player->getSize()
                        && $playerIterator->getSize()+10 < $player->getSize()){

                        $player->eat($playerIterator->mass);
                        $this->players[$key]->alive = false;
                    }
                }

                foreach ($this->points as $key => $point){
                    if(abs( $point->x - $player->x) < $player->getSize()
                        && abs($player->y - $point->y) < $player->getSize()){

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

                $data =
                [
                    'points' => array_values($points),
                    'players' => array_values($players),
                    'player' => $player
                ];

                $client->send(json_encode($data));
            }

        }

    }

    /**
     * @param ConnectionInterface $conn klient
     * @return void inicjalizuje gracza po otwarciu ws
     */
    public function onOpen(ConnectionInterface $conn) {

        $this->clients->attach($conn);
        $this->players[$conn->resourceId] = new Player();
        $this->players[$conn->resourceId]->mass = 700;
        $this->players[$conn->resourceId]->initialized = false;

    }

    /**
     * @param ConnectionInterface $from klient
     * @param $msg object wiadomosc od klienta
     * @return void obsluguje wiadomosci od graczy, wywoluje metode run() obslugujaca gre
     * @throws Exception gracz mogl wyslac zle zapytanie
     */
    public function onMessage(ConnectionInterface $from,  $msg) {

        $player = json_decode($msg);

        if ($player->x && $player->y && $player->color && $player->mass
            && $player->ctxHeight && $player->ctxWidth){

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

    /**
     * @param ConnectionInterface $conn klient
     * @return void zamkniecie socketu, usuwa gracza
     */
    public function onClose(ConnectionInterface $conn) {

        unset($this->players[$conn->resourceId]);
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * @param ConnectionInterface $conn klient
     * @param \Exception $e blad
     * @return void obsluguje blad, zamyka polaczenie
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {

        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

}