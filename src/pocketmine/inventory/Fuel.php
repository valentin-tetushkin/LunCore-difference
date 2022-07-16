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

namespace pocketmine\inventory;

use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

//TODO: remove this
abstract class Fuel {
	public static $duration = [
		ItemIds::COAL => 1600,
		BlockIds::COAL_BLOCK => 16000,
		BlockIds::TRUNK => 300,
		//Item::BROWN_MUSHROOM_BLOCK => 300,
		//Item::RED_MUSHROOM_BLOCK => 300,
		BlockIds::WOODEN_PLANKS => 300,
		BlockIds::SAPLING => 100,
		ItemIds::WOODEN_AXE => 200,
		ItemIds::WOODEN_PICKAXE => 200,
		ItemIds::WOODEN_SWORD => 200,
		ItemIds::WOODEN_SHOVEL => 200,
		ItemIds::WOODEN_HOE => 200,
		BlockIds::WOODEN_PRESSURE_PLATE => 300,
		ItemIds::STICK => 100,
		BlockIds::FENCE => 300,
		BlockIds::FENCE_GATE => 300,
		BlockIds::FENCE_GATE_SPRUCE => 300,
		BlockIds::FENCE_GATE_BIRCH => 300,
		BlockIds::FENCE_GATE_JUNGLE => 300,
		BlockIds::FENCE_GATE_ACACIA => 300,
		BlockIds::FENCE_GATE_DARK_OAK => 300,
		BlockIds::WOODEN_STAIRS => 300,
		BlockIds::SPRUCE_WOOD_STAIRS => 300,
		BlockIds::BIRCH_WOOD_STAIRS => 300,
		BlockIds::JUNGLE_WOOD_STAIRS => 300,
		BlockIds::TRAPDOOR => 300,
		BlockIds::WORKBENCH => 300,
		BlockIds::NOTEBLOCK => 300,
		BlockIds::BOOKSHELF => 300,
		BlockIds::CHEST => 300,
		BlockIds::TRAPPED_CHEST => 300,
		BlockIds::DAYLIGHT_SENSOR => 300,
		ItemIds::BUCKET => 20000,
		ItemIds::BLAZE_ROD => 2400,
	];

}