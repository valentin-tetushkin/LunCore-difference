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
 * Автор биома - http://vk.com/KlainYT
 * @author LunCore team
 * @link http://vk.com/luncore
 *
 *
*/

namespace pocketmine\level\generator\normal\biome;

use pocketmine\block\BlockIds;
use pocketmine\level\generator\populator\TallGrass;
use pocketmine\level\generator\populator\Tree;
use pocketmine\block\Sapling;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\generator\normal\biome\NormalBiome;

class SavannaBiome extends NormalBiome {

	/**
	 * MountainsBiome constructor.
	 */
	public function __construct(){
		$this->setGroundCover([
			Block::get(BlockIds::GRASS),
			Block::get(BlockIds::DIRT),
			Block::get(BlockIds::DIRT),
			Block::get(BlockIds::DIRT),
			Block::get(BlockIds::DIRT),
		]);
		$trees = new Tree(Sapling::ACACIA);
		$trees->setBaseAmount(1);
		$this->addPopulator($trees);

		$this->temperature = 1.5;
		$this->rainfall = 0;
        $this->setElevation(62, 65);
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Savanna";
	}
}
