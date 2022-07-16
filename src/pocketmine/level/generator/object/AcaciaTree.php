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
use pocketmine\block\Leaves2;
use pocketmine\block\Wood2;

class AcaciaTree extends Tree {
	/**
	 * AcaciaTree constructor.
	 */
	public function __construct(){
		$this->trunkBlock = BlockIds::WOOD2;
		$this->leafBlock = BlockIds::LEAVES2;
		$this->leafType = Leaves::ACACIA;
		$this->type = Wood2::ACACIA;
		$this->treeHeight = 8;
	}

	/*public function placeObject(ChunkManager $level, $x, $y, $z, Random $random){
	}*/
	//TODO: rewrite
}