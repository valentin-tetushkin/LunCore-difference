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
use pocketmine\block\Flower as FlowerBlock;
use pocketmine\level\generator\populator\Flower;
use pocketmine\level\generator\populator\LilyPad;
use pocketmine\level\generator\populator\Sugarcane;
use pocketmine\level\generator\populator\TallGrass;
use pocketmine\level\generator\populator\WaterPit;

class PlainBiome extends GrassyBiome {

	/**
	 * PlainBiome constructor.
	 */
	public function __construct(){
		parent::__construct();

		$sugarcane = new Sugarcane();
		$sugarcane->setBaseAmount(6);
		$tallGrass = new TallGrass();
		$tallGrass->setBaseAmount(12);
		$waterPit = new WaterPit();
		$waterPit->setBaseAmount(9999);
		$lilyPad = new LilyPad();
		$lilyPad->setBaseAmount(8);

		$flower = new Flower();
		$flower->setBaseAmount(2);
		$flower->addType([BlockIds::DANDELION, 0]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_POPPY]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_AZURE_BLUET]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_RED_TULIP]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_ORANGE_TULIP]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_WHITE_TULIP]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_PINK_TULIP]);
		$flower->addType([BlockIds::RED_FLOWER, FlowerBlock::TYPE_OXEYE_DAISY]);

		$this->addPopulator($sugarcane);
		$this->addPopulator($tallGrass);
		$this->addPopulator($flower);
		$this->addPopulator($waterPit);
		$this->addPopulator($lilyPad);

        $this->setElevation(62, 65);

		$this->temperature = 0.8;
		$this->rainfall = 0.4;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Plains";
	}
}
