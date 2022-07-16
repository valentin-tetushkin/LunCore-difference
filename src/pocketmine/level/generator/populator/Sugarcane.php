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

namespace pocketmine\level\generator\populator;

use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;

class Sugarcane extends Populator {
	/** @var ChunkManager */
	private $level;
	private $randomAmount;
	private $baseAmount;

	/**
	 * @param $amount
	 */
	public function setRandomAmount($amount){
		$this->randomAmount = $amount;
	}

	/**
	 * @param $amount
	 */
	public function setBaseAmount($amount){
		$this->baseAmount = $amount;
	}

	/**
	 * @param ChunkManager $level
	 * @param              $chunkX
	 * @param              $chunkZ
	 * @param Random       $random
	 *
	 * @return mixed|void
	 */
	public function populate(ChunkManager $level, $chunkX, $chunkZ, Random $random){
		$this->level = $level;
		$amount = $random->nextRange(0, $this->randomAmount + 1) + $this->baseAmount;
		for($i = 0; $i < $amount; ++$i){
			$x = $random->nextRange($chunkX * 16, $chunkX * 16 + 15);
			$z = $random->nextRange($chunkZ * 16, $chunkZ * 16 + 15);

			$y = $this->getHighestWorkableBlock($x, $z);
			$tallRand = $random->nextRange(0, 17);
			$yMax = $y + 2 + (int) ($tallRand > 10) + (int) ($tallRand > 15);
			if($y !== -1){
				for(; $y < 127 and $y < $yMax; $y++){
					if($this->canSugarcaneStay($x, $y, $z)){
						$this->level->setBlockIdAt($x, $y, $z, BlockIds::SUGARCANE_BLOCK);
						$this->level->setBlockDataAt($x, $y, $z, 1);
					}
				}
			}
		}
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $z
	 *
	 * @return bool
	 */
	private function canSugarcaneStay($x, $y, $z){
		$b = $this->level->getBlockIdAt($x, $y, $z);
		$below = $this->level->getBlockIdAt($x, $y - 1, $z);
		$water = false;
		foreach([$this->level->getBlockIdAt($x + 1, $y - 1, $z), $this->level->getBlockIdAt($x - 1, $y - 1, $z), $this->level->getBlockIdAt($x, $y - 1, $z + 1), $this->level->getBlockIdAt($x, $y - 1, $z - 1)] as $adjacent){
			if($adjacent === BlockIds::WATER or $adjacent === BlockIds::STILL_WATER){
				$water = true;
				break;
			}
		}
		return ($b === BlockIds::AIR) and ((($below === BlockIds::SAND or $below === BlockIds::GRASS) and $water) or ($below === BlockIds::SUGARCANE_BLOCK));
	}

	/**
	 * @param $x
	 * @param $z
	 *
	 * @return int
	 */
	private function getHighestWorkableBlock($x, $z){
		for($y = 127; $y >= 0; --$y){
			$b = $this->level->getBlockIdAt($x, $y, $z);
			if($b !== BlockIds::AIR and $b !== BlockIds::LEAVES and $b !== BlockIds::LEAVES2){
				break;
			}
		}

		return $y === 0 ? -1 : ++$y;
	}
}
