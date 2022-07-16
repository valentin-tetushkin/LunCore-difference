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
use pocketmine\item\Tool;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;

abstract class Stair extends Transparent {

	protected function recalculateCollisionBoxes() : array{
		//TODO: handle corners

		$minYSlab = ($this->meta & 0x04) === 0 ? 0 : 0.5;
		$maxYSlab = $minYSlab + 0.5;

		$bbs = [
			new AxisAlignedBB(
				$this->x,
				$this->y + $minYSlab,
				$this->z,
				$this->x + 1,
				$this->y + $maxYSlab,
				$this->z + 1
			)
		];

		$minY = ($this->meta & 0x04) === 0 ? 0.5 : 0;
		$maxY = $minY + 0.5;

		$rotationMeta = $this->meta & 0x03;

		$minX = $minZ = 0;
		$maxX = $maxZ = 1;

		switch($rotationMeta){
			case 0:
				$minX = 0.5;
				break;
			case 1:
				$maxX = 0.5;
				break;
			case 2:
				$minZ = 0.5;
				break;
			case 3:
				$maxZ = 0.5;
				break;
		}

	    $bbs[] = new AxisAlignedBB(
			$this->x + $minX,
			$this->y + $minY,
			$this->z + $minZ,
			$this->x + $maxX,
			$this->y + $maxY,
			$this->z + $maxZ
		);

		return $bbs;
	}

	/**
	 * @param Item        $item
	 * @param Block       $block
	 * @param Block       $target
	 * @param int         $face
	 * @param float       $fx
	 * @param float       $fy
	 * @param float       $fz
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$faces = [
			0 => 0,
			1 => 2,
			2 => 1,
			3 => 3,
		];
		$this->meta = $player !== null ? $faces[$player->getDirection()] & 0x03 : 0;
		if(($fy > 0.5 and $face !== 1) or $face === 0){
			$this->meta |= 0x04; //Upside-down stairs
		}
		$this->getLevel()->setBlock($block, $this, true, true);

		return true;
	}

	/**
	 * @return int
	 */
	public function getHardness(){
		return 2;
	}

	/**
	 * @return int
	 */
	public function getResistance(){
		return 15;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->isPickaxe() >= Tool::TIER_WOODEN){
			return [
				[$this->getId(), 0, 1],
			];
		}else{
			return [];
		}
	}
}