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
use pocketmine\block\Flower as FlowerBlock;
use pocketmine\level\generator\populator\Flower;
use pocketmine\level\generator\populator\WaterPit;
use pocketmine\level\generator\populator\Tree;
use pocketmine\level\generator\populator\TallGrass;
use pocketmine\block\Sapling;

class JungleBiome extends GrassyBiome {

    /**
     * JungleBiome constructor.
     */
    public function __construct(){
        parent::__construct();

        $trees = new Tree(Sapling::JUNGLE);
        $trees->setBaseAmount(8);
        $this->addPopulator($trees);
        $waterPit = new WaterPit();
        $waterPit->setBaseAmount(9999);

        $this->temperature = 0.95;
        $this->rainfall = 0.9;
        $this->setElevation(62, 65);

        $flower = new Flower();
        $flower->setBaseAmount(8);
        $flower->addType([Block::RED_FLOWER, FlowerBlock::TYPE_BLUE_ORCHID]);
        $this->addPopulator($flower);

        $tallGrass = new TallGrass();
        $tallGrass->setBaseAmount(3);
        $this->addPopulator($tallGrass);



        $this->setGroundCover([
            Block::get(BlockIds::GRASS),
            Block::get(BlockIds::DIRT),
            Block::get(BlockIds::DIRT),
            Block::get(BlockIds::DIRT),
        ]);
    }

    /**
     * @return string
     */
    public function getName() : string{
        return "Jungle";
    }
}