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
use pocketmine\block\Sapling;
use pocketmine\level\ChunkManager;
use pocketmine\utils\Random;

abstract class Tree {
	public $overridable = [
		BlockIds::AIR => true,
		BlockIds::SAPLING => true,
		BlockIds::LEAVES => true,
		BlockIds::SNOW_LAYER => true,
		BlockIds::LEAVES2 => true
	];

	public $type = 0;
	public $trunkBlock = BlockIds::LOG;
	public $leafBlock = BlockIds::LEAVES;
	public $treeHeight = 7;
	public $leafType = 0;

	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 * @param Random       $random
	 * @param int          $type
	 * @param bool         $noBigTree
	 */
	public static function growTree(ChunkManager $level, $x, $y, $z, Random $random, $type = 0, bool $noBigTree = true){
		switch($type){
			case Sapling::SPRUCE:
				$tree = new SpruceTree();
				break;
			case Sapling::BIRCH:
				if($random->nextBoundedInt(39) === 0){
					$tree = new BirchTree(true);
				}else{
					$tree = new BirchTree();
				}
				break;
			case Sapling::JUNGLE:
				$tree = new JungleTree();
				break;
			case Sapling::ACACIA:
				$tree = new AcaciaTree();
				break;
			case Sapling::DARK_OAK:
				$tree = new DarkOakTree();
				break;
			case Sapling::OAK:
			default:
				if(!$noBigTree and $random->nextRange(0, 9) === 0){
					$tree = new BigTree();
				}else{
					$tree = new OakTree();
				}
				break;
		}
		if($tree->canPlaceObject($level, $x, $y, $z, $random)){
			$tree->placeObject($level, $x, $y, $z, $random);
		}
	}


	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 * @param Random       $random
	 *
	 * @return bool
	 */
	public function canPlaceObject(ChunkManager $level, $x, $y, $z, Random $random){
		$radiusToCheck = 0;
		for($yy = 0; $yy < $this->treeHeight + 3; ++$yy){
			if($yy == 1 or $yy === $this->treeHeight){
				++$radiusToCheck;
			}
			for($xx = -$radiusToCheck; $xx < ($radiusToCheck + 1); ++$xx){
				for($zz = -$radiusToCheck; $zz < ($radiusToCheck + 1); ++$zz){
					if(!isset($this->overridable[$level->getBlockIdAt($x + $xx, $y + $yy, $z + $zz)])){
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 * @param Random       $random
	 */
	public function placeObject(ChunkManager $level, $x, $y, $z, Random $random){

		$this->placeTrunk($level, $x, $y, $z, $random, $this->treeHeight - 1);

		for($yy = $y - 3 + $this->treeHeight; $yy <= $y + $this->treeHeight; ++$yy){
			$yOff = $yy - ($y + $this->treeHeight);
			$mid = (int) (1 - $yOff / 2);
			for($xx = $x - $mid; $xx <= $x + $mid; ++$xx){
				$xOff = abs($xx - $x);
				for($zz = $z - $mid; $zz <= $z + $mid; ++$zz){
					$zOff = abs($zz - $z);
					if($xOff === $mid and $zOff === $mid and ($yOff === 0 or $random->nextBoundedInt(2) === 0)){
						continue;
					}
					if(!Block::$solid[$level->getBlockIdAt($xx, $yy, $zz)]){
						$level->setBlockIdAt($xx, $yy, $zz, $this->leafBlock);
						$level->setBlockDataAt($xx, $yy, $zz, $this->leafType);
					}
				}
			}
		}
	}

	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 * @param Random       $random
	 * @param              $trunkHeight
	 */
	protected function placeTrunk(ChunkManager $level, $x, $y, $z, Random $random, $trunkHeight){
		// The base dirt block
		$level->setBlockIdAt($x, $y - 1, $z, BlockIds::DIRT);

		for($yy = 0; $yy < $trunkHeight; ++$yy){
			$blockId = $level->getBlockIdAt($x, $y + $yy, $z);
			if(isset($this->overridable[$blockId])){
				$level->setBlockIdAt($x, $y + $yy, $z, $this->trunkBlock);
				$level->setBlockDataAt($x, $y + $yy, $z, $this->type);
			}
		}
	}
}