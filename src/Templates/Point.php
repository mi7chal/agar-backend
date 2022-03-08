<?php
namespace MyApp\Templates;

class Point{
    public int $x;
    public int $y;
    public string $color;
    public float $mass;

    public function __construct(int $x, int $y) {
        $this->x=$x;
        $this->y=$y;
        $this->color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        $this->mass = rand(150, 450);
    }

}