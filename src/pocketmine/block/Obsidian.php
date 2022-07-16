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
use pocketmine\math\Vector3;

class Obsidian extends Solid {

	protected $id = self::OBSIDIAN;

	/** @var Vector3 */
	private $temporalVector = null;

	/**
	 * Obsidian constructor.
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
		if($this->temporalVector === null){
			$this->temporalVector = new Vector3(0, 0, 0);
		}
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Obsidian";
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	/**
	 * @return int
	 */
	public function getHardness(){
		return 35; //50 in PC
	}

	/**
	 * @return float
	 */
	public function getBlastResistance() : float{
		return 6000;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		if($item->isPickaxe() >= 5){
			return [
				[BlockIds::OBSIDIAN, 0, 1],
			];
		}else{
			return [];
		}
	}

	/**
	 * @param Item $item
	 *
	 * @return mixed|void
	 */
	public function onBreak(Item $item){
		parent::onBreak($item);

		if($this->getLevel()->getServer()->netherEnabled){
			for($i = 0; $i <= 6; $i++){
				if($this->getSide($i)->getId() == self::PORTAL){
					break;
				}
				if($i == 6){
					return;
				}
			}
			$block = $this->getSide($i);
			if($this->getLevel()->getBlock($this->temporalVector->setComponents($block->x - 1, $block->y, $block->z))->getId() == BlockIds::PORTAL or
				$this->getLevel()->getBlock($this->temporalVector->setComponents($block->x + 1, $block->y, $block->z))->getId() == BlockIds::PORTAL
			){//x方向
				for($x = $block->x; $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $block->y, $block->z))->getId() == BlockIds::PORTAL; $x++){
					for($y = $block->y; $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y, $block->z))->getId() == BlockIds::PORTAL; $y++){
						$this->getLevel()->setBlock($this->temporalVector->setComponents($x, $y, $block->z), new Air());
					}
					for($y = $block->y - 1; $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y, $block->z))->getId() == BlockIds::PORTAL; $y--){
						$this->getLevel()->setBlock($this->temporalVector->setComponents($x, $y, $block->z), new Air());
					}
				}
				for($x = $block->x - 1; $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $block->y, $block->z))->getId() == BlockIds::PORTAL; $x--){
					for($y = $block->y; $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y, $block->z))->getId() == BlockIds::PORTAL; $y++){
						$this->getLevel()->setBlock($this->temporalVector->setComponents($x, $y, $block->z), new Air());
					}
					for($y = $block->y - 1; $this->getLevel()->getBlock($this->temporalVector->setComponents($x, $y, $block->z))->getId() == BlockIds::PORTAL; $y--){
						$this->getLevel()->setBlock($this->temporalVector->setComponents($x, $y, $block->z), new Air());
					}
				}
			}else{//z方向
				for($z = $block->z; $this->getLevel()->getBlock($this->temporalVector->setComponents($block->x, $block->y, $z))->getId() == BlockIds::PORTAL; $z++){
					for($y = $block->y; $this->getLevel()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() == BlockIds::PORTAL; $y++){
						$this->getLevel()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
					}
					for($y = $block->y - 1; $this->getLevel()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() == BlockIds::PORTAL; $y--){
						$this->getLevel()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
					}
				}
				for($z = $block->z - 1; $this->getLevel()->getBlock($this->temporalVector->setComponents($block->x, $block->y, $z))->getId() == BlockIds::PORTAL; $z--){
					for($y = $block->y; $this->getLevel()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() == BlockIds::PORTAL; $y++){
						$this->getLevel()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
					}
					for($y = $block->y - 1; $this->getLevel()->getBlock($this->temporalVector->setComponents($block->x, $y, $z))->getId() == BlockIds::PORTAL; $y--){
						$this->getLevel()->setBlock($this->temporalVector->setComponents($block->x, $y, $z), new Air());
					}
				}
			}
		}
	}
}