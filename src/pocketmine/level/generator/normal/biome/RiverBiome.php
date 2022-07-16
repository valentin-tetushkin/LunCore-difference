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

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\generator\populator\Sugarcane;
use pocketmine\level\generator\populator\TallGrass;

class RiverBiome extends NormalBiome{

	/**
	 * RiverBiome constructor.
	 */
	public function __construct(){
		$this->setGroundCover([
			Block::get(BlockIds::GRASS),
			Block::get(BlockIds::DIRT),
			Block::get(BlockIds::DIRT),
			Block::get(BlockIds::DIRT),
			Block::get(BlockIds::DIRT)
		]);

		$sugarcane = new Sugarcane();
		$sugarcane->setBaseAmount(8);
		$tallGrass = new TallGrass();
		$tallGrass->setBaseAmount(100);

		$this->addPopulator($sugarcane);
		$this->addPopulator($tallGrass);

        $this->setElevation(62, 65);

		$this->temperature = 0.5;
		$this->rainfall = 0.7;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "River";
	}
}
