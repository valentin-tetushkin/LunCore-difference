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
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Door extends Transparent implements ElectricalAppliance{

	/**
	 * @return bool
	 */
	public function isSolid(){
		return false;
	}

	/**
	 * @return bool
	 */
	public function canPassThrough(){
		return true;
	}

	/**
	 * @return int
	 */
	private function getFullDamage(){
		$damage = $this->getDamage();
		$isUp = ($damage & 0x08) > 0;

		if($isUp){
			$down = $this->getSide(Vector3::SIDE_DOWN)->getDamage();
			$up = $damage;
		}else{
			$down = $damage;
			$up = $this->getSide(Vector3::SIDE_UP)->getDamage();
		}

		$isRight = ($up & 0x01) > 0;

		return $down & 0x07 | ($isUp ? 8 : 0) | ($isRight ? 0x10 : 0);
	}

	/**
	 * @return AxisAlignedBB
	 */
	protected function recalculateBoundingBox(){

		$f = 0.1875;
		$damage = $this->getFullDamage();

		$bb = new AxisAlignedBB(
			$this->x,
			$this->y,
			$this->z,
			$this->x + 1,
			$this->y + 2,
			$this->z + 1
		);

		$j = $damage & 0x03;
		$isOpen = (($damage & 0x04) > 0);
		$isRight = (($damage & 0x10) > 0);

		if($j === 0){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + $f
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z + 1 - $f,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z,
					$this->x + $f,
					$this->y + 1,
					$this->z + 1
				);
			}
		}elseif($j === 1){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x + 1 - $f,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + $f,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z,
					$this->x + 1,
					$this->y + 1,
					$this->z + $f
				);
			}
		}elseif($j === 2){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z + 1 - $f,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + $f
					);
				}
			}else{
				$bb->setBounds(
					$this->x + 1 - $f,
					$this->y,
					$this->z,
					$this->x + 1,
					$this->y + 1,
					$this->z + 1
				);
			}
		}elseif($j === 3){
			if($isOpen){
				if(!$isRight){
					$bb->setBounds(
						$this->x,
						$this->y,
						$this->z,
						$this->x + $f,
						$this->y + 1,
						$this->z + 1
					);
				}else{
					$bb->setBounds(
						$this->x + 1 - $f,
						$this->y,
						$this->z,
						$this->x + 1,
						$this->y + 1,
						$this->z + 1
					);
				}
			}else{
				$bb->setBounds(
					$this->x,
					$this->y,
					$this->z + 1 - $f,
					$this->x + 1,
					$this->y + 1,
					$this->z + 1
				);
			}
		}

		return $bb;
	}


	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			if($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR and $this->getSide(Vector3::SIDE_UP) instanceof Door){ //Block underneath the door was broken

				$this->getLevel()->setBlock($this, new Air(), false, false);
				$this->getLevel()->setBlock($this->getSide(Vector3::SIDE_UP), new Air(), false);

				foreach($this->getDrops(Item::get(ItemIds::DIAMOND_PICKAXE)) as $drop){
					$this->getLevel()->dropItem($this, Item::get($drop[0], $drop[1], $drop[2]));
				}

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
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
		if($face === 1){
			$blockUp = $this->getSide(Vector3::SIDE_UP);
			$blockDown = $this->getSide(Vector3::SIDE_DOWN);
			if($blockUp->canBeReplaced() === false or $blockDown->isTransparent()){
				return false;
			}
			$direction = $player instanceof Player ? $player->getDirection() : 0;
			$face = [
				0 => 3,
				1 => 4,
				2 => 2,
				3 => 5
			];
			$next = $this->getSide($face[(($direction + 2) % 4)]);
			$next2 = $this->getSide($face[$direction]);
			$metaUp = 0x08;
			if($next->getId() === $this->getId() or (!$next2->isTransparent() and $next->isTransparent())){ //Door hinge
				$metaUp |= 0x01;
			}

			$this->setDamage($player->getDirection() & 0x03);
			$this->getLevel()->setBlock($block, $this, true, true); //Bottom
			$this->getLevel()->setBlock($blockUp, $b = Block::get($this->getId(), $metaUp), true); //Top
			return true;
		}

		return false;
	}

	/**
	 * @param Item $item
	 *
	 * @return bool
	 */
	public function onBreak(Item $item){
		if(($this->getDamage() & 0x08) === 0x08){
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === $this->getId()){
				$this->getLevel()->setBlock($down, new Air(), true);
			}
		}else{
			$up = $this->getSide(Vector3::SIDE_UP);
			if($up->getId() === $this->getId()){
				$this->getLevel()->setBlock($up, new Air(), true);
			}
		}
		$this->getLevel()->setBlock($this, new Air(), true);

		return true;
	}

	/**
	 * @return bool
	 */
	public function isOpened(){
		return (($this->getFullDamage() & 0x04) > 0);
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
		if(($this->getDamage() & 0x08) === 0x08){ //Top
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === $this->getId()){
				$meta = $down->getDamage() ^ 0x04;
				$this->level->setBlock($down, Block::get($this->getId(), $meta), true);
				$this->level->addSound(new DoorSound($this));
				return true;
			}

			return false;
		}else{
			$this->meta ^= 0x04;
			$this->level->setBlock($this, $this, true);
			$this->level->addSound(new DoorSound($this));
		}

		return true;
	}

	public function getAffectedBlocks() : array{
		if(($this->getDamage() & 0x08) === 0x08){
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if($down->getId() === $this->getId()){
				return [$this, $down];
			}
		}else{
			$up = $this->getSide(Vector3::SIDE_UP);
			if($up->getId() === $this->getId()){
				return [$this, $up];
			}
		}

		return parent::getAffectedBlocks();
	}
}