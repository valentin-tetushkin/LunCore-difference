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
// use pocketmine\item\MushroomStew;
use pocketmine\block\Flower as FlowerBlock;
use pocketmine\level\generator\populator\Flower;
use pocketmine\level\generator\populator\LilyPad;
use pocketmine\level\generator\populator\WaterPit;
use pocketmine\level\generator\populator\Tree;
// use pocketmine\block\RedMushroom;
// use pocketmine\block\BrownMushroom;
use pocketmine\block\Sapling;

class SwampBiome extends GrassyBiome {

	/**
	 * SwampBiome constructor.
	 */
    public function __construct(){
        parent::__construct();

        $trees = new Tree(Sapling::OAK);
        $trees->setBaseAmount(1);
        $this->addPopulator($trees);
        $waterPit = new WaterPit();
        $waterPit->setBaseAmount(9999);

        $this->temperature = 0.8;
        $this->rainfall = 0.9;
        $this->setElevation(62, 65);

        $flower = new Flower();
        $flower->setBaseAmount(8);
        $flower->addType([Block::RED_FLOWER, FlowerBlock::TYPE_BLUE_ORCHID]);

        // $mushroom = new RedMushroom();
        //$mushroom->setBaseAmount(7);
        // $mushroom->addType([Block::RED_MUSHROOM]);

        // $mushroom2 = new BrownMushroom();
        // $mushroom2->setBaseAmount(7);
        // $mushroom2->addType([Block::BROWN_MUSHROOM]);

        // $this->addPopulator($mushroom);
        //  $this->addPopulator($mushroom2);

        $this->addPopulator($flower);

        $lilypad = new LilyPad();
        $lilypad->setBaseAmount(4);
        $this->addPopulator($lilypad);

        $this->setGroundCover([
            Block::get(BlockIds::GRASS),
            Block::get(BlockIds::DIRT),
            Block::get(BlockIds::DIRT),
            Block::get(BlockIds::DIRT),
            Block::get(BlockIds::DIRT),
            Block::get(BlockIds::DIRT),
        ]);
    }

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Swamp";
	}
}