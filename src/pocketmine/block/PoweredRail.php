<?php


/* @author LunCore team
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

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class PoweredRail extends Rail {

	protected $id = self::POWERED_RAIL;
	/** @var Vector3 [] */
	protected $connected = [];

	/**
	 * PoweredRail constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;//0,1,2,3,4,5
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "PoweredRail";
	}

	/**
	 * @return bool
	 */
	protected function update(){

		return true;
	}

	/**
	 * @param Rail $block
	 *
	 * @return bool
	 */
	public function canConnect(Rail $block){
		if($this->distanceSquared($block) > 2){
			return false;
		}
		/** @var Vector3 [] $blocks */
		if(count($blocks = self::check($this)) == 2){
			return false;
		}
		if(isset($blocks[0])){
			$v3 = $blocks[0]->subtract($this);
			$v33 = $block->subtract($this);
			if(abs($v3->x) == abs($v33->z) and abs($v3->z) == abs($v33->x)){
				return false;
			}
		}
		return $blocks;
	}

	/**
	 * @param Block $block
	 *
	 * @return bool|Block
	 */
	public function isBlock(Block $block){
		if($block instanceof Air){
			return false;
		}
		return $block;
	}

	/**
	 * @param Rail $rail
	 * @param bool $force
	 *
	 * @return bool
	 */
	public function connect(Rail $rail, $force = false){

		if(!$force){
			$connected = $this->canConnect($rail);
			if(!is_array($connected)){
				return false;
			}
			/** @var Vector3 [] $connected */
			$connected[] = $rail;
			switch(count($connected)){
				case  1:
					$v3 = $connected[0]->subtract($this);
					$this->meta = (($v3->y != 1) ? ($v3->x == 0 ? 0 : 1) : ($v3->z == 0 ? ($v3->x / -2) + 2.5 : ($v3->z / 2) + 4.5));
					break;
				case 2:
					$subtract = [];
					foreach($connected as $key => $value){
						$subtract[$key] = $value->subtract($this);
					}
					if(abs($subtract[0]->x) == abs($subtract[1]->z) and abs($subtract[1]->x) == abs($subtract[0]->z)){
						$v3 = $connected[0]->subtract($this)->add($connected[1]->subtract($this));
						$this->meta = $v3->x == 1 ? ($v3->z == 1 ? 6 : 9) : ($v3->z == 1 ? 7 : 8);
					}elseif($subtract[0]->y == 1 or $subtract[1]->y == 1){
						$v3 = $subtract[0]->y == 1 ? $subtract[0] : $subtract[1];
						$this->meta = $v3->x == 0 ? ($v3->x == -1 ? 4 : 5) : ($v3->x == 1 ? 2 : 3);
					}else{
						$this->meta = $subtract[0]->x == 0 ? 0 : 1;
					}
					break;
				default:
					break;
			}
		}
		$this->level->setBlock($this, Block::get($this->id, $this->meta), true, true);
		return true;
	}

	/**
	 * @param Item        $item
	 * @param Block       $block
	 * @param Block       $target
	 * @param             $face
	 * @param             $fx
	 * @param             $fy
	 * @param             $fz
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$downBlock = $this->getSide(Vector3::SIDE_DOWN);

		if($downBlock instanceof Rail or !$this->isBlock($downBlock)){//判断是否可以放置
			return false;
		}

		$arrayXZ = [[1, 0], [0, 1], [-1, 0], [0, -1]];
		$arrayY = [0, 1, -1];

		/** @var Vector3 [] $connected */
		$connected = [];
		foreach($arrayXZ as $key => $xz){
			foreach($arrayY as $y){
				$v3 = (new Vector3($xz[0], $y, $xz[1]))->add($this);
				$block = $this->level->getBlock($v3);
				if($block instanceof Rail){
					if($block->connect($this)){
						$connected[] = $v3;
						//感觉这里怪怪的
						if($key <= 1){
							$xz = $arrayXZ[$key + 1];
							foreach($arrayY as $yy){
								$v3 = (new Vector3($xz[0], $yy, $xz[1]))->add($this);
								$block = $this->level->getBlock($v3);
								if($block instanceof Rail){
									if($block->connect($this)){
										$connected[] = $v3;
									}
								}
							}
						}
						break;
					}
				}
			}
			if(count($connected) >= 1){
				break;
			}
		}
		switch(count($connected)){
			case  1:
				$v3 = $connected[0]->subtract($this);
				$this->meta = (($v3->y != 1) ? ($v3->x == 0 ? 0 : 1) : ($v3->z == 0 ? ($v3->x / -2) + 2.5 : ($v3->z / 2) + 4.5));
				break;
			case 2:
				$subtract = [];
				foreach($connected as $key => $value){
					$subtract[$key] = $value->subtract($this);
				}
				if(abs($subtract[0]->x) == abs($subtract[1]->z) and abs($subtract[1]->x) == abs($subtract[0]->z)){
					$v3 = $connected[0]->subtract($this)->add($connected[1]->subtract($this));
					$this->meta = $v3->x == 1 ? ($v3->z == 1 ? 6 : 9) : ($v3->z == 1 ? 7 : 8);
				}elseif($subtract[0]->y == 1 or $subtract[1]->y == 1){
					$v3 = $subtract[0]->y == 1 ? $subtract[0] : $subtract[1];
					$this->meta = $v3->x == 0 ? ($v3->x == -1 ? 4 : 5) : ($v3->x == 1 ? 2 : 3);
				}else{
					$this->meta = $subtract[0]->x == 0 ? 0 : 1;
				}
				break;
			default:
				break;
		}

		$this->level->setBlock($this, Block::get($this->id, $this->meta), true, true);
		return true;
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.7;
	}

	/**
	 * @return bool
	 */
	public function canPassThrough(){
		return true;
	}
}