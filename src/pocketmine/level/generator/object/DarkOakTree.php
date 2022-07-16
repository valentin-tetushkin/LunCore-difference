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

class DarkOakTree extends Tree {
	/**
	 * DarkOakTree constructor.
	 */
	public function __construct(){
		$this->trunkBlock = BlockIds::WOOD2;
		$this->leafBlock = BlockIds::LEAVES2;
		$this->leafType = Leaves::DARK_OAK;
		$this->type = Wood2::DARK_OAK;
		$this->treeHeight = 8;
	}
}