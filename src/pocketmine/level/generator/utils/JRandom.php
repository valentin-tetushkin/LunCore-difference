<?php


/*
 *
 *
 * @author LunCore team
 * @link http://vk.com/luncore
 *
 *
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 *
 *
 * @author LunCore team
 * @link http://vk.com/luncore
 *
 *
*/

namespace pocketmine\level\generator\utils;

use InvalidArgumentException;

class JRandom {

    /** @var bool */
    private bool $haveNextNextGaussian;

    /** @var int */
    private int $seed;

    public function __construct(int $seed = -1){
        if($seed === -1){
            $seed = time();
        }

        $this->setSeed($seed);
    }

    public function setSeed(int $seed) : void{
        $this->seed = ($seed ^ 0x5DEECE66D) & ((1 << 48) - 1);
        $this->haveNextNextGaussian = false;
    }

    public function nextInt(int $n, bool $arg = true) : int{
        if(!$arg){
            $this->next(32);
        }

        if($n <= 0){
            throw new InvalidArgumentException("n должен быть положительным");
        }
        if(($n & -$n) == $n){
            return ($n * $this->next(31)) >> 31;
        }
        do{
            $bits = $this->next(31);
            $val = $bits % $n;
        }while($bits - $val + ($n - 1) < 0);
        return $val;
    }

    protected function next(int $bits) : int{
        $this->seed = ($this->seed * 0x5DEECE66D + 0xB) & ((1 << 48) - 1);
        return $this->seed >> (48 - $bits);
    }

    public function nextLong() : int{
        return ($this->next(32) << 32) + $this->next(32);
    }

    public function nextBoolean() : bool{
        return $this->next(1) != 0;
    }

    public function nextFloat() : float{
        return $this->next(24) / (float) (1 << 24);
    }
}