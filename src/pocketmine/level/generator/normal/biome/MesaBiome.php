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
use pocketmine\block\StainedClay;
use pocketmine\level\generator\populator\Cactus;
use pocketmine\level\generator\populator\DeadBush;

class MesaBiome extends SandyBiome {

    /**
     * MesaBiome constructor.
     */
    public function __construct(){
        parent::__construct();

        $cactus = new Cactus();
        $cactus->setBaseAmount(0);
        $cactus->setRandomAmount(5);
        $deadBush = new DeadBush();
        $cactus->setBaseAmount(2);
        $deadBush->setRandomAmount(10);

        $this->addPopulator($cactus);
        $this->addPopulator($deadBush);

        $this->setElevation(62, 65);

        $this->temperature = 2.0;
        $this->rainfall = 0.8;
        $this->setGroundCover([
            Block::get(BlockIds::HARDENED_CLAY),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_PINK),
            Block::get(BlockIds::HARDENED_CLAY),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_ORANGE),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_BLACK),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_GRAY),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_WHITE),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_ORANGE),
            Block::get(BlockIds::HARDENED_CLAY),
            Block::get(BlockIds::HARDENED_CLAY),
            Block::get(BlockIds::HARDENED_CLAY),
            Block::get(BlockIds::HARDENED_CLAY),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_YELLOW),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_BLACK),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_PINK),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_PINK),
            Block::get(BlockIds::RED_SANDSTONE),
            Block::get(BlockIds::STAINED_CLAY, StainedClay::CLAY_WHITE),
            Block::get(BlockIds::RED_SANDSTONE),
            Block::get(BlockIds::RED_SANDSTONE),
            Block::get(BlockIds::RED_SANDSTONE),
            Block::get(BlockIds::RED_SANDSTONE),
            Block::get(BlockIds::RED_SANDSTONE),
            Block::get(BlockIds::RED_SANDSTONE),
            Block::get(BlockIds::RED_SANDSTONE),
            Block::get(BlockIds::RED_SANDSTONE),
        ]);
    }

    /**
     * @return string
     */
    public function getName() : string{
        return "Mesa";
    }
} 