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

namespace pocketmine\item;

use pocketmine\entity\MinecartTNT as MinecartTNTEntity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\block\Rail;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\Player;

class MinecartTNT extends Item {
    /**
	 * MinecartTNT constructor.
	 *
	 * @param int $meta
	 * @param int $count
	 */
    public function __construct($meta = 0, $count = 1){
        parent::__construct(self::MINECART_WITH_TNT, $meta, $count, 'Minecart TNT');
    }

    /**
     * @return int
     */
    public function getMaxStackSize() : int {
        return 1;
    }

    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
        if($target instanceof Rail){
            $entity = new MinecartTNTEntity($level, new CompoundTag('', [
                'Pos' => new ListTag('Pos', [new DoubleTag('', $block->getX()), new DoubleTag('', $block->getY()), new DoubleTag('', $block->GetZ())]),
                'Rotation' => new ListTag('Rotation', [new DoubleTag('', 0), new DoubleTag('', 0)])]));
            $entity->spawnToAll();
        }
        if($player instanceof Player){
            if($player->isSurvival()){
                $player->getInventory()->setItemInHand(Item::get(0));
            }
        }
        return true;
    }
}