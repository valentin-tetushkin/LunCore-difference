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
use pocketmine\math\Vector3;
use pocketmine\Player;

class DoublePlant extends Flowable{
	const BITFLAG_TOP = 0x08;

	protected $id = self::DOUBLE_PLANT;

	const SUNFLOWER = 0;
	const LILAC = 1;
	const DOUBLE_TALLGRASS = 2;
	const LARGE_FERN = 3;
	const ROSE_BUSH = 4;
	const PEONY = 5;

	/**
	 * DoublePlant constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return bool
	 */
	public function canBeReplaced(){
		return $this->meta === 2 or $this->meta === 3; //grass or fern
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		static $names = [
			0 => "Sunflower",
			1 => "Lilac",
			2 => "Double Tallgrass",
			3 => "Large Fern",
			4 => "Rose Bush",
			5 => "Peony"
		];
		return $names[$this->meta & 0x07] ?? "";
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
		$id = $block->getSide(Vector3::SIDE_DOWN)->getId();
		if(($id === BlockIds::GRASS or $id === BlockIds::DIRT) and $block->getSide(Vector3::SIDE_UP)->canBeReplaced()){
			$this->getLevel()->setBlock($block, $this, false, false);
			$this->getLevel()->setBlock($block->getSide(Vector3::SIDE_UP), Block::get($this->id, $this->meta | self::BITFLAG_TOP), false, false);

			return true;
		}

		return false;
	}

	/**
	 * Returns whether this double-plant has a corresponding other half.
	 * @return bool
	 */
	public function isValidHalfPlant() : bool{
		if($this->meta & self::BITFLAG_TOP){
			$other = $this->getSide(Vector3::SIDE_DOWN);
		}else{
			$other = $this->getSide(Vector3::SIDE_UP);
		}

		return (
			$other->getId() === $this->getId() and
			($other->getDamage() & 0x07) === ($this->getDamage() & 0x07) and
			($other->getDamage() & self::BITFLAG_TOP) !== ($this->getDamage() & self::BITFLAG_TOP)
		);
	}

	public function onUpdate($type){
		if($type === Level::BLOCK_UPDATE_NORMAL){
			$down = $this->getSide(Vector3::SIDE_DOWN);
			if(!$this->isValidHalfPlant() or (($this->meta & self::BITFLAG_TOP) === 0 and $down->isTransparent())){
				$this->getLevel()->useBreakOn($this);

				return Level::BLOCK_UPDATE_NORMAL;
			}
		}

		return false;
	}

	public function onBreak(Item $item){
		if(parent::onBreak($item) and $this->isValidHalfPlant()){
			return $this->getLevel()->setBlock($this->getSide(($this->meta & self::BITFLAG_TOP) !== 0 ? Vector3::SIDE_DOWN : Vector3::SIDE_UP), Block::get(BlockIds::AIR));
		}

		return false;
	}

	public function getDrops(Item $item) : array{
		if($this->meta & self::BITFLAG_TOP){
			if(!$item->isShears() and ($this->meta === 2 or $this->meta === 3)){ //grass or fern
				if(mt_rand(0, 24) === 0){
					return [
						Item::get(ItemIds::SEEDS)
					];
				}

				return [];
			}
			
			return parent::getDrops($item);
		}

		return [];
	}

	public function getAffectedBlocks() : array{
		if($this->isValidHalfPlant()){
			return [$this, $this->getSide(($this->meta & self::BITFLAG_TOP) !== 0 ? Vector3::SIDE_DOWN : Vector3::SIDE_UP)];
		}

		return parent::getAffectedBlocks();
	}
}