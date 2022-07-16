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

class BirchTree extends Tree {

	protected $superBirch = false;

	/**
	 * BirchTree constructor.
	 *
	 * @param bool $superBirch
	 */
	public function __construct($superBirch = false){
		$this->trunkBlock = BlockIds::LOG;
		$this->leafBlock = BlockIds::LEAVES;
		$this->leafType = Leaves::BIRCH;
		$this->type = Wood::BIRCH;
		$this->superBirch = (bool) $superBirch;
	}

	/**
	 * @param ChunkManager $level
	 * @param              $x
	 * @param              $y
	 * @param              $z
	 * @param Random       $random
	 */
	public function placeObject(ChunkManager $level, $x, $y, $z, Random $random){
		$this->treeHeight = $random->nextBoundedInt(3) + 5;
		if($this->superBirch){
			$this->treeHeight += 5;
		}
		parent::placeObject($level, $x, $y, $z, $random);
	}
}