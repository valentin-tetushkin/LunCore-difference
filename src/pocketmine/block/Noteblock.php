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

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\sound\NoteblockSound;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Noteblock extends Solid implements ElectricalAppliance {
	protected $id = self::NOTEBLOCK;

	/**
	 * Noteblock constructor.
	 *
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return float
	 */
	public function getHardness(){
		return 0.8;
	}

	/**
	 * @return int
	 */
	public function getResistance(){
		return 4;
	}

	/**
	 * @return int
	 */
	public function getToolType(){
		return Tool::TYPE_AXE;
	}

	/**
	 * @return int
	 */
	public function getStrength(){
		if($this->meta < 24) $this->meta++;
		else $this->meta = 0;
		$this->getLevel()->setBlock($this, $this);
		return $this->meta;
	}

	/**
	 * @return int
	 */
	public function getInstrument(){
		$below = $this->getSide(Vector3::SIDE_DOWN);
		switch($below->getId()){
			case BlockIds::WOOD:
			case BlockIds::WOOD2:
			case BlockIds::WOODEN_PLANK:
			case BlockIds::WOODEN_SLABS:
			case BlockIds::DOUBLE_WOOD_SLABS:
			case BlockIds::OAK_WOODEN_STAIRS:
			case BlockIds::SPRUCE_WOODEN_STAIRS:
			case BlockIds::BIRCH_WOODEN_STAIRS:
			case BlockIds::JUNGLE_WOODEN_STAIRS:
			case BlockIds::ACACIA_WOODEN_STAIRS:
			case BlockIds::DARK_OAK_WOODEN_STAIRS:
			case BlockIds::FENCE:
			case BlockIds::FENCE_GATE:
			case BlockIds::FENCE_GATE_SPRUCE:
			case BlockIds::FENCE_GATE_BIRCH:
			case BlockIds::FENCE_GATE_JUNGLE:
			case BlockIds::FENCE_GATE_DARK_OAK:
			case BlockIds::FENCE_GATE_ACACIA:
			case BlockIds::SPRUCE_WOOD_STAIRS:
			case BlockIds::BOOKSHELF:
			case BlockIds::CHEST:
			case BlockIds::CRAFTING_TABLE:
			case BlockIds::SIGN_POST:
			case BlockIds::WALL_SIGN:
			case BlockIds::DOOR_BLOCK:
			case BlockIds::NOTEBLOCK:
				return NoteblockSound::INSTRUMENT_BASS;
			case BlockIds::SAND:
			case BlockIds::SOUL_SAND:
				return NoteblockSound::INSTRUMENT_TABOUR;
			case BlockIds::GLASS:
			case BlockIds::GLASS_PANE:
				return NoteblockSound::INSTRUMENT_CLICK;
			case BlockIds::STONE:
			case BlockIds::COBBLESTONE:
			case BlockIds::SANDSTONE:
			case BlockIds::MOSS_STONE:
			case BlockIds::BRICKS:
			case BlockIds::STONE_BRICK:
			case BlockIds::NETHER_BRICKS:
			case BlockIds::QUARTZ_BLOCK:
			case BlockIds::SLAB:
			case BlockIds::COBBLESTONE_STAIRS:
			case BlockIds::BRICK_STAIRS:
			case BlockIds::STONE_BRICK_STAIRS:
			case BlockIds::NETHER_BRICKS_STAIRS:
			case BlockIds::SANDSTONE_STAIRS:
			case BlockIds::QUARTZ_STAIRS:
			case BlockIds::COBBLESTONE_WALL:
			case BlockIds::NETHER_BRICK_FENCE:
			case BlockIds::BEDROCK:
			case BlockIds::GOLD_ORE:
			case BlockIds::IRON_ORE:
			case BlockIds::COAL_ORE:
			case BlockIds::LAPIS_ORE:
			case BlockIds::DIAMOND_ORE:
			case BlockIds::REDSTONE_ORE:
			case BlockIds::GLOWING_REDSTONE_ORE:
			case BlockIds::EMERALD_ORE:
			case BlockIds::FURNACE:
			case BlockIds::BURNING_FURNACE:
			case BlockIds::OBSIDIAN:
			case BlockIds::MONSTER_SPAWNER:
			case BlockIds::NETHERRACK:
			case BlockIds::ENCHANTING_TABLE:
			case BlockIds::END_STONE:
			case BlockIds::STAINED_CLAY:
			case BlockIds::COAL_BLOCK:
				return NoteblockSound::INSTRUMENT_BASS_DRUM;
		}
		return NoteblockSound::INSTRUMENT_PIANO;
	}

	/**
	 * @param Item        $item
	 * @param Player|null $player
	 *
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null){
		$up = $this->getSide(Vector3::SIDE_UP);
		if($up->getId() == 0){
			$this->getLevel()->addSound(new NoteblockSound($this, $this->getInstrument(), $this->getStrength()));
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Noteblock";
	}
}