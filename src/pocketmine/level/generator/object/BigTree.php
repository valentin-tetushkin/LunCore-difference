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
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\utils\VectorIterator;

class BigTree extends Tree {
	public $overridable = [
		BlockIds::AIR => true,
		BlockIds::LEAVES => true,
		BlockIds::SAPLING => true
	];

	/** @var Random */
	private $random;
	private $trunkHeightMultiplier = 0.618;
	private $trunkHeight;
	private $leafAmount = 1;
	private $leafDistanceLimit = 5;
	private $widthScale = 1;
	private $branchSlope = 0.381;

	private $totalHeight;
	private $baseHeight = 5;

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
		if(!parent::canPlaceObject($level, $x, $y, $z, $random) or $level->getBlockIdAt($x, $y, $z) == BlockIds::WATER or $level->getBlockIdAt($x, $y, $z) == BlockIds::STILL_WATER){
			return false;
		}
		$base = new Vector3($x, $y, $z);
		$this->totalHeight = $this->baseHeight + $random->nextBoundedInt(12);
		$availableSpace = $this->getAvailableBlockSpace($level, $base, $base->add(0, $this->totalHeight - 1));
		if($availableSpace > $this->baseHeight or $availableSpace == -1){
			if($availableSpace != -1){
				$this->totalHeight = $availableSpace;
			}
			return true;
		}
		return false;
	}

	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 * @param Random       $random
	 */
	public function placeObject(ChunkManager $level, $x, $y, $z, Random $random){
		$this->random = $random;
		$this->trunkHeight = (int) ($this->totalHeight * $this->trunkHeightMultiplier);
		$leaves = $this->getLeafGroupPoints($level, $x, $y, $z);
		foreach($leaves as $leaf){
			/** @var Vector3 $leafGroup */
			$leafGroup = $leaf[0];
			$groupX = $leafGroup->getX();
			$groupY = $leafGroup->getY();
			$groupZ = $leafGroup->getZ();
			for($yy = $groupY; $yy < $groupY + $this->leafDistanceLimit; ++$yy){
				$this->generateGroupLayer($level, $groupX, $yy, $groupZ, $this->getLeafGroupLayerSize($yy - $groupY));
			}
		}
		$trunk = new VectorIterator($level, new Vector3($x, $y - 1, $z), new Vector3($x, $y + $this->trunkHeight, $z));
		while($trunk->valid()){
			$trunk->next();
			$pos = $trunk->current();
			$level->setBlockIdAt($pos->x, $pos->y, $pos->z, BlockIds::LOG);
		}
		$this->generateBranches($level, $x, $y, $z, $leaves);
	}

	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 *
	 * @return array
	 */
	private function getLeafGroupPoints(ChunkManager $level, $x, $y, $z){
		$amount = $this->leafAmount * $this->totalHeight / 13;
		$groupsPerLayer = (int) (1.382 + $amount * $amount);

		if($groupsPerLayer == 0){
			$groupsPerLayer = 1;
		}

		$trunkTopY = $y + $this->trunkHeight;
		$groups = [];
		$groupY = $y + $this->totalHeight - $this->leafDistanceLimit;
		$groups[] = [new Vector3($x, $groupY, $z), $trunkTopY];

		for($currentLayer = $this->totalHeight - $this->leafDistanceLimit; $currentLayer >= 0; $currentLayer--){
			$layerSize = $this->getRoughLayerSize($currentLayer);

			if($layerSize < 0){
				$groupY--;
				continue;
			}

			for($count = 0; $count < $groupsPerLayer; $count++){
				$scale = $this->widthScale * $layerSize * ($this->random->nextFloat() + 0.328);
				$randomOffset = Vector2::createRandomDirection($this->random)->multiply($scale);
				$groupX = (int) ($randomOffset->getX() + $x + 0.5);
				$groupZ = (int) ($randomOffset->getY() + $z + 0.5);
				$group = new Vector3($groupX, $groupY, $groupZ);
				if($this->getAvailableBlockSpace($level, $group, $group->add(0, $this->leafDistanceLimit)) != -1){
					continue;
				}
				$xOff = $x - $groupX;
				$zOff = $z - $groupZ;
				$horizontalDistanceToTrunk = sqrt($xOff * $xOff + $zOff * $zOff);
				$verticalDistanceToTrunk = $horizontalDistanceToTrunk * $this->branchSlope;
				$yDiff = (int) ($groupY - $verticalDistanceToTrunk);
                $base = min($yDiff, $trunkTopY);
				if($this->getAvailableBlockSpace($level, new Vector3($x, $base, $z), $group) == -1){
					$groups[] = [$group, $base];
				}
			}
			$groupY--;
		}
		return $groups;
	}

	/**
	 * @param int $y
	 *
	 * @return int
	 */
	private function getLeafGroupLayerSize(int $y){
		if($y >= 0 and $y < $this->leafDistanceLimit){
			return ($y != ($this->leafDistanceLimit - 1)) ? 3 : 2;
		}
		return -1;
	}

	/**
	 * @param ChunkManager $level
	 * @param int          $x
	 * @param int          $y
	 * @param int          $z
	 * @param int          $size
	 */
	private function generateGroupLayer(ChunkManager $level, int $x, int $y, int $z, int $size){
		for($xx = $x - $size; $xx <= $x + $size; $xx++){
			for($zz = $z - $size; $zz <= $z + $size; $zz++){
				$sizeX = abs($x - $xx) + 0.5;
				$sizeZ = abs($z - $zz) + 0.5;
				if(($sizeX * $sizeX + $sizeZ * $sizeZ) <= ($size * $size)){
					if(isset($this->overridable[$level->getBlockIdAt($xx, $y, $zz)])){
						$level->setBlockIdAt($xx, $y, $zz, BlockIds::LEAVES);
					}
				}
			}
		}
	}

	/**
	 * @param int $layer
	 *
	 * @return float
	 */
	private function getRoughLayerSize(int $layer) : float{
		$halfHeight = $this->totalHeight / 2;
		if($layer < ($this->totalHeight / 3)){
			return -1;
		}elseif($layer == $halfHeight){
			return $halfHeight / 4;
		}elseif($layer >= $this->totalHeight or $layer <= 0){
			return 0;
		}else{
			return sqrt($halfHeight * $halfHeight - ($layer - $halfHeight) * ($layer - $halfHeight)) / 2;
		}
	}

	/**
	 * @param ChunkManager $level
	 * @param int          $x
	 * @param int          $y
	 * @param int          $z
	 * @param array        $groups
	 */
	private function generateBranches(ChunkManager $level, int $x, int $y, int $z, array $groups){
		foreach($groups as $group){
			$baseY = $group[1];
			if(($baseY - $y) >= ($this->totalHeight * 0.2)){
				$base = new Vector3($x, $baseY, $z);
				$branch = new VectorIterator($level, $base, $group[0]);
				while($branch->valid()){
					$branch->next();
					$pos = $branch->current();
					$level->setBlockIdAt($pos->x, $pos->y, $pos->z, BlockIds::LOG);
					$level->updateBlockLight($pos->x, $pos->y, $pos->z);
				}
			}
		}
	}

	/**
	 * @param ChunkManager $level
	 * @param Vector3      $from
	 * @param Vector3      $to
	 *
	 * @return int
	 */
	private function getAvailableBlockSpace(ChunkManager $level, Vector3 $from, Vector3 $to){
		$count = 0;
		$iter = new VectorIterator($level, $from, $to);
		while($iter->valid()){
			$iter->next();
			$pos = $iter->current();
			if(!isset($this->overridable[$level->getBlockIdAt($pos->x, $pos->y, $pos->z)])){
				return $count;
			}
			$count++;
		}
		return -1;
	}
}