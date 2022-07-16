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

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\Server;

class Sponge extends Solid {

	protected $id = self::SPONGE;
	protected $absorbRange = 6;

	/**
	 * Sponge constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.6;
	}

	public function absorbWater(){
		if(Server::getInstance()->absorbWater){
			$range = $this->absorbRange / 2;
			for($xx = -$range; $xx <= $range; $xx++){
				for($yy = -$range; $yy <= $range; $yy++){
					for($zz = -$range; $zz <= $range; $zz++){
						$block = $this->getLevel()->getBlock(new Vector3($this->x + $xx, $this->y + $yy, $this->z + $zz));
						if($block->getId() === BlockIds::WATER) $this->getLevel()->setBlock($block, Block::get(BlockIds::AIR), true, true);
						if($block->getId() === BlockIds::STILL_WATER) $this->getLevel()->setBlock($block, Block::get(BlockIds::AIR), true, true);
					}
				}
			}
		}
	}

	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type){
		if($this->meta == 0){
			if($type === Level::BLOCK_UPDATE_NORMAL){
				$blockAbove = $this->getSide(Vector3::SIDE_UP)->getId();
				$blockBeneath = $this->getSide(Vector3::SIDE_DOWN)->getId();
				$blockNorth = $this->getSide(Vector3::SIDE_NORTH)->getId();
				$blockSouth = $this->getSide(Vector3::SIDE_SOUTH)->getId();
				$blockEast = $this->getSide(Vector3::SIDE_EAST)->getId();
				$blockWest = $this->getSide(Vector3::SIDE_WEST)->getId();

				if($blockAbove === BlockIds::WATER ||
					$blockBeneath === BlockIds::WATER ||
					$blockNorth === BlockIds::WATER ||
					$blockSouth === BlockIds::WATER ||
					$blockEast === BlockIds::WATER ||
					$blockWest === BlockIds::WATER
				){
					$this->absorbWater();
					$this->getLevel()->setBlock($this, Block::get(BlockIds::SPONGE, 1), true, true);
					return Level::BLOCK_UPDATE_NORMAL;
				}
				if($blockAbove === BlockIds::STILL_WATER ||
					$blockBeneath === BlockIds::STILL_WATER ||
					$blockNorth === BlockIds::STILL_WATER ||
					$blockSouth === BlockIds::STILL_WATER ||
					$blockEast === BlockIds::STILL_WATER ||
					$blockWest === BlockIds::STILL_WATER
				){
					$this->absorbWater();
					$this->getLevel()->setBlock($this, Block::get(BlockIds::SPONGE, 1), true, true);
					return Level::BLOCK_UPDATE_NORMAL;
				}
			}
			return false;
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		static $names = [
			0 => "Sponge",
			1 => "Wet Sponge",
		];
		return $names[$this->meta & 0x0f];
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		return [
			[$this->id, $this->meta & 0x0f, 1],
		];
	}
}
