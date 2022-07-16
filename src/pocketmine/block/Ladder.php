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

use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;

class Ladder extends Transparent {

	protected $id = self::LADDER;

	/**
	 * Ladder constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Ladder";
	}

	/**
	 * @return bool
	 */
	public function hasEntityCollision(){
		return true;
	}

	public function canClimb() : bool{
		return true;
	}

	/**
	 * @return bool
	 */
	public function isSolid(){
		return false;
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.4;
	}

	/**
	 * @param Entity $entity
	 */
	public function onEntityCollide(Entity $entity){
		if($entity instanceof Living and $entity->asVector3()->floor()->distanceSquared($this) < 1){ //entity coordinates must be inside block
			$entity->resetFallDistance();
			$entity->onGround = true;
		}
	}

	/**
	 * @return AxisAlignedBB
	 */
	protected function recalculateBoundingBox(){
		$f = 0.1875;

		$minX = $minZ = 0;
		$maxX = $maxZ = 1;

		if($this->meta === 2){
			$minZ = 1 - $f;
		}elseif($this->meta === 3){
			$maxZ = $f;
		}elseif($this->meta === 4){
			$minX = 1 - $f;
		}elseif($this->meta === 5){
			$maxX = $f;
		}

		return new AxisAlignedBB(
			$this->x + $minX,
			$this->y,
			$this->z + $minZ,
			$this->x + $maxX,
			$this->y + 1,
			$this->z + $maxZ
		);
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
		if($target->isTransparent() === false){
			$faces = [
				2 => 2,
				3 => 3,
				4 => 4,
				5 => 5,
			];
			if(isset($faces[$face])){
				$this->meta = $faces[$face];
				$this->getLevel()->setBlock($block, $this, true, true);

				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type){
		$faces = [
			2 => 3,
			3 => 2,
			4 => 5,
			5 => 4,
		];
		/*if($this->getSide(0)->getId() === self::AIR){ //Replace with common break method
			Server::getInstance()->api->entity->drop($this, Item::get(LADDER, 0, 1));
			$this->getLevel()->setBlock($this, new Air(), true, true, true);
			return Level::BLOCK_UPDATE_NORMAL;
			}*/
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if(isset($faces[$this->meta])){
				if($this->getSide($faces[$this->meta])->getId() === self::AIR){
					$this->getLevel()->useBreakOn($this);
				}
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}
		return false;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_AXE;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		return [
			[$this->id, 0, 1],
		];
	}
}
