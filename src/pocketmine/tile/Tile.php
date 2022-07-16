<?php


/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
 * @author LunCore team
 * @link http://vk.com/luncore
 * @creator vk.com/klainyt
 *
*/

namespace pocketmine\tile;

use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

abstract class Tile extends Position {

	const BREWING_STAND = "BrewingStand";
	const CHEST = "Chest";
	const DL_DETECTOR = "DayLightDetector";
	const ENCHANT_TABLE = "EnchantTable";
	const FLOWER_POT = "FlowerPot";
	const FURNACE = "Furnace";
	const MOB_SPAWNER = "MobSpawner";
	const SIGN = "Sign";
	const SKULL = "Skull";
	const ITEM_FRAME = "ItemFrame";
	const DISPENSER = "Dispenser";
	const DROPPER = "Dropper";
	const CAULDRON = "Cauldron";
	const HOPPER = "Hopper";
	const BEACON = "Beacon";
	const ENDER_CHEST = "EnderChest";
	const BED = "Bed";
	const DAY_LIGHT_DETECTOR = "DLDetector";
	const SHULKER_BOX = "ShulkerBox";
	const PISTON_ARM = "PistonArm";

	public static $tileCount = 1;

	private static $knownTiles = [];
	private static $shortNames = [];

	public $name;
	public $id;
	public $x;
	public $y;
	public $z;
	public $closed = false;
	public $namedtag;
	protected $lastUpdate;
	protected $server;
	protected $timings;

	public static function init(){
		self::registerTile(Beacon::class);
		self::registerTile(Bed::class);
		self::registerTile(BrewingStand::class);
		self::registerTile(Cauldron::class);
		self::registerTile(Chest::class);
		self::registerTile(Dispenser::class);
		self::registerTile(DLDetector::class);
		self::registerTile(Dropper::class);
		self::registerTile(EnchantTable::class);
		self::registerTile(EnderChest::class);
		self::registerTile(FlowerPot::class);
		self::registerTile(Furnace::class);
		self::registerTile(Hopper::class);
		self::registerTile(ItemFrame::class);
		self::registerTile(MobSpawner::class);
		self::registerTile(Sign::class);
		self::registerTile(Skull::class);
		self::registerTile(ShulkerBox::class);
		self::registerTile(PistonArm::class);
	}

	/**
	 * @param string      $type
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 * @param array       $args
	 *
	 * @return Tile
	 */
	public static function createTile($type, Level $level, CompoundTag $nbt, ...$args){
		if(isset(self::$knownTiles[$type])){
			$class = self::$knownTiles[$type];
			return new $class($level, $nbt, ...$args);
		}

		return null;
	}

    /**
     * @param string $className
     *
     * @return bool
     */
	public static function registerTile(string $className){
        try {
            $class = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
        }
        if(is_a($className, Tile::class, true) and !$class->isAbstract()){
			self::$knownTiles[$class->getShortName()] = $className;
			self::$shortNames[$className] = $class->getShortName();
			return true;
		}

		return false;
	}


	/**
	 * Returns the short save name
	 */
	public static function getSaveId() : string{
		if(!isset(self::$shortNames[static::class])){
			throw new \InvalidStateException("Tile is not registered");
		}

		return self::$shortNames[static::class];
	}

	/**
	 * Tile constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		$this->timings = Timings::getTileEntityTimings($this);

		$this->namedtag = $nbt;
		$this->server = $level->getServer();
		$this->setLevel($level);

		$this->name = "";
		$this->lastUpdate = microtime(true);
		$this->id = Tile::$tileCount++;
		$this->x = (int) $this->namedtag["x"];
		$this->y = (int) $this->namedtag["y"];
		$this->z = (int) $this->namedtag["z"];

		$this->getLevel()->addTile($this);
	}

	/**
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}

	public function saveNBT(){
		$this->namedtag->id = new StringTag("id", static::getSaveId());
		$this->namedtag->x = new IntTag("x", $this->x);
		$this->namedtag->y = new IntTag("y", $this->y);
		$this->namedtag->z = new IntTag("z", $this->z);
	}

	public function getBlock(){
		return $this->level->getBlockAt($this->x, $this->y, $this->z);
	}

	/**
	 * @return bool
	 */
	public function onUpdate(){
		return false;
	}

	public final function scheduleUpdate(){
		if($this->closed){
			throw new \InvalidStateException("Cannot schedule update on garbage tile " . get_class($this));
		}
		$this->level->updateTiles[$this->id] = $this;
	}

	public function isClosed() : bool{
		return $this->closed;
	}

	public function __destruct(){
		$this->close();
	}

	public function close(){
		if(!$this->closed){
			$this->closed = true;

			if($this->isValid()){
				$this->level->removeTile($this);
				$this->setLevel();
			}

			$this->namedtag = null;
		}
	}

	public function getName() : string{
		return $this->name;
	}

}
