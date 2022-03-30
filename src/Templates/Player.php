<?php
namespace MyApp\Templates;

/**
 * gracz w grze
 */
class Player{

    /**
     * @var float wspolrzedna x gracza
     */
    public float $x;

    /**
     * @var float wspolrzedna x gracza
     */
    public float $y;

    /**
     * @var string kolor kulki gracza
     */
    public string $color;

    /**
     * @var float masa (wielkosc) gracza
     */
    public float $mass;

    /**
     * @var bool czy gracz zostal zainicjalizowany,
     * czyli czy chociaz raz uaktualniono jego wlasciwosci
     */
    public bool $initialized = false;

    /**
     * @var float szerokosc pola gry
     */
    public float $ctxWidth;

    /**
     * @var float wysokosc pola gry
     */
    public float $ctxHeight;

    public bool $alive = true;

    /**
     * @return float promien kulki gracza
     */
    public function getSize():float{
        return sqrt($this->mass/pi());
    }

    /**
     * @param float $mass masa zjedzona
     * @return void zjada, czyli zwieksza mase
     */
    public function eat(float $mass){
        $this->mass+=$mass;
    }
}