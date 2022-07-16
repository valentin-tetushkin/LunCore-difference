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

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\Player;

class EmptyMap extends Item{

    public function __construct(int $meta = 0, int $count = 1){
        parent::__construct(self::EMPTY_MAP, $meta, $count, "Empty Map");
    }

    public function getMaxStackSize() : int{
        return 1;
    }

    public function useOnAir(Player $player) : void{
        $item = self::get(self::FILLED_MAP);
        if($item instanceof FilledMap){
            $player->getInventory()->setItemInHand($item);
        }
    }
}