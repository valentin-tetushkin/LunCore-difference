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

namespace pocketmine\level\generator\object;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\block\Leaves;
use pocketmine\block\Wood;
use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;

class SpruceTree extends Tree {

	/**
	 * SpruceTree constructor.
	 */
	public function __construct(){
		$this->trunkBlock = BlockIds::LOG;
		$this->leafBlock = BlockIds::LEAVES;
		$this->leafType = Leaves::SPRUCE;
		$this->type = Wood::SPRUCE;
		$this->treeHeight = 10;
	}

	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 * @param Random       $random
	 */
	public function placeObject(ChunkManager $level, $x, $y, $z, Random $random){
		$this->treeHeight = $random->nextBoundedInt(4) + 6;

		$topSize = $this->treeHeight - (1 + $random->nextBoundedInt(2));
		$lRadius = 2 + $random->nextBoundedInt(2);

		$this->placeTrunk($level, $x, $y, $z, $random, $this->treeHeight - $random->nextBoundedInt(3));

		$radius = $random->nextBoundedInt(2);
		$maxR = 1;
		$minR = 0;

		for($yy = 0; $yy <= $topSize; ++$yy){
			$yyy = $y + $this->treeHeight - $yy;

			for($xx = $x - $radius; $xx <= $x + $radius; ++$xx){
				$xOff = abs($xx - $x);
				for($zz = $z - $radius; $zz <= $z + $radius; ++$zz){
					$zOff = abs($zz - $z);
					if($xOff === $radius and $zOff === $radius and $radius > 0){
						continue;
					}

					if(!Block::$solid[$level->getBlockIdAt($xx, $yyy, $zz)]){
						$level->setBlockIdAt($xx, $yyy, $zz, $this->leafBlock);
						$level->setBlockDataAt($xx, $yyy, $zz, $this->type);
					}
				}
			}

			if($radius >= $maxR){
				$radius = $minR;
				$minR = 1;
				if(++$maxR > $lRadius){
					$maxR = $lRadius;
				}
			}else{
				++$radius;
			}
		}
	}


}