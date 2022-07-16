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

namespace pocketmine\block;


use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;

class Leaves2 extends Leaves {

	const WOOD_TYPE = self::WOOD2;

	protected $id = self::LEAVES2;

	/**
	 * Leaves2 constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		static $names = [
			self::ACACIA => "Acacia Leaves",
			self::DARK_OAK => "Dark Oak Leaves",
		];
		return $names[$this->meta & 0x01];
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getDrops(Item $item) : array{
		$drops = [];
		if($item->isShears() or $item->getEnchantmentLevel(Enchantment::TYPE_MINING_SILK_TOUCH) > 0){
			$drops[] = [$this->id, $this->meta & 0x01, 1];
		}else{
			$fortunel = $item->getEnchantmentLevel(Enchantment::TYPE_MINING_FORTUNE);
			$fortunel = min(3, $fortunel);
			$rates = [20, 16, 12, 10];
			if(mt_rand(1, $rates[$fortunel]) === 1){ //Saplings
				$drops[] = [BlockIds::SAPLING, ($this->meta & 0x01) | 0x04, 1];
			}
		}

		return $drops;
	}
}
