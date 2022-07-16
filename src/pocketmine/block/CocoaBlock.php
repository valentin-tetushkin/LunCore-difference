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

use pocketmine\event\block\BlockGrowEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;

class CocoaBlock extends Transparent{

	protected $id = self::COCOA_BLOCK;

	/**
	 * CocoaBlock constructor.
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
		return "Cocoa Block";
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.2;
	}

	/**
	 * @return int
	 */
	public function getResistance(){
		return 15;
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
		if($item->getId() === ItemIds::DYE and $item->getDamage() === 0x0F){
			$block = clone $this;
			if($block->meta > 7){
				return false;
			}
			$block->meta += 4;
			Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($this, $block));
			if(!$ev->isCancelled()){
				$this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
			}
            $item->pop();
			return true;
		}
		return false;
	}

	/**
	 * @param int $type
	 *
	 * @return bool|int
	 */
	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$faces = [3, 4, 2, 5, 3, 4, 2, 5, 3, 4, 2, 5];
			if($this->getSide($faces[$this->meta])->isTransparent() === true){
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}elseif($type === Level::BLOCK_UPDATE_RANDOM){
			if(mt_rand(0, 45) === 1){
				if($this->meta <= 7){
					$block = clone $this;
					$block->meta += 4;
					Server::getInstance()->getPluginManager()->callEvent($ev = new BlockGrowEvent($this, $block));
					if(!$ev->isCancelled()){
						$this->getLevel()->setBlock($this, $ev->getNewState(), true, true);
					}else{
						return Level::BLOCK_UPDATE_RANDOM;
					}
				}
			}else{
				return Level::BLOCK_UPDATE_RANDOM;
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
		if($target->getId() === BlockIds::WOOD and $target->getDamage() === 3){
			if($face !== 0 and $face !== 1){
				$faces = [
					2 => 0,
					3 => 2,
					4 => 3,
					5 => 1,
				];
				$this->meta = $faces[$face];
				$this->getLevel()->setBlock($block, Block::get(BlockIds::COCOA_BLOCK, $this->meta), true);
				return true;
			}
		}
		return false;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		$drops = [];
		if($this->meta >= 8){
			$drops[] = [ItemIds::DYE, 3, 3];
		}else{
			$drops[] = [ItemIds::DYE, 3, 1];
		}
		return $drops;
	}
}
