<?php
namespace MyApp\Templates;

class Player{
    public float $x;
    public float $y;
    public string $color;
    public float $mass;
    public bool $initialized = false;

//    public function __construct(){
//        $this->x=x;
//        $this->y=y;
//        $this->color=$this->color;
//        $
//    }

    public function getSize():float{
        return sqrt($this->mass/pi());
    }

    public function eat(float $mass){
        $this->mass+=$mass;
    }
}