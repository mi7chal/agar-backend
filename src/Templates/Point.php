<?php
namespace MyApp\Templates;

/**
 * punkt w grze
 */
class Point{

    /**
     * @var int wspolrzedna x punktu
     */
    public int $x;

    /**
     * @var int wspolrzedna y punktu
     */
    public int $y;

    /**
     * @var string kolor hex punktu
     */
    public string $color;

    /**
     * @var float|int masa, wielkosc punktu
     */
    public float $mass;

    /**
     * przypisuje x i y, przekazane do funkcji, losuje kolor i rozmiar
     * @param int $x wspolrzedna x
     * @param int $y wspolrzedna y
     */
    public function __construct(int $x, int $y) {
        $this->x=$x;
        $this->y=$y;
        $this->color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        $this->mass = rand(150, 450);
    }

    /**
     * zwraca x kontekstu, w ktorym punkt powininen byc wyrenderowany wzgledem gracza
     * @param float $x x gracza
     * @param float $width szerokosc pola gry gracza
     * @return float | int x ctx
     */
    public function getCtxX(float $x, float $width){
        return $this->x - $x + $width / 2;
    }

    /**
     * zwraca y kontekstu, w ktorym punkt powininen byc wyrenderowany wzgledem gracza
     * @param float $y y gracza
     * @param float $height wysokosc pola gry gracza
     * @return float | int y ctx
     */
    public function getCtxY(float $y, float $height){
        return $this->y - $y + $height / 2;
    }
}