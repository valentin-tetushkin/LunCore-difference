<?php
/*
╔╗──╔╗╔╗╔╗─╔╗╔══╗╔══╗╔═══╗╔═══╗
║║──║║║║║╚═╝║║╔═╝║╔╗║║╔═╗║║╔══╝
║║──║║║║║╔╗─║║║──║║║║║╚═╝║║╚══╗
║║──║║║║║║╚╗║║║──║║║║║╔╗╔╝║╔══╝
║╚═╗║╚╝║║║─║║║╚═╗║╚╝║║║║║─║╚══╗
╚══╝╚══╝╚╝─╚╝╚══╝╚══╝╚╝╚╝─╚═══╝
*/

namespace pocketmine;


use pocketmine\plugin\Plugin;
use pocketmine\resources\resourcepacks\ResourcePack;
use pocketmine\scheduler\SendMapTask;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\NBT;
use pocketmine\network\SourceInterface;
use pocketmine\level\sound\LaunchSound;
use pocketmine\level\loadchunk\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\event\player\cheat\PlayerIllegalMoveEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\event\block\ItemFrameDropItemEvent;
use pocketmine\block\{Air, Bed, Block, Fire, PressurePlate};
use pocketmine\command\{Command, CommandSender};
use pocketmine\entity\{Animal, Arrow, Attribute, Boat, Effect, Entity, FishingHook, Human, Item as DroppedItem, Living, Minecart, Projectile};
use pocketmine\event\entity\{EntityCombustByEntityEvent, EntityDamageByBlockEvent, EntityDamageByEntityEvent, EntityDamageEvent, EntityShootBowEvent, ProjectileLaunchEvent};
use pocketmine\event\inventory\{CraftItemEvent, InventoryCloseEvent, InventoryPickupArrowEvent, InventoryPickupItemEvent};
use pocketmine\event\player\{PlayerAchievementAwardedEvent, PlayerAnimationEvent, PlayerBedEnterEvent, PlayerBedLeaveEvent, PlayerChatEvent, PlayerCommandPreprocessEvent, PlayerDeathEvent, PlayerDropItemEvent, PlayerExhaustEvent, PlayerGameModeChangeEvent, PlayerInteractEvent, PlayerItemConsumeEvent, PlayerJoinEvent, PlayerJumpEvent, PlayerKickEvent, PlayerLoginEvent, PlayerMoveEvent, PlayerPreLoginEvent, PlayerQuitEvent, PlayerRespawnEvent, PlayerTextPreSendEvent, PlayerToggleFlightEvent, PlayerToggleGlideEvent, PlayerToggleSneakEvent, PlayerToggleSprintEvent, PlayerTransferEvent, PlayerUseFishingRodEvent};
use pocketmine\event\server\{DataPacketReceiveEvent, DataPacketSendEvent};
use pocketmine\event\{TextContainer, Timings, TranslationContainer};
use pocketmine\inventory\{AnvilInventory, BaseTransaction, BigShapedRecipe, BigShapelessRecipe, DropItemTransaction, EnchantInventory, FurnaceInventory, Inventory, InventoryHolder, ShapedRecipe, ShapelessRecipe};
use pocketmine\item\{FilledMap, Item};
use pocketmine\level\{Level, Location, Position};
use pocketmine\math\{AxisAlignedBB, Vector3};
use pocketmine\nbt\tag\{ByteTag, CompoundTag, DoubleTag, FloatTag, IntTag, ListTag, LongTag, ShortTag, StringTag};
use pocketmine\network\mcpe\protocol\{CommandStepPacket, LevelSoundEventPacket, AdventureSettingsPacket, AnimatePacket, AvailableCommandsPacket, BatchPacket, ChangeDimensionPacket, ChunkRadiusUpdatedPacket, ContainerSetContentPacket, ContainerSetSlotPacket, DataPacket, DisconnectPacket, EntityEventPacket, InteractPacket, LevelEventPacket, MobEquipmentPacket, MovePlayerPacket, PlayerActionPacket, PlayStatusPacket, ProtocolInfo, ResourcePackChunkDataPacket, ResourcePackClientResponsePacket, ResourcePackDataInfoPacket, ResourcePacksInfoPacket, ResourcePackStackPacket, RespawnPacket, SetEntityMotionPacket, SetPlayerGameTypePacket, SetSpawnPositionPacket, SetTitlePacket, StartGamePacket, TakeItemEntityPacket, TextPacket, TransferPacket, UpdateAttributesPacket, UpdateBlockPacket};
use pocketmine\permission\{PermissibleBase, PermissionAttachment};
use pocketmine\tile\{ItemFrame, Spawnable};
use pocketmine\utils\{TextFormat, UUID};

/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, InventoryHolder, ChunkLoader, IPlayer {

	const SURVIVAL = 0;
	const CREATIVE = 1;
	const ADVENTURE = 2;
	const SPECTATOR = 3;
	const VIEW = Player::SPECTATOR;

	const CRAFTING_SMALL = 0;
	const CRAFTING_BIG = 1;
	const CRAFTING_ANVIL = 2;
	const CRAFTING_ENCHANT = 3;

	const RESOURCE_PACK_CHUNK_SIZE = 128 * 1024;

	/** @var SourceInterface */
	protected $interface;

	/** @var bool */
	public $playedBefore = false;
	public $spawned = false;
	public $loggedIn = false;
	public $gamemode;

	protected $windowCnt = 2;
	/** @var int[] */
	protected $windows = [];
	/** @var Inventory[] */
	protected $windowIndex = [];

	protected $messageCounter = 2;

	public $achievements = [];

	public $craftingType = self::CRAFTING_SMALL; 

	public $creationTime = 0;

	protected $randomClientId;

	protected $protocol;

	protected $connected = true;
	protected $ip;
	protected $removeFormat = false;
	protected $port;
	protected $username;
	protected $iusername;
	protected $displayName;
	protected $languageCode = "en_UK";
	protected $startAction = -1;
	/** @var Vector3|null */
	protected $sleeping = null;

	protected $deviceModel;
	protected $deviceOS;

	private $loaderId = null;

	protected $stepHeight = 0.6;

	public $usedChunks = [];
	protected $loadQueue = [];
	/** @var int */
	protected $nextChunkOrderRun = 5;

	/** @var bool[] map: raw UUID (string) => bool */
	protected $hiddenPlayers = [];

	private $ping = 0;

	/** @var Vector3 */
	protected $newPosition;

	/** @var bool */
	protected $isTeleporting = false;

	protected $viewDistance = -1;
	protected $chunksPerTick;
	protected $spawnThreshold;
	/** @var int */
	protected $spawnChunkLoadCount = 0;
	/** @var Position|null */
	private $spawnPosition = null;

	protected $inAirTicks = 0;

	protected $autoJump = true;
	protected $allowFlight = false;
	protected $flying = false;

	protected $allowMovementCheats = false;

	private $needACK = [];

	private $batchedPackets = [];

	/** @var PermissibleBase */
	private $perm = null;

	public $weatherData = [0, 0, 0];

	/** @var Vector3 */
	public $fromPos = null;
	private $portalTime = 0;
	protected $shouldSendStatus = false;
	/** @var  Position */
	private $shouldResPos;

	/** @var FishingHook */
	public $fishingHook = null;

	/** @var Position[] */
	public $selectedPos = [];
	/** @var Level[] */
	public $selectedLev = [];

	/** @var Item[] */
	protected $personalCreativeItems = [];

	/** @var int */
	protected $lastEnderPearlUse = 0;

	/**
	 * @param FishingHook $entity
	 *
	 * @return bool
	 */
	public function linkHookToPlayer(FishingHook $entity){
		if($entity->isAlive()){
			$this->setFishingHook($entity);
			$pk = new EntityEventPacket();
			$pk->eid = $this->getFishingHook()->getId();
			$pk->event = EntityEventPacket::FISH_HOOK_POSITION;
			$this->server->broadcastPacket($this->level->getPlayers(), $pk);
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function unlinkHookFromPlayer(){
		if($this->fishingHook instanceof FishingHook){
			$pk = new EntityEventPacket();
			$pk->eid = $this->fishingHook->getId();
			$pk->event = EntityEventPacket::FISH_HOOK_TEASE;
			$this->server->broadcastPacket($this->level->getPlayers(), $pk);
			$this->setFishingHook();
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isFishing(){
		return ($this->fishingHook instanceof FishingHook);
	}

	/**
	 * @return FishingHook
	 */
	public function getFishingHook(){
		return $this->fishingHook;
	}

	/**
	 * @param FishingHook|null $entity
	 */
	public function setFishingHook(FishingHook $entity = null){
		if($entity === null and $this->fishingHook instanceof FishingHook){
			$this->fishingHook->close();
		}

		if($entity !== null){
		    $entity->setOwningEntity($this);
        }

		$this->fishingHook = $entity;
	}

	/**
	 * @return mixed
	 */
	public function getDeviceModel(){
		return $this->deviceModel;
	}

	/**
	 * @return mixed
	 */
	public function getDeviceOS(){
		return $this->deviceOS;
	}

	/**
	 * @return Item
	 */
	public function getItemInHand(){
		return $this->inventory->getItemInHand();
	}

	/**
	 * @return TranslationContainer
	 */
	public function getLeaveMessage(){
		return new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.left", [
			$this->getDisplayName()
		]);
	}

	public function getClientId(){
		return $this->randomClientId;
	}

	/**
	 * @return bool
	 */
	public function isBanned(){
		return $this->server->getNameBans()->isBanned($this->getLowerCaseName());
	}

	/**
	 * @param bool $value
	 */
	public function setBanned($value){
		if($value === true){
			$this->server->getNameBans()->addBan($this->getName(), null, null, null);
			$this->kick(TextFormat::RED . "Вы были забанены!");
		}else{
			$this->server->getNameBans()->remove($this->getName());
		}
	}

	/**
	 * @return bool
	 */
	public function isWhitelisted() : bool{
		return $this->server->isWhitelisted($this->getLowerCaseName());
	}

	/**
	 * @param bool $value
	 */
	public function setWhitelisted($value){
		if($value === true){
			$this->server->addWhitelist($this->getLowerCaseName());
		}else{
			$this->server->removeWhitelist($this->getLowerCaseName());
		}
	}

	 /* @return UUID|null
	 */
	public function getUniqueId(){
		return parent::getUniqueId();
	}

	/**
	 * @return $this
	 */
	public function getPlayer(){
		return $this;
	}

	/**
	 * @return null
	 */
	public function getFirstPlayed(){
		return $this->namedtag instanceof CompoundTag ? $this->namedtag["firstPlayed"] : null;
	}

	/**
	 * @return null
	 */
	public function getLastPlayed(){
		return $this->namedtag instanceof CompoundTag ? $this->namedtag["lastPlayed"] : null;
	}

	/**
	 * @return bool
	 */
	public function hasPlayedBefore(){
		return $this->playedBefore;
	}

	/**
	 * @param $value
	 */
	public function setAllowFlight($value){
		$this->allowFlight = (bool) $value;
		$this->sendSettings();
	}

	/**
	 * @return bool
	 */
	public function getAllowFlight() : bool{
		return $this->allowFlight;
	}

	public function setFlying(bool $value){
		if($this->flying !== $value){
			$this->flying = $value;
			$this->resetFallDistance();
			$this->sendSettings();
		}
	}

	/**
	 * @return bool
	 */
	public function isFlying() : bool{
		return $this->flying;
	}

	/**
	 * @param $value
	 */
	public function setAutoJump($value){
		$this->autoJump = $value;
		$this->sendSettings();
	}

	/**
	 * @return bool
	 */
	public function hasAutoJump() : bool{
		return $this->autoJump;
	}

	/**
	 * @return bool
	 */
	public function allowMovementCheats() : bool{
		return $this->allowMovementCheats;
	}

	/**
	 * @param bool $value
	 */
	public function setAllowMovementCheats(bool $value = false){
		$this->allowMovementCheats = $value;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		if($this->spawned and $player->spawned and $this->isAlive() and $player->isAlive() and $player->getLevel() === $this->level and $player->canSee($this) and !$this->isSpectator()){
			parent::spawnTo($player);
		}
	}

	/**
	 * @return Server
	 */
	public function getServer(){
		return $this->server;
	}

	/**
	 * @return bool
	 */
	public function getRemoveFormat(){
		return $this->removeFormat;
	}

	/**
	 * @param bool $remove
	 */
	public function setRemoveFormat($remove = true){
		$this->removeFormat = (bool) $remove;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function canSee(Player $player) : bool{
		return !isset($this->hiddenPlayers[$player->getRawUniqueId()]);
	}

	/**
	 * @param Player $player
	 */
	public function hidePlayer(Player $player){
		if($player === $this){
			return;
		}
		$this->hiddenPlayers[$player->getRawUniqueId()] = true;
		$player->despawnFrom($this);
	}

	/**
	 * @param Player $player
	 */
	public function showPlayer(Player $player){
		if($player === $this){
			return;
		}
		unset($this->hiddenPlayers[$player->getRawUniqueId()]);
		if($player->isOnline()){
			$player->spawnTo($this);
		}
	}

	/**
	 * @param Entity $entity
	 *
	 * @return bool
	 */
	public function canCollideWith(Entity $entity) : bool{
		return false;
	}

	public function canBeCollidedWith() : bool{
		return !$this->isSpectator() and parent::canBeCollidedWith();
	}

	public function resetFallDistance(){
		parent::resetFallDistance();
		$this->inAirTicks = 0;
	}

	/**
	 * @return int
	 */
	public function getViewDistance() : int{
		return $this->viewDistance;
	}

	/**
	 * @return void
	 */
	public function setViewDistance(int $distance){
		$this->viewDistance = $this->server->getAllowedViewDistance($distance);

		$this->spawnThreshold = (int) (min($this->viewDistance, $this->server->getProperty("chunk-sending.spawn-radius", 4)) ** 2 * M_PI);

		$this->nextChunkOrderRun = 0;

		$pk = new ChunkRadiusUpdatedPacket();
		$pk->radius = $this->viewDistance;
		$this->dataPacket($pk);

		$this->server->getLogger()->debug("Setting view distance for " . $this->getName() . " to " . $this->viewDistance . " (requested " . $distance . ")");
	}

	/**
	 * @return bool
	 */
	public function isOnline() : bool{
		return $this->connected === true and $this->loggedIn === true;
	}

	/**
	 * @return bool
	 */
	public function isOp() : bool{
		return $this->server->isOp($this->getName());
	}

	/**
	 * @param bool $value
	 */
	public function setOp($value){
		if($value === $this->isOp()){
			return;
		}

		if($value){
			$this->server->addOp($this->getName());
		}else{
			$this->server->removeOp($this->getName());
		}

		$this->sendSettings();
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 */
	public function isPermissionSet($name){
		return $this->perm->isPermissionSet($name);
	}

	/**
	 * @param permission\Permission|string $name
	 *
	 * @return bool
	 *
	 * @throws \InvalidStateException if the player is closed
	 */
	public function hasPermission($name){
		/*if($this->closed){
			throw new \InvalidStateException("Trying to get permissions of closed player");
		}*/
		return $this->perm->hasPermission($name);
	}

	/**
	 * @param Plugin $plugin
	 * @param string $name
	 * @param bool   $value
	 *
	 * @return permission\PermissionAttachment|null
	 */
	public function addAttachment(Plugin $plugin, $name = null, $value = null){
		if($this->perm == null) return null;
		return $this->perm->addAttachment($plugin, $name, $value);
	}


	/**
	 * @param PermissionAttachment $attachment
	 *
	 * @return bool
	 */
	public function removeAttachment(PermissionAttachment $attachment){
		if($this->perm == null){
			return false;
		}
		$this->perm->removeAttachment($attachment);
		return true;
	}

	public function recalculatePermissions(){
		$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

		if($this->perm === null){
			return;
		}

		$this->perm->recalculatePermissions();

		if($this->spawned){
			if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
			    $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		    }
		    if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
			    $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		    }

			$this->sendCommandData();
		}
	}

	/**
	 * @return permission\PermissionAttachmentInfo[]
	 */
	public function getEffectivePermissions(){
		return $this->perm->getEffectivePermissions();
	}

	public function sendCommandData(){
		$data = [];
		$count = 0;
		foreach($this->server->getCommandMap()->getCommands() as $command){
				if (($cmdData = $command->generateCustomCommandData($this)) !== null){
					++$count;
					$data[$command->getName()]["versions"][0] = $cmdData;
				}
		}

		if($count > 0){
			$pk = new AvailableCommandsPacket();
			$pk->commands = json_encode($data);
			$this->dataPacket($pk);
		}
	}

	/**
	 * @param SourceInterface $interface
	 * @param string          $ip
	 * @param int             $port
	 */
	public function __construct(SourceInterface $interface, $ip, $port){
		$this->interface = $interface;
		$this->perm = new PermissibleBase($this);
		$this->namedtag = new CompoundTag();
		$this->server = Server::getInstance();
		$this->ip = $ip;
		$this->port = $port;
		$this->loaderId = Level::generateChunkLoaderId($this);
		$this->chunksPerTick = (int) $this->server->getProperty("chunk-sending.per-tick", 4);
		$this->spawnThreshold = (int) (($this->server->getProperty("chunk-sending.spawn-radius", 4) ** 2) * M_PI);
		$this->spawnPosition = null;
		$this->gamemode = $this->server->getGamemode();
		$this->setLevel($this->server->getDefaultLevel());
		$this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

		$this->uuid = null;
		$this->rawUUID = null;

		$this->creationTime = microtime(true);

		$this->allowMovementCheats = (bool) $this->server->getProperty("player.anti-cheat.allow-movement-cheats", false);
	}

	/**
	 * @param string $achievementId
	 */
	public function removeAchievement($achievementId){
		if($this->hasAchievement($achievementId)){
			$this->achievements[$achievementId] = false;
		}
	}

	/**
	 * @param string $achievementId
	 *
	 * @return bool
	 */
	public function hasAchievement($achievementId) : bool{
		if(!isset(Achievement::$list[$achievementId]) or !isset($this->achievements)){
			$this->achievements = [];

			return false;
		}

		return isset($this->achievements[$achievementId]) and $this->achievements[$achievementId] != false;
	}

	/**
	 * @return bool
	 */
	public function isConnected() : bool{
		return $this->connected === true;
	}

	/**
	 * Gets the "friendly" name to display of this player to use in the chat.
	 *
	 * @return string
	 */
	public function getDisplayName(){
		return $this->displayName;
	}

	/**
	 * @param string $name
	 */
	public function setDisplayName($name){
		$this->displayName = $name;
		if($this->spawned){
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $this->getSkinId(), $this->getSkinData());
		}
	}

	/**
	 * @param string $str
	 * @param string $skinId
	 */
	public function setSkin($str, $skinId){
		parent::setSkin($str, $skinId);
		if($this->spawned){
			$this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $skinId, $str);
		}
	}

	/**
	 * Gets the player IP address
	 *
	 * @return string
	 */
	public function getAddress() : string{
		return $this->ip;
	}

	/**
	 * @return int
	 */
	public function getPort() : int{
		return $this->port;
	}

	/**
	 * @return Position
	 */
	public function getNextPosition(){
		return $this->newPosition !== null ? new Position($this->newPosition->x, $this->newPosition->y, $this->newPosition->z, $this->level) : $this->getPosition();
	}

	/**
	 * @return bool
	 */
	public function isSleeping() : bool{
		return $this->sleeping !== null;
	}

	/**
	 * @return int
	 */
	public function getInAirTicks(){
		return $this->inAirTicks;
	}

	/**
	 * @param Level $targetLevel
	 *
	 * @return bool|void
	 */
	protected function switchLevel(Level $targetLevel){
		$oldLevel = $this->level;
		if(parent::switchLevel($targetLevel)){
			if($oldLevel !== null){
			    foreach($this->usedChunks as $index => $d){
				    Level::getXZ($index, $X, $Z);
				    $this->unloadChunk($X, $Z, $oldLevel);
			    }
			}

			$this->usedChunks = [];
			$this->loadQueue = [];

			$this->level->sendTime($this);

			if($targetLevel->getDimension() != $oldLevel->getDimension()){
				$pk = new ChangeDimensionPacket();
				$pk->dimension = $targetLevel->getDimension();
				$pk->x = $this->x;
				$pk->y = $this->y;
				$pk->z = $this->z;
				$this->dataPacket($pk);
				$pk1 = new PlayStatusPacket();
				$pk1->status = PlayStatusPacket::PLAYER_SPAWN;
				$this->dataPacket($pk1);
			}
			$targetLevel->getWeather()->sendWeather($this);

			if($this->spawned){
				$this->spawnToAll();
			}
		}
	}

	/**
	 * @return void
	 */
	private function unloadChunk($x, $z, Level $level = null){
		$level = $level ?? $this->level;
		$index = Level::chunkHash($x, $z);
		if(isset($this->usedChunks[$index])){
			foreach($level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this){
					$entity->despawnFrom($this);
				}
			}

			unset($this->usedChunks[$index]);
		}
		$level->unregisterChunkLoader($this, $x, $z);
		unset($this->loadQueue[$index]);
	}

	/**
	 * @return Position
	 */
	public function getSpawn(){
		if($this->hasValidSpawnPosition()){
			return $this->spawnPosition;
		}else{
			$level = $this->server->getDefaultLevel();

			return $level->getSafeSpawn();
		}
	}

	/**
	 * @return bool
	 */
	public function hasValidSpawnPosition() : bool{
		return $this->spawnPosition !== null and $this->spawnPosition->isValid();
	}

	public function sendChunk(int $x, int $z, BatchPacket $payload){
		if($this->connected === false){
			return;
		}

		$this->usedChunks[Level::chunkHash($x, $z)] = true;
		$this->dataPacket($payload);

		if($this->spawned){
			foreach($this->level->getChunkEntities($x, $z) as $entity){
				if($entity !== $this and !$entity->closed and $entity->isAlive()){
					$entity->spawnTo($this);
				}
			}
		}

		if($this->spawnChunkLoadCount !== -1 and ++$this->spawnChunkLoadCount >= $this->spawnThreshold){
			$this->doFirstSpawn();
		}
	}

	public function setPing($ping) {
		$this->ping = $ping;
	}
	
	public function getPing() {
		return $this->ping;
	}
	
	public function sendPing() {
		if ($this->ping <= 100) {
			$this->sendMessage("§l§7[§l§5Пинг§7] §fОтличное подключение§c ({$this->ping}ms)");
		} elseif ($this->ping <= 230) {
			$this->sendMessage("§l§7[§l§5Пинг§7] §fХорошее подключение§c ({$this->ping}ms)");
		} else {
			$this->sendMessage("§l§7[§l§5Пинг§7] §fУжасное подключение§c ({$this->ping}ms)");
		}
	}

	protected function sendNextChunk(){
		if(!$this->isConnected()){
			return;
		}

		Timings::$playerChunkSendTimer->startTiming();

		$count = 0;
		foreach($this->loadQueue as $index => $distance){
			if($count >= $this->chunksPerTick){
				break;
			}

			$X = null;
			$Z = null;
			Level::getXZ($index, $X, $Z);
			assert(is_int($X) and is_int($Z));

			++$count;

			$this->usedChunks[$index] = false;
			$this->level->registerChunkLoader($this, $X, $Z, false);

			if(!$this->level->populateChunk($X, $Z)){
				continue;
			}

			unset($this->loadQueue[$index]);
			$this->level->requestChunk($X, $Z, $this);
		}

		Timings::$playerChunkSendTimer->stopTiming();
	}

	protected function doFirstSpawn(){
		if($this->spawned){
			return;
		}
		$this->spawned = true;
		$this->setImmobile(false);

		$this->sendPotionEffects($this);
		
		$this->sendData($this);

		$pk = new PlayStatusPacket();
		$pk->status = PlayStatusPacket::PLAYER_SPAWN;
		$this->dataPacket($pk);

		$this->spawnChunkLoadCount = -1;

		$this->noDamageTicks = 60;

		foreach($this->usedChunks as $index => $hasSent){
			if(!$hasSent){
				continue;
			}
			Level::getXZ($index, $chunkX, $chunkZ);
			foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
				if($entity !== $this and !$entity->closed and $entity->isAlive()){
					$entity->spawnTo($this);
				}
			}
		}

		$this->allowFlight = (($this->gamemode == 3) or ($this->gamemode == 1));
		$this->setHealth($this->getHealth());

		$this->server->getPluginManager()->callEvent($ev = new PlayerJoinEvent($this, new TranslationContainer(TextFormat::YELLOW . "%multiplayer.player.joined", [
			$this->getDisplayName()
		])));

		$this->sendSettings();

		if(strlen(trim((string) $msg = $ev->getJoinMessage())) > 0){
			if($this->server->playerMsgType === Server:: PLAYER_MSG_TYPE_MESSAGE) $this->server->broadcastMessage($msg);
			elseif($this->server->playerMsgType === Server::PLAYER_MSG_TYPE_TIP) $this->server->broadcastTip(str_replace("@player", $this->getName(), $this->server->playerLoginMsg));
			elseif($this->server->playerMsgType === Server::PLAYER_MSG_TYPE_POPUP) $this->server->broadcastPopup(str_replace("@player", $this->getName(), $this->server->playerLoginMsg));
		}

		$this->server->onPlayerLogin($this);
		$this->spawnToAll();

		$this->level->getWeather()->sendWeather($this);

		if($this->server->dserverConfig["enable"] and $this->server->dserverConfig["queryAutoUpdate"]){
			$this->server->updateQuery();
		}

		if($this->getHealth() <= 0){
			$this->respawn();
		}

		if ($this->inventory !== null) {
			$this->inventory->sendContents($this);
		    $this->inventory->sendArmorContents($this);
            $this->offhandInventory->sendContents($this);
		}
	}

	protected function sendRespawnPacket(Vector3 $pos){
		$pk = new RespawnPacket();
		$pk->x = $pos->x;
		$pk->y = $pos->y + $this->baseOffset;
		$pk->z = $pos->z;
		$this->dataPacket($pk);
	}

	/**
	 * @return bool
	 */
	protected function orderChunks(){
		if(!$this->isConnected() or $this->viewDistance === -1){
			return;
		}

		Timings::$playerChunkOrderTimer->startTiming();

		$radius = $this->server->getAllowedViewDistance($this->viewDistance);
		$radiusSquared = $radius ** 2;

		$newOrder = [];
		$unloadChunks = $this->usedChunks;

        $centerX = $this->getFloorX() >> 4;
        $centerZ = $this->getFloorZ() >> 4;

		for($x = 0; $x < $radius; ++$x){
			for($z = 0; $z <= $x; ++$z){
				if(($x ** 2 + $z ** 2) > $radiusSquared){
					break;
				}

				/* Top right quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $x, $centerZ + $z)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				/* Top left quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $x - 1, $centerZ + $z)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				/* Bottom right quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $x, $centerZ - $z - 1)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				/* Bottom left quadrant */
				if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $x - 1, $centerZ - $z - 1)]) or $this->usedChunks[$index] === false){
					$newOrder[$index] = true;
				}
				unset($unloadChunks[$index]);

				if($x !== $z){
					/* Top right quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $z, $centerZ + $x)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Top left quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $z - 1, $centerZ + $x)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Bottom right quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $z, $centerZ - $x - 1)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);

					/* Bottom left quadrant mirror */
					if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $z - 1, $centerZ - $x - 1)]) or $this->usedChunks[$index] === false){
						$newOrder[$index] = true;
					}
					unset($unloadChunks[$index]);
				}
			}
		}

		foreach($unloadChunks as $index => $bool){
			Level::getXZ($index, $X, $Z);
			$this->unloadChunk($X, $Z);
		}

		$this->loadQueue = $newOrder;

		Timings::$playerChunkOrderTimer->stopTiming();

		return true;
	}

	/**
	 * Batch a Data packet into the channel list to send at the end of the tick
	 */
	public function batchDataPacket(DataPacket $packet) : bool{
		if($this->connected === false){
			return false;
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		$this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
		if($ev->isCancelled()){
			$timings->stopTiming();
			return false;
		}

		if(!isset($this->batchedPackets)){
			$this->batchedPackets = [];
		}

		$this->batchedPackets[] = clone $packet;
		$timings->stopTiming();
		return true;
	}

    /**
	 * @return bool|int
	 */
	public function dataPacket(DataPacket $packet, bool $needACK = false, bool $immediate = false){
		if(!$this->isConnected()){
			return false;
		}

		if(!$this->loggedIn and !$packet->canBeSentBeforeLogin()){
			throw new \InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getName() . " too early");
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try{
			$this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
			if($ev->isCancelled()){
				return false;
			}

			$identifier = $this->interface->putPacket($this, $packet, $needACK, $immediate);

			if($needACK and $identifier !== null){
				$this->needACK[$identifier] = false;
				return $identifier;
			}

			return true;
		}finally{
			$timings->stopTiming();
		}
	}

	/**
	 * @param DataPacket $packet
	 * @param bool       $needACK
	 *
	 * @return bool|int
	 */
	public function directDataPacket(DataPacket $packet, $needACK = false){
		if(!$this->isConnected()){
			return false;
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try{
		    $this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
		    if($ev->isCancelled()){
			    $timings->stopTiming();
			    return false;
		    }

		    $identifier = $this->interface->putPacket($this, $packet, $needACK, true);

		    if($needACK and $identifier !== null){
			    $this->needACK[$identifier] = false;
			    return $identifier;
		    }

		    return true;
		}finally{
			$timings->stopTiming();
		}
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return boolean
	 */
	public function sleepOn(Vector3 $pos){
		if(!$this->isOnline()){
			return false;
		}

		$pos = $pos->floor();
		$b = $this->level->getBlock($pos);

		$this->server->getPluginManager()->callEvent($ev = new PlayerBedEnterEvent($this, $b));
		if($ev->isCancelled()){
			return false;
		}

		if($b instanceof Bed){
			$b->setOccupied();
		}

		$this->sleeping = clone $pos;

		$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [$pos->x, $pos->y, $pos->z]);
		$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, true, self::DATA_TYPE_BYTE);

		$this->setSpawn($pos);

		$this->level->sleepTicks = 60;

		return true;
	}

	/**
	 * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a
	 * Position object
	 *
	 * @param Vector3|Position $pos
	 *
	 * @return void
	 */
	public function setSpawn(Vector3 $pos){
		if(!($pos instanceof Position)){
			$level = $this->level;
		}else{
			$level = $pos->getLevel();
		}
		$this->spawnPosition = new Position($pos->x, $pos->y, $pos->z, $level);
		$pk = new SetSpawnPositionPacket();
		$pk->x = $this->spawnPosition->getFloorX();
		$pk->y = $this->spawnPosition->getFloorY();
		$pk->z = $this->spawnPosition->getFloorZ();
		$pk->spawnType = SetSpawnPositionPacket::TYPE_PLAYER_SPAWN;
		$pk->spawnForced = false;
		$this->dataPacket($pk);
	}

	public function stopSleep(){
		if($this->sleeping instanceof Vector3){
			$b = $this->level->getBlock($this->sleeping);
			if($b instanceof Bed){
				$b->setOccupied(false);
			}
			$this->server->getPluginManager()->callEvent($ev = new PlayerBedLeaveEvent($this, $b));

			$this->sleeping = null;
			$this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0]);
			$this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false, self::DATA_TYPE_BYTE);


			$this->level->sleepTicks = 0;

			$pk = new AnimatePacket();
			$pk->eid = $this->id;
			$pk->action = PlayerAnimationEvent::WAKE_UP;
			$this->dataPacket($pk);
		}

	}

	/**
	 * @param string $achievementId
	 *
	 * @return bool
	 */
	public function awardAchievement($achievementId){
		if(isset(Achievement::$list[$achievementId]) and !$this->hasAchievement($achievementId)){
			foreach(Achievement::$list[$achievementId]["requires"] as $requirementId){
				if(!$this->hasAchievement($requirementId)){
					return false;
				}
			}
			$this->server->getPluginManager()->callEvent($ev = new PlayerAchievementAwardedEvent($this, $achievementId));
			if(!$ev->isCancelled()){
				$this->achievements[$achievementId] = true;
				Achievement::broadcast($this, $achievementId);

				return true;
			}else{
				return false;
			}
		}

		return false;
	}

	/**
	 * @return int
	 */
	public function getGamemode() : int{
		return $this->gamemode;
	}

	/**
	 * @internal
	 *
	 * Returns a client-friendly gamemode of the specified real gamemode
	 * This function takes care of handling gamemodes known to MCPE (as of 1.1.0.3, that includes Survival, Creative and Adventure)
	 *
	 * TODO: remove this when Spectator Mode gets added properly to MCPE
	 *
	 * @param int $gamemode
	 *
	 * @return int
	 */
	public static function getClientFriendlyGamemode(int $gamemode) : int{
		$gamemode &= 0x03;
		if($gamemode === Player::SPECTATOR){
			return Player::CREATIVE;
		}

		return $gamemode;
	}

	/**
	 * Sets the gamemode, and if needed, kicks the Player.
	 *
	 * @param bool $client if the client made this change in their GUI
	 */
	public function setGamemode(int $gm, bool $client = false){
		if($gm < 0 or $gm > 3 or $this->gamemode === $gm){
			return false;
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerGameModeChangeEvent($this, $gm));
		if($ev->isCancelled()){
			if($client){
				$this->sendGamemode();
			}
			return false;
		}

		if($this->server->autoClearInv){
			$this->inventory->clearAll();
			$this->offhandInventory->clearAll();
		}

		$this->gamemode = $gm;

		$this->allowFlight = $this->isCreative();
		if($this->isSpectator()){
			$this->setFlying(true);
			$this->keepMovement = true;
			$this->onGround = false;

			$this->sendPosition($this, null, null, MovePlayerPacket::MODE_TELEPORT);

			$this->despawnFromAll();

			$this->teleport($this->temporalVector->setComponents($this->x, $this->y + 0.1, $this->z));
		}else{
			$this->keepMovement = $this->allowMovementCheats;
			$this->checkGroundState(0, 0, 0, 0, 0, 0);
			if($this->isSurvival()){
				$this->setFlying(false);
			}
			$this->spawnToAll();
		}

		$this->namedtag->playerGameType = new IntTag("playerGameType", $this->gamemode);

		if(!$client){
			$this->sendGamemode();
		}else{
			Command::broadcastCommandMessage($this, new TranslationContainer("commands.gamemode.success.self", [Server::getGamemodeString($gm)]));
		}

		if($this->gamemode === Player::SPECTATOR){
			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			$this->dataPacket($pk);
		}else{
			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			$pk->slots = array_merge(Item::getCreativeItems(), $this->personalCreativeItems);
			$this->dataPacket($pk);
		}

		$this->sendSettings();

		return true;
	}

	/**
	 * @internal
	 * Sends the player's gamemode to the client.
	 */
	public function sendGamemode(){
		$pk = new SetPlayerGameTypePacket();
		$pk->gamemode = Player::getClientFriendlyGamemode($this->gamemode);
		$this->dataPacket($pk);
	}

	/**
	 * Sends all the option flags
	 *
	 * @return void
	 */
	public function sendSettings(){
		$pk = new AdventureSettingsPacket();
		$pk->flags = 0;
		$pk->worldImmutable = $this->isSpectator();
		$pk->noPvp = $this->isSpectator();
		$pk->autoJump = $this->autoJump;
		$pk->allowFlight = $this->allowFlight;
		$pk->noClip = $this->isSpectator();
		$pk->worldBuilder = !($this->isAdventure());
		$pk->isFlying = $this->flying;
		$pk->userPermission = ($this->isOp() ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
		$this->dataPacket($pk);
	}

	/**
	 * @return bool
	 */
	public function isSurvival() : bool{
		return ($this->gamemode & 0x01) === 0;
	}

	/**
	 * @return bool
	 */
	public function isCreative() : bool{
		return ($this->gamemode & 0x01) > 0;
	}

	/**
	 * @return bool
	 */
	public function isSpectator() : bool{
		return $this->gamemode === 3;
	}

	/**
	 * @return bool
	 */
	public function isAdventure() : bool{
		return ($this->gamemode & 0x02) > 0;
	}

	/**
	 * @return bool
	 */
	public function isFireProof() : bool{
		return $this->isCreative();
	}

	/**
	 * @return array
	 */
	public function getDrops(){
		if(!$this->isCreative()){
			return parent::getDrops();
		}

		return [];
	}

	/**
	 * @param int   $id
	 * @param int   $type
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function setDataProperty($id, $type, $value){
		if(parent::setDataProperty($id, $type, $value)){
			$this->sendData($this, [$id => $this->dataProperties[$id]]);
			return true;
		}

		return false;
	}

	/**
	 * @param $movX
	 * @param $movY
	 * @param $movZ
	 * @param $dx
	 * @param $dy
	 * @param $dz
	 */
	protected function checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz){
		if($this->isSpectator()){
			$this->onGround = false;
		}else{
			$bb = clone $this->boundingBox;
			$bb->minY = $this->y - 0.2;
			$bb->maxY = $this->y + 0.2;

			$this->onGround = $this->isCollided = count($this->level->getCollisionBlocks($bb, true)) > 0;
		}
	}

	public function canBeMovedByCurrents() : bool{
		return false;
	}

	public function startFishing(){
	    $f = 0.9;
        $nbt = Entity::createBaseNBT(
            $this->add(0, $this->getEyeHeight(), 0),
            new Vector3(
                -sin(deg2rad($this->yaw)) * cos(deg2rad($this->pitch)) * $f * $f,
                -sin(deg2rad($this->pitch)) * $f * $f,
                cos(deg2rad($this->yaw)) * cos(deg2rad($this->pitch)) * $f * $f
            ),
            $this->yaw,
            $this->pitch
        );
        $fishingHook = new FishingHook($this->level, $nbt, $this);
        $this->linkHookToPlayer($fishingHook);

        $fishingHook->spawnToAll();
        $this->level->addSound(new LaunchSound($this), $this->getViewers());
    }

    public function stopFishing(){
	    $this->unlinkHookFromPlayer();
    }

	protected function checkNearEntities(){
		foreach($this->level->getNearbyEntities($this->boundingBox->grow(0.5, 0.5, 0.5), $this) as $entity){
			$entity->scheduleUpdate();

			if(!$entity->isAlive()){
				continue;
			}

			if($entity instanceof Arrow and $entity->hadCollision){
				$item = Item::get(Item::ARROW, $entity->getPotionId(), 1);

				$add = false;
				if(!$this->server->allowInventoryCheats and !$this->isCreative()){
					if(!$this->getFloatingInventory()->canAddItem($item) or !$this->inventory->canAddItem($item)){
						continue;
					}
					$add = true;
				}

				$this->server->getPluginManager()->callEvent($ev = new InventoryPickupArrowEvent($this->inventory, $entity));
				if($ev->isCancelled()){
					continue;
				}

				$pk = new TakeItemEntityPacket();
				$pk->eid = $this->id;
				$pk->target = $entity->getId();
				$this->server->broadcastPacket($entity->getViewers(), $pk);

				if($add){
					$this->getFloatingInventory()->addItem(clone $item);
				}
				$entity->kill();
			}elseif($entity instanceof DroppedItem){
				if($entity->getPickupDelay() <= 0){
					$item = $entity->getItem();

					if($item instanceof Item){
						$add = false;
						if(!$this->server->allowInventoryCheats and !$this->isCreative()){
							if(!$this->getFloatingInventory()->canAddItem($item) or !$this->inventory->canAddItem($item)){
								continue;
							}
							$add = true;
						}

						$this->server->getPluginManager()->callEvent($ev = new InventoryPickupItemEvent($this->inventory, $entity));
						if($ev->isCancelled()){
							continue;
						}

						switch($item->getId()){
							case Item::WOOD:
								$this->awardAchievement("mineWood");

								break;
							case Item::DIAMOND:
								$this->awardAchievement("diamond");
								break;
						}

						$pk = new TakeItemEntityPacket();
						$pk->eid = $this->id;
						$pk->target = $entity->getId();
						$this->server->broadcastPacket($entity->getViewers(), $pk);

						if($add){
							$this->getFloatingInventory()->addItem(clone $item);
						}
						$entity->kill();
					}
				}
			}
		}
	}

	/**
	 * @param $tickDiff
	 */
	protected function processMovement($tickDiff){
		if(!$this->isAlive() or !$this->spawned or $this->newPosition === null or $this->isSleeping()){
			return;
		}

		assert($this->x !== null and $this->y !== null and $this->z !== null);
		assert($this->newPosition->x !== null and $this->newPosition->y !== null and $this->newPosition->z !== null);

		$newPos = $this->newPosition;
		$distanceSquared = $newPos->distanceSquared($this);

		$revert = false;

		if(($distanceSquared / ($tickDiff ** 2)) > 100){
			$this->server->getLogger()->debug($this->getName() . " moved too fast, reverting movement");
			$this->server->getLogger()->debug("Old position: " . $this->asVector3() . ", new position: " . $this->newPosition);
			$revert = true;
		}elseif(!$this->level->isInLoadedTerrain($newPos) or !$this->level->isChunkGenerated($newPos->getFloorX() >> 4, $newPos->getFloorZ() >> 4)){
			$revert = true;
			$this->nextChunkOrderRun = 0;
		}

		if(!$revert and $distanceSquared != 0){
			$dx = $newPos->x - $this->x;
			$dy = $newPos->y - $this->y;
			$dz = $newPos->z - $this->z;
			$expectedClipDistance = $this->ySize * (1 - self::STEP_CLIP_MULTIPLIER);
			$dy -= $expectedClipDistance;
			$this->move($dx, $dy, $dz);

			$diff = $this->distanceSquared($newPos) / $tickDiff ** 2;

			if($this->isSurvival() and $diff > 0.0625){
				$ev = new PlayerIllegalMoveEvent($this, $newPos);
				$ev->setCancelled($this->allowMovementCheats);

				$this->server->getPluginManager()->callEvent($ev);

				if(!$ev->isCancelled()){
					$revert = true;
					$this->server->getLogger()->debug($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidMove", [$this->getName()]));
					$this->server->getLogger()->debug("Old position: " . $this->asVector3() . ", new position: " . $this->newPosition . ", expected clip distance: $expectedClipDistance");
				}
			}

			if($diff > 0 and !$revert){
				$this->setPosition($newPos);
			}
		}

		$from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
		$to = $this->getLocation();

		$delta = (($this->lastX - $to->x) ** 2) + (($this->lastY - $to->y) ** 2) + (($this->lastZ - $to->z) ** 2);
		$deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

		if(!$revert and ($delta > 0.0001 or $deltaAngle > 1.0)){
			$this->lastX = $to->x;
			$this->lastY = $to->y;
			$this->lastZ = $to->z;

			$this->lastYaw = $to->yaw;
			$this->lastPitch = $to->pitch;

			$ev = new PlayerMoveEvent($this, $from, $to);
			$this->setMoving(true);

			$this->server->getPluginManager()->callEvent($ev);

			if(!($revert = $ev->isCancelled())){
				if($this->server->netherEnabled){
					if($this->isInsideOfPortal()){
						if($this->portalTime == 0){
							$this->portalTime = $this->server->getTick();
						}
					}else{
						$this->portalTime = 0;
					}
				}

				if($to->distanceSquared($ev->getTo()) > 0.01){ //If plugins modify the destination
					$this->teleport($ev->getTo());
				}else{
					$this->level->addEntityMovement($this->x >> 4, $this->z >> 4, $this->getId(), $this->x, $this->y + $this->baseOffset, $this->z, $this->yaw, $this->pitch, $this->yaw);

					$distance = sqrt((($from->x - $to->x) ** 2) + (($from->z - $to->z) ** 2));
					//TODO: check swimming (adds 0.015 exhaustion in MCPE)
					if($this->isSprinting()){
						$this->exhaust(0.1 * $distance, PlayerExhaustEvent::CAUSE_SPRINTING);
					}else{
						$this->exhaust(0.01 * $distance, PlayerExhaustEvent::CAUSE_WALKING);
					}
				}

				if($this->fishingHook instanceof FishingHook){
					if($this->distance($this->fishingHook) > 33 or $this->inventory->getItemInHand()->getId() !== Item::FISHING_ROD){
						$this->setFishingHook();
					}
				}
			}
		}

		if($revert){

			$this->lastX = $from->x;
			$this->lastY = $from->y;
			$this->lastZ = $from->z;

			$this->lastYaw = $from->yaw;
			$this->lastPitch = $from->pitch;

			$this->setPosition($from);
			$this->sendPosition($from, $from->yaw, $from->pitch, MovePlayerPacket::MODE_RESET);
		}else{
			if($distanceSquared != 0 and $this->nextChunkOrderRun > 20){
				$this->nextChunkOrderRun = 20;
			}
		}

		$this->newPosition = null;
	}

	public function fall($fallDistance) : void{
		if(!$this->flying){
			parent::fall($fallDistance);
		}
	}

	public function jump() : void{
		$this->server->getPluginManager()->callEvent(new PlayerJumpEvent($this));
		parent::jump();
	}

	/**
	 * @param Vector3 $mot
	 *
	 * @return bool
	 */
	public function setMotion(Vector3 $mot){
		if(parent::setMotion($mot)){
			if($this->chunk !== null){
				$this->level->addEntityMotion($this->chunk->getX(), $this->chunk->getZ(), $this->getId(), $this->motionX, $this->motionY, $this->motionZ);
				$pk = new SetEntityMotionPacket();
				$pk->eid = $this->id;
				$pk->motionX = $mot->x;
				$pk->motionY = $mot->y;
				$pk->motionZ = $mot->z;
				$this->dataPacket($pk);
			}

			return true;
		}
		return false;
	}


	protected function updateMovement(bool $teleport = false){

	}

	public $foodTick = 0;

	public $starvationTick = 0;

	public $foodUsageTime = 0;

	protected $moving = false;

	/**
	 * @param $moving
	 */
	public function setMoving($moving){
		$this->moving = $moving;
	}

	/**
	 * @return bool
	 */
	public function isMoving() : bool{
		return $this->moving;
	}

	/**
	 * @return void
	 */
	public function sendAttributes(bool $sendAll = false){
		$entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
		if(count($entries) > 0){
			$pk = new UpdateAttributesPacket();
			$pk->entityId = $this->id;
			$pk->entries = $entries;
			$this->dataPacket($pk);
			foreach($entries as $entry){
				$entry->markSynchronized();
			}
		}
	}

	public function onUpdate($currentTick){
		if(!$this->loggedIn){
			return false;
		}

		$tickDiff = $currentTick - $this->lastUpdate;

		if($tickDiff <= 0){
			return true;
		}

		$this->messageCounter = 2;

		$this->lastUpdate = $currentTick;

		$this->sendAttributes();

		if(!$this->isAlive() and $this->spawned){
			++$this->deadTicks;
			if($this->deadTicks >= $this->maxDeadTicks){
				$this->despawnFromAll();
			}
			return true;
		}

		$this->timings->startTiming();

		if($this->spawned){
			if($this->server->netherEnabled){
				if(($this->isCreative() or $this->isSurvival() and $this->server->getTick() - $this->portalTime >= 80) and $this->portalTime > 0){
					$netherLevel = null;
					if($this->server->isLevelLoaded($this->server->netherName) or $this->server->loadLevel($this->server->netherName)){
						$netherLevel = $this->server->getLevelByName($this->server->netherName);
					}

					if($netherLevel instanceof Level){
						if($this->getLevel() !== $netherLevel){
							$this->fromPos = $this->getPosition();
							$this->fromPos->x = ((int) $this->fromPos->x) + 0.5;
							$this->fromPos->z = ((int) $this->fromPos->z) + 0.5;
							$this->teleport($this->shouldResPos = $netherLevel->getSafeSpawn());
						}elseif($this->fromPos instanceof Position){
							if(!($this->getLevel()->isChunkLoaded($this->fromPos->x, $this->fromPos->z))){
								$this->getLevel()->loadChunk($this->fromPos->x, $this->fromPos->z);
							}
							$add = [1, 0, -1, 0, 0, 1, 0, -1];
							$tempos = null;
							for($j = 2; $j < 5; $j++){
								for($i = 0; $i < 4; $i++){
									if($this->fromPos->getLevel()->getBlock($this->temporalVector->fromObjectAdd($this->fromPos, $add[$i] * $j, 0, $add[$i + 4] * $j))->getId() === Block::AIR){
										if($this->fromPos->getLevel()->getBlock($this->temporalVector->fromObjectAdd($this->fromPos, $add[$i] * $j, 1, $add[$i + 4] * $j))->getId() === Block::AIR){
											$tempos = $this->fromPos->add($add[$i] * $j, 0, $add[$i + 4] * $j);
											break;
										}
									}
								}
								if($tempos != null){
									break;
								}
							}
							if($tempos === null){
								$tempos = $this->fromPos->add(mt_rand(-2, 2), 0, mt_rand(-2, 2));
							}
							$this->teleport($this->shouldResPos = $tempos);
							$add = null;
							$tempos = null;
							$this->fromPos = null;
						}else{
							$this->teleport($this->shouldResPos = $this->server->getDefaultLevel()->getSafeSpawn());
						}
						$this->portalTime = 0;
					}
				}
			}

			$this->processMovement($tickDiff);
			if($this->onGround){
				$this->inAirTicks = 0;
			}else{
				$this->inAirTicks += $tickDiff;
			}

			Timings::$timerEntityBaseTick->startTiming();
			$this->entityBaseTick($tickDiff);
			Timings::$timerEntityBaseTick->stopTiming();

			if(!$this->isSpectator() and $this->isAlive()){
				Timings::$playerCheckNearEntitiesTimer->startTiming();
				$this->checkNearEntities();
				Timings::$playerCheckNearEntitiesTimer->stopTiming();
			}

			if($this->isOnFire() or $this->lastUpdate % 10 == 0){
				if($this->isCreative() and !$this->isInsideOfFire()){
					$this->extinguish();
				}elseif($this->getLevel()->getWeather()->isRainy()){
					if($this->getLevel()->canBlockSeeSky($this)){
						$this->extinguish();
					}
				}
			}

			if($this->getTransactionQueue() !== null){
				$this->getTransactionQueue()->execute();
			}
		}

		$this->timings->stopTiming();

		if(count($this->messageQueue) > 0){
			$pk = new TextPacket();
			$pk->type = TextPacket::TYPE_RAW;
			$pk->message = implode("\n", $this->messageQueue);
			$this->dataPacket($pk);
			$this->messageQueue = [];
		}

		return true;
	}

	public function doFoodTick($tickDiff = 1){
		if($this->isSurvival()){
			parent::doFoodTick($tickDiff);
		}
	}

	public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float{
		if($this->isSurvival()){
			return parent::exhaust($amount, $cause);
		}

		return 0.0;
	}

	public function isHungry() : bool{
		return $this->isSurvival() and parent::isHungry();
	}

	/**
	 * @return void
	 */
	public function checkNetwork(){
		if(!$this->isOnline()){
			return;
		}

		if($this->nextChunkOrderRun !== PHP_INT_MAX and $this->nextChunkOrderRun-- <= 0){
			$this->nextChunkOrderRun = PHP_INT_MAX;
			$this->orderChunks();
		}

		if(count($this->loadQueue) > 0){
			$this->sendNextChunk();
		}

		if(count($this->batchedPackets) > 0){
			$this->server->batchPackets([$this], $this->batchedPackets, false);
			$this->batchedPackets = [];
		}
	}

	/**
	 * Returns whether the player can interact with the specified position. This checks distance and direction.
	 *
	 * @param float   $maxDiff defaults to half of the 3D diagonal width of a block
	 */
	public function canInteract(Vector3 $pos, float $maxDistance, float $maxDiff = M_SQRT3 / 2){
		$eyePos = $this->getPosition()->add(0, $this->getEyeHeight(), 0);
		if($eyePos->distanceSquared($pos) > $maxDistance ** 2){
			return false;
		}

		$dV = $this->getDirectionVector();
		$eyeDot = $dV->dot($eyePos);
		$targetDot = $dV->dot($pos);
		return ($targetDot - $eyeDot) >= -$maxDiff;
	}

	public function onPlayerPreLogin(){
		$pk = new PlayStatusPacket();
		$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
		$this->dataPacket($pk);

		$this->processLogin();
	}

	public function clearCreativeItems(){
		$this->personalCreativeItems = [];
	}

	/**
	 * @return array
	 */
	public function getCreativeItems() : array{
		return $this->personalCreativeItems;
	}

	/**
	 * @param Item $item
	 */
	public function addCreativeItem(Item $item){
		$this->personalCreativeItems[] = Item::get($item->getId(), $item->getDamage());
	}

	/**
	 * @param Item $item
	 */
	public function removeCreativeItem(Item $item){
		$index = $this->getCreativeItemIndex($item);
		if($index !== -1){
			unset($this->personalCreativeItems[$index]);
		}
	}

	/**
	 * @param Item $item
	 *
	 * @return int
	 */
	public function getCreativeItemIndex(Item $item) : int{
		foreach($this->personalCreativeItems as $i => $d){
			if($item->equals($d, !$item->isTool())){
				return $i;
			}
		}

		return -1;
	}

	protected function processLogin(){
		if(!$this->server->isWhitelisted($this->getLowerCaseName())){
			$this->close($this->getLeaveMessage(), "§l§6технические работы!");

			return;
		}elseif($this->getPing() >= 500){
		    $obv = mt_rand(70, 110);
		    $obs = mt_rand(40, 69);
		    $this->close($this->getLeaveMessage(), "(LunCore) - Проверьте интернет соединение!");
		    return;
		
		}elseif($this->isBanned() or $this->server->getIPBans()->isBanned($this->getAddress()) or $this->server->getCIDBans()->isBanned($this->randomClientId)){
			$this->close($this->getLeaveMessage(), TextFormat::RED . "Вы забанены");

			return;
		}

		if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
		}
		if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
			$this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
		}

		foreach($this->server->getOnlinePlayers() as $p){
			if($p !== $this and ($p->iusername === $this->iusername or $this->getUniqueId()->equals($p->getUniqueId()))){
				$this->close($this->getLeaveMessage(), "Игрок с данным ником уже играет, смените ник!");
				return;
			}
		}

		$this->setNameTag($this->getDisplayName());

		$nbt = $this->server->getOfflinePlayerData($this->username);
		$this->playedBefore = ($nbt["lastPlayed"] - $nbt["firstPlayed"]) > 1;
		if(!isset($nbt->NameTag)){
			$nbt->NameTag = new StringTag("NameTag", $this->username);
		}else{
			$nbt["NameTag"] = $this->username;
		}

		$this->gamemode = $nbt["playerGameType"] & 0x03;
		if($this->server->getForceGamemode()){
			$this->gamemode = $this->server->getGamemode();
			$nbt->playerGameType = new IntTag("playerGameType", $this->gamemode);
		}

		$this->allowFlight = $this->isCreative();
		$this->keepMovement = $this->isSpectator() || $this->allowMovementCheats();

		if(($level = $this->server->getLevelByName($nbt["Level"])) === null){
			$this->setLevel($this->server->getDefaultLevel());
			$nbt["Level"] = $this->level->getFolderName();
			$nbt["Pos"][0] = $this->level->getSafeSpawn()->x;
			$nbt["Pos"][1] = $this->level->getSafeSpawn()->y;
			$nbt["Pos"][2] = $this->level->getSafeSpawn()->z;
		}else{
			$this->setLevel($level);
		}

		if(!($nbt instanceof CompoundTag)){
			$this->close($this->getLeaveMessage(), "Некорректно переданы данные игрока!");

			return;
		}

		$this->achievements = [];

		/** @var ByteTag $achievement */
		foreach($nbt->Achievements as $achievement){
			$this->achievements[$achievement->getName()] = $achievement->getValue() > 0 ? true : false;
		}

		parent::__construct($this->level, $nbt);
		$this->loggedIn = true;

		if(!$this->isConnected()){
			return;
		}

		$this->dataPacket(new ResourcePacksInfoPacket());

		if(!$this->hasValidSpawnPosition()){
			if(isset($this->namedtag->SpawnLevel) and ($level = $this->server->getLevelByName((string) $this->namedtag["SpawnLevel"])) instanceof Level){
				$this->spawnPosition = new Position($this->namedtag["SpawnX"], $this->namedtag["SpawnY"], $this->namedtag["SpawnZ"], $level);
			}else{
				$this->spawnPosition = $this->level->getSafeSpawn();
			}
		}

		$spawnPosition = $this->getSpawn();

		$pk = new StartGamePacket();
		$pk->entityUniqueId = $this->id;
		$pk->entityRuntimeId = $this->id;
		$pk->playerGamemode = Player::getClientFriendlyGamemode($this->gamemode);
		$pk->x = $this->x;
		$pk->y = $this->y + $this->baseOffset;
		$pk->z = $this->z;
		$pk->pitch = $this->pitch;
		$pk->yaw = $this->yaw;
		$pk->seed = -1;
		$pk->dimension = $this->level->getDimension();
		$pk->worldGamemode = Player::getClientFriendlyGamemode($this->server->getGamemode());
		$pk->difficulty = $this->server->getDifficulty();
		$pk->spawnX = $spawnPosition->getFloorX();
		$pk->spawnY = $spawnPosition->getFloorY();
		$pk->spawnZ = $spawnPosition->getFloorZ();
		$pk->hasAchievementsDisabled = 1;
		$pk->dayCycleStopTime = -1; //TODO: implement this properly
		$pk->eduMode = 0;
		$pk->rainLevel = 0; //TODO: implement these properly
		$pk->lightningLevel = 0;
		$pk->commandsEnabled = 1;
		$pk->levelId = "";
		$pk->worldName = $this->server->getMotd();
		$this->dataPacket($pk);

		$this->server->getPluginManager()->callEvent($ev = new PlayerLoginEvent($this, "Plugin reason"));
		if($ev->isCancelled()){
			$this->close($this->getLeaveMessage(), $ev->getKickMessage());
			return;
		}

		$this->level->sendTime($this);

		$this->sendAttributes(true);
		$this->setNameTagVisible(true);
		$this->setNameTagAlwaysVisible(true);
		$this->setCanClimb(true);
		$this->setImmobile(); //disable pre-spawn movement

		$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logIn", [
			TextFormat::AQUA . $this->username . TextFormat::WHITE,
			$this->ip,
			$this->port,
			TextFormat::GREEN . $this->randomClientId . TextFormat::WHITE,
			$this->id,
			$this->level->getName(),
			round($this->x, 4),
			round($this->y, 4),
			round($this->z, 4)
		]));

		if($this->isOp()){
			$this->setRemoveFormat(false);
		}

		if($this->getUniqueId()->getVersion() !== 3){
			$this->close("", "(LunCore) - Проверьте интернет соединение!", true);
			$this->server->getNetwork()->blockAddress($ip);
		}
		
		if($this->getPing() < 5){
			$this->close("", "§(LunCore) - Проверьте интернет соединение!!", true);
			$this->server->getNetwork()->blockAddress($ip);
		}

		if($this->gamemode === Player::SPECTATOR){
			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			$this->dataPacket($pk);
		}else{
			$pk = new ContainerSetContentPacket();
			$pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
			$pk->slots = array_merge(Item::getCreativeItems(), $this->personalCreativeItems);
			$this->dataPacket($pk);
		}

		$this->sendCommandData();

		$this->level->getWeather()->sendWeather($this);

		$this->server->addOnlinePlayer($this);
	}

	/**
	 * @return mixed
	 */
	public function getProtocol(){
		return $this->protocol;
	}

	public function handleDataPacket(DataPacket $packet){
		if($this->connected === false){
			return;
		}

		if($packet::NETWORK_ID === 0xfe){
			/** @var BatchPacket $packet */
			$this->server->getNetwork()->processBatch($packet, $this);
			return;
		}

		$timings = Timings::getReceiveDataPacketTimings($packet);

		$timings->startTiming();

		$this->server->getPluginManager()->callEvent($ev = new DataPacketReceiveEvent($this, $packet));
		if($ev->isCancelled()){
			$timings->stopTiming();
			return;
		}

		switch($packet::NETWORK_ID){
            case ProtocolInfo::MAP_INFO_REQUEST_PACKET:
                foreach($this->getInventory()->getContents() as $content){
                    if($content instanceof FilledMap){
                        if($content->getMapId() === $this->uuid){
                            $this->dataPacket($content->getSavedData());

                            return;
                        }
                    }
                }

                $this->server->getScheduler()->scheduleAsyncTask(new SendMapTask($this, $packet->uuid, get_class($this->level->getProvider()), $this->level->getProvider()->getPath()));
                break;
			case ProtocolInfo::LEVEL_SOUND_EVENT_PACKET:
			    $this->level->addChunkPacket($packet->x >> 4, $packet->z >> 4, $packet);
			    break;
            case ProtocolInfo::LOGIN_PACKET:
				if($this->loggedIn){
					break;
				}

				$pk = new PlayStatusPacket();
				$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
				$this->dataPacket($pk);

				$this->username = TextFormat::clean($packet->username);
				$this->displayName = $this->username;
				$this->setNameTag($this->username);
				$this->iusername = strtolower($this->username);
				$this->protocol = $packet->protocol;
				$this->deviceModel = $packet->deviceModel;
				$this->deviceOS = $packet->deviceOS;

				$this->languageCode = $packet->languageCode;

				if($this->server->getConfigBoolean("online-mode", false) && $packet->identityPublicKey === null){
					$this->kick("disconnectionScreen.notAuthenticated", false);
					break;
				}

				if(count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() and $this->kick("disconnectionScreen.serverFull", false)){
					break;
				}

				if(!in_array($packet->protocol, ProtocolInfo::ACCEPTED_PROTOCOLS)){
					if($packet->protocol < ProtocolInfo::CURRENT_PROTOCOL){
						$message = "disconnectionScreen.outdatedClient";

						$pk = new PlayStatusPacket();
						$pk->status = PlayStatusPacket::LOGIN_FAILED_CLIENT;
						$this->directDataPacket($pk);
					}else{
						$message = "disconnectionScreen.outdatedServer";

						$pk = new PlayStatusPacket();
						$pk->status = PlayStatusPacket::LOGIN_FAILED_SERVER;
						$this->directDataPacket($pk);
					}
					$this->close("", $message, false);

					break;
				}

				if(!self::isValidUserName($packet->username)){
			        $this->close("", "disconnectionScreen.invalidName");
			        
			        return true;
		        }

				$this->randomClientId = $packet->clientId;

				$this->uuid = UUID::fromString($packet->clientUUID);
				$this->rawUUID = $this->uuid->toBinary();

				if((strlen($packet->skin) != 64 * 64 * 4) and (strlen($packet->skin) != 64 * 32 * 4)){
					$this->close("", "disconnectionScreen.invalidSkin");

					break;
				}

				$this->setSkin($packet->skin, $packet->skinId);

				$this->server->getPluginManager()->callEvent($ev = new PlayerPreLoginEvent($this, "Plugin reason"));
				if($ev->isCancelled()){
					$this->close("", $ev->getKickMessage());

					break;
				}

				$pk = new PlayStatusPacket();
				$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
				$this->directDataPacket($pk);

				$infoPacket = new ResourcePacksInfoPacket();
				$infoPacket->resourcePackEntries = $this->server->getResourcePackManager()->getResourceStack();
				$infoPacket->mustAccept = $this->server->getResourcePackManager()->resourcePacksRequired();
				$this->directDataPacket($infoPacket);

				/*if($this->isConnected()){
					$this->processLogin();
				}*/
				break;

			case ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET:
				switch($packet->status){
					case ResourcePackClientResponsePacket::STATUS_REFUSED:
						$this->close("", $this->server->getLanguage()->translateString("disconnectionScreen.refusedResourcePack"), true);
						break;
					case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
						$manager = $this->server->getResourcePackManager();
						foreach($packet->packIds as $uuid){

							$pack = $manager->getPackById($uuid);
							if(!($pack instanceof ResourcePack)){
								$this->close("", $this->server->getLanguage()->translateString("disconnectionScreen.unavailableResourcePack"), true);
								$this->server->getLogger()->debug("Got a resource pack request for unknown pack with UUID " . $uuid . ", available packs: " . implode(", ", $manager->getPackIdList()));
								
								return false;
							}

							$pk = new ResourcePackDataInfoPacket();
							$pk->packId = $pack->getPackId();
							$pk->maxChunkSize = self::RESOURCE_PACK_CHUNK_SIZE;
							$pk->chunkCount = (int) ceil($pack->getPackSize() / $pk->maxChunkSize);
							$pk->compressedPackSize = $pack->getPackSize();
							$pk->sha256 = $pack->getSha256();
							$this->dataPacket($pk);
						}
						break;
					case ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS:
						$pk = new ResourcePackStackPacket();
						$manager = $this->server->getResourcePackManager();
						$pk->resourcePackStack = $manager->getResourceStack();
				        $pk->mustAccept = false;
						$this->dataPacket($pk);
						break;
					case ResourcePackClientResponsePacket::STATUS_COMPLETED:
						$this->processLogin();
						break;
				}
				break;

			case ProtocolInfo::RESOURCE_PACK_CHUNK_REQUEST_PACKET:
				$manager = $this->server->getResourcePackManager();
				$pack = $manager->getPackById($packet->packId);
				if(!($pack instanceof ResourcePack)){
					$this->close("", "disconnectionScreen.resourcePack", true);
					return true;
				}

				$pk = new ResourcePackChunkDataPacket();
				$pk->packId = $pack->getPackId();
				$pk->chunkIndex = $packet->chunkIndex;
				$pk->data = $pack->getPackChunk(self::RESOURCE_PACK_CHUNK_SIZE * $packet->chunkIndex, self::RESOURCE_PACK_CHUNK_SIZE);
		        $pk->progress = (self::RESOURCE_PACK_CHUNK_SIZE * $packet->chunkIndex);
				$this->dataPacket($pk);
				break;

			case ProtocolInfo::MOVE_PLAYER_PACKET:
				if($this->linkedEntity instanceof Entity){
					$entity = $this->linkedEntity;
					if($entity instanceof Boat){
						$entity->setPosition($this->temporalVector->setComponents($packet->x, $packet->y - 0.3, $packet->z));
					}
					/*if($entity instanceof Minecart){
						$entity->isFreeMoving = true;
						$entity->motionX = -sin($packet->yaw / 180 * M_PI);
						$entity->motionZ = cos($packet->yaw / 180 * M_PI);
					}*/
				}

				$newPos = new Vector3($packet->x, $packet->y - $this->baseOffset, $packet->z);

				if($newPos->distanceSquared($this) < 0.0001 and ($packet->yaw % 360) === $this->yaw and ($packet->pitch % 360) === $this->pitch){ //player hasn't moved, just client spamming packets
					break;
				}

				if($this->isTeleporting and $newPos->distanceSquared($this) > 1){  //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
			        $this->sendPosition($this, null, null, MovePlayerPacket::MODE_RESET);
			        $this->server->getLogger()->debug("Got outdated pre-teleport movement from " . $this->getName() . ", received " . $newPos . ", expected " . $this->asVector3());
			        //Still getting movements from before teleport, ignore them
		        }elseif((!$this->isAlive() or !$this->spawned) and $newPos->distanceSquared($this) > 0.01){
					$this->sendPosition($this, null, null, MovePlayerPacket::MODE_RESET);
					$this->server->getLogger()->debug("Reverted movement of " . $this->getName() . " due to not alive or not spawned, received " . $newPos . ", locked at " . $this->asVector3());
				}else{
					// Once we get a movement within a reasonable distance, treat it as a teleport ACK and remove position lock
			        if($this->isTeleporting){
			 	        $this->isTeleporting = false;
			        }

					$packet->yaw = fmod($packet->yaw, 360);
			        $packet->pitch = fmod($packet->pitch, 360);

					if($packet->yaw < 0){
						$packet->yaw += 360;
					}

					$this->setRotation($packet->yaw, $packet->pitch);
					$this->newPosition = $newPos;
				}

				break;
			case ProtocolInfo::ADVENTURE_SETTINGS_PACKET:
				//TODO: player abilities, check for other changes
				$isCheater = ($this->allowFlight === false && ($packet->flags >> 9) & 0x01 === 1) || (!$this->isSpectator() && ($packet->flags >> 7) & 0x01 === 1);
				if(($packet->isFlying !== $this->isFlying()) or $isCheater){
					$this->server->getPluginManager()->callEvent($ev = new PlayerToggleFlightEvent($this, $packet->isFlying));
					if($packet->isFlying and !$this->allowFlight){
				        $ev->setCancelled();
			        }

					if($ev->isCancelled()){
						$this->sendSettings();
					}else{ //don't use setFlying() here, to avoid feedback loops
						$this->flying = $ev->isFlying();
						$this->resetFallDistance();
					}
					break;
				}
				break;
			case ProtocolInfo::MOB_EQUIPMENT_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}

                /** @var MobEquipmentPacket $packet */
			    if($packet->windowId === 119){ // Offhand
			        if($this->offhandInventory !== null){
			            $transaction = new BaseTransaction($this->offhandInventory, 0, $packet->item);
			            $this->getTransactionQueue()->addTransaction($transaction);
                    }
			        break;
                }


				/**
				 * Handle hotbar slot remapping
				 * This is the only time and place when hotbar mapping should ever be changed.
				 * Changing hotbar slot mapping at will has been deprecated because it causes far too many
				 * issues with Windows 10 Edition Beta.
				 */
				$this->inventory->setHeldItemIndex($packet->selectedSlot, false, $packet->slot);

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
				break;
			case ProtocolInfo::USE_ITEM_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}

				$blockVector = new Vector3($packet->x, $packet->y, $packet->z);

				$this->craftingType = self::CRAFTING_SMALL;

				if($packet->face >= 0 and $packet->face <= 5){ //Use Block, place
					$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);

					if(!$this->canInteract($blockVector->add(0.5, 0.5, 0.5), 13) or $this->isSpectator()){

					}elseif($this->isCreative()){
						$item = $this->inventory->getItemInHand();
						if($this->level->useItemOn($blockVector, $item, $packet->face, $packet->fx, $packet->fy, $packet->fz, $this) === true){
							break;
						}
					}elseif(!$this->inventory->getItemInHand()->equals($packet->item)){
						$this->inventory->sendHeldItem($this);
					}else{
						$item = $this->inventory->getItemInHand();
						$oldItem = clone $item;
						if($this->level->useItemOn($blockVector, $item, $packet->face, $packet->fx, $packet->fy, $packet->fz, $this)){
							if(!$item->equals($oldItem) or $item->getCount() !== $oldItem->getCount()){
								$this->inventory->setItemInHand($item);
								$this->inventory->sendHeldItem($this->hasSpawned);
							}
							break;
						}
					}

					$this->inventory->sendHeldItem($this);

					if($blockVector->distanceSquared($this) > 10000){
						break;
					}

					$target = $this->level->getBlock($blockVector);
					$block = $target->getSide($packet->face);

					/** @var Block[] $blocks */
					$blocks = array_merge($target->getAllSides(), $block->getAllSides()); //getAllSides() on each of these will include $target and $block because they are next to each other

					$this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

					break;
				}elseif($packet->face === -1){
					$aimPos = (new Vector3($packet->x / 32768, $packet->y / 32768, $packet->z / 32768))->normalize();

					if($this->isCreative()){
						$item = $this->inventory->getItemInHand();
					}elseif(!$this->inventory->getItemInHand()->equals($packet->item)){
						$this->inventory->sendHeldItem($this);
						break;
					}else{
						$item = $this->inventory->getItemInHand();
					}

					$ev = new PlayerInteractEvent($this, $item, $aimPos, $packet->face, PlayerInteractEvent::RIGHT_CLICK_AIR);

					$this->server->getPluginManager()->callEvent($ev);

					if($ev->isCancelled()){
						$this->inventory->sendHeldItem($this);
						break;
					}

					$item->useOnAir($this);
					$entity = null;
					$reduceCount = true;

					$nbt = Entity::createBaseNBT(
					    $this->add(0, $this->getEyeHeight(), 0),
                        new Vector3(
                            -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI),
                            -sin($this->pitch / 180 * M_PI),
                            cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)
                        ),
                        $this->yaw,
                        $this->pitch
                    );
					switch($item->getId()){
                        case Item::FISHING_ROD:
                            $this->server->getPluginManager()->callEvent($ev = new PlayerUseFishingRodEvent($this, ($this->isFishing() ? PlayerUseFishingRodEvent::ACTION_STOP_FISHING : PlayerUseFishingRodEvent::ACTION_START_FISHING)));
                            if(!$ev->isCancelled()){
                                if(!$this->isFishing()){
                                    $this->startFishing();
                                }else{
                                    $this->stopFishing();
                                }
                            }

                            $reduceCount = false;
                            break;
						case Item::SNOWBALL:
							$f = 1.5;
							$entity = Entity::createEntity("Snowball", $this->getLevel(), $nbt, $this);
							$entity->setMotion($entity->getMotion()->multiply($f));
							$this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
							if($ev->isCancelled()){
								$entity->kill();
							}
							break;
						case Item::EGG:
							$f = 1.5;
							$entity = Entity::createEntity("Egg", $this->getLevel(), $nbt, $this);
							$entity->setMotion($entity->getMotion()->multiply($f));
							$this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
							if($ev->isCancelled()){
								$entity->kill();
							}
							break;
						case Item::ENCHANTING_BOTTLE:
							$f = 1.1;
							$entity = Entity::createEntity("ThrownExpBottle", $this->getLevel(), $nbt, $this);
							$entity->setMotion($entity->getMotion()->multiply($f));
							$this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
							if($ev->isCancelled()){
								$entity->kill();
							}
							break;
						case Item::SPLASH_POTION:
							if($this->server->allowSplashPotion){
								$f = 1.1;
								$nbt["PotionId"] = new ShortTag("PotionId", $item->getDamage());
								$entity = Entity::createEntity("ThrownPotion", $this->getLevel(), $nbt, $this);
								$entity->setMotion($entity->getMotion()->multiply($f));
								$this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
								if($ev->isCancelled()){
									$entity->kill();
								}
							}
							break;
						case Item::ENDER_PEARL:
							if(floor(($time = microtime(true)) - $this->lastEnderPearlUse) >= 1){
								$f = 1.1;
								$entity = Entity::createEntity("EnderPearl", $this->getLevel(), $nbt, $this);
								$entity->setMotion($entity->getMotion()->multiply($f));
								$this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
								if($ev->isCancelled()){
									$entity->kill();
								}else{
									$this->lastEnderPearlUse = $time;
								}
							}
							break;
					}

					if($entity instanceof Projectile and $entity->isAlive()){
						if($reduceCount and $this->isSurvival()){
							$item->setCount($item->getCount() - 1);
							$this->inventory->setItemInHand($item->getCount() > 0 ? $item : Item::get(Item::AIR));
						}
						$entity->spawnToAll();
						$this->level->addSound(new LaunchSound($this), $this->getViewers());
					}

					$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, true);
					$this->startAction = $this->server->getTick();
				}
				break;
			case ProtocolInfo::PLAYER_ACTION_PACKET:
				if($this->spawned === false or (!$this->isAlive() and $packet->action !== PlayerActionPacket::ACTION_SPAWN_SAME_DIMENSION and $packet->action !== PlayerActionPacket::ACTION_SPAWN_OVERWORLD and $packet->action !== PlayerActionPacket::ACTION_SPAWN_NETHER)){
					break;
				}

				$pos = new Vector3($packet->x, $packet->y, $packet->z);

				switch($packet->action){
					case PlayerActionPacket::ACTION_START_BREAK:
						if($pos->distanceSquared($this) > 10000){
							break;
						}
						
						$target = $this->level->getBlock($pos);
						$ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, $packet->face, $target->getId() === 0 ? PlayerInteractEvent::LEFT_CLICK_AIR : PlayerInteractEvent::LEFT_CLICK_BLOCK);
						if($this->level->checkSpawnProtection($this, $target)){
					        $ev->setCancelled();
				        }
				        
						$this->getServer()->getPluginManager()->callEvent($ev);
						
						if(!$ev->isCancelled()){
							$side = $target->getSide($packet->face);
							if($side instanceof Fire){
								$side->getLevel()->setBlock($side, new Air());
								break;
							}
							
							if(!$this->isCreative()){
								$breakTime = ceil($target->getBreakTime($this->inventory->getItemInHand()) * 20);
								if($breakTime > 0){
									$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_START_BREAK, (int) (65535 / $breakTime));
								}
							}

						}else{
							$this->inventory->sendHeldItem($this);
						}
						break; 
						
					case PlayerActionPacket::ACTION_ABORT_BREAK:
						$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
						break;
						
					case PlayerActionPacket::ACTION_STOP_BREAK:
						$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_BLOCK_STOP_BREAK);
						break;
						
					case PlayerActionPacket::ACTION_RELEASE_ITEM:
						if($this->startAction > -1 and $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION)){
							if($this->inventory->getItemInHand()->getId() === Item::BOW){
								$bow = $this->inventory->getItemInHand();
								if($this->isSurvival() and !$this->inventory->contains(Item::get(Item::ARROW, -1))){
									$this->inventory->sendContents($this);
									break;
								}

								$arrow = null;

								$index = $this->inventory->first(Item::get(Item::ARROW, -1));

								if($index !== -1){
									$arrow = $this->inventory->getItem($index);
									$arrow->setCount(1);
								}elseif($this->isCreative()){
									$arrow = Item::get(Item::ARROW, 0, 1);
								}else{
									$this->inventory->sendContents($this);
									break;
								}

								$nbt = new CompoundTag("", [
									"Pos" => new ListTag("Pos", [
										new DoubleTag("", $this->x),
										new DoubleTag("", $this->y + $this->getEyeHeight()),
										new DoubleTag("", $this->z)
									]),
									"Motion" => new ListTag("Motion", [
										new DoubleTag("", -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)),
										new DoubleTag("", -sin($this->pitch / 180 * M_PI)),
										new DoubleTag("", cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
									]),
									"Rotation" => new ListTag("Rotation", [
										//yaw/pitch for arrows taken crosswise, not along the arrow shaft.
								        new FloatTag("", ($this->yaw > 180 ? 360 : 0) - $this->yaw), //arrow yaw must range from -180 to +180
								        new FloatTag("", -$this->pitch)
									]),
									"Fire" => new ShortTag("Fire", $this->isOnFire() ? 45 * 60 : 0),
									"Potion" => new ShortTag("Potion", $arrow->getDamage())
								]);

								$diff = ($this->server->getTick() - $this->startAction);
								$p = $diff / 20;
								$f = min((($p ** 2) + $p * 2) / 3, 1) * 2;
								$ev = new EntityShootBowEvent($this, $bow, Entity::createEntity("Arrow", $this->getLevel(), $nbt, $this, $f == 2 ? true : false), $f);

								if($f < 0.1 or $diff < 5){
									$ev->setCancelled();
								}

								$this->server->getPluginManager()->callEvent($ev);

								if($ev->isCancelled()){
									$ev->getProjectile()->kill();
									$this->inventory->sendContents($this);
								}else{
									$ev->getProjectile()->setMotion($ev->getProjectile()->getMotion()->multiply($ev->getForce()));
									if($this->isSurvival()){
										$this->inventory->removeItem($arrow);
										$bow->setDamage($bow->getDamage() + 1);
										if($bow->getDamage() >= 385){
											$this->inventory->setItemInHand(Item::get(Item::AIR, 0, 0));
										}else{
											$this->inventory->setItemInHand($bow);
										}
									}
									$projectile = $ev->getProjectile();
							        if($projectile instanceof Projectile){
								        $this->server->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($projectile));
										if($projectileEv->isCancelled()){
											$ev->getProjectile()->kill();
										}else{
											$ev->getProjectile()->spawnToAll();
											$this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BOW);
										}
									}else{
										$ev->getProjectile()->spawnToAll();
									}
								}
							}
						}elseif($this->inventory->getItemInHand()->getId() === Item::BUCKET and $this->inventory->getItemInHand()->getDamage() === 1){ //Milk!
							$this->server->getPluginManager()->callEvent($ev = new PlayerItemConsumeEvent($this, $this->inventory->getItemInHand()));
							if($ev->isCancelled()){
								$this->inventory->sendContents($this);
								break;
							}

							$pk = new EntityEventPacket();
							$pk->eid = $this->getId();
							$pk->event = EntityEventPacket::USE_ITEM;
							//$pk;
							$this->dataPacket($pk);
							$this->server->broadcastPacket($this->getViewers(), $pk);

							if($this->isSurvival()){
								$slot = $this->inventory->getItemInHand();
								--$slot->count;
								$this->inventory->setItemInHand($slot);
								$this->inventory->addItem(Item::get(Item::BUCKET, 0, 1));
							}

							$this->removeAllEffects();
						}else{
							$this->inventory->sendContents($this);
						}
						break;
					case PlayerActionPacket::ACTION_STOP_SLEEPING:
						$this->stopSleep();
						break;
					case PlayerActionPacket::ACTION_SPAWN_NETHER:
						break;
					case PlayerActionPacket::ACTION_SPAWN_SAME_DIMENSION:
					case PlayerActionPacket::ACTION_SPAWN_OVERWORLD:
						if($this->isAlive() or !$this->isOnline()){
							break;
						}

						$this->respawn();
						break;
					case PlayerActionPacket::ACTION_JUMP:
					    $this->jump();
						break 2;
					case PlayerActionPacket::ACTION_START_SPRINT:
						$ev = new PlayerToggleSprintEvent($this, true);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSprinting(true);
						}
						return true;
					case PlayerActionPacket::ACTION_STOP_SPRINT:
						$ev = new PlayerToggleSprintEvent($this, false);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSprinting(false);
						}
						return true;
					case PlayerActionPacket::ACTION_START_SNEAK:
						$ev = new PlayerToggleSneakEvent($this, true);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSneaking(true);
						}
						break 2;
					case PlayerActionPacket::ACTION_STOP_SNEAK:
						$ev = new PlayerToggleSneakEvent($this, false);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setSneaking(false);
						}
						break 2;
					case PlayerActionPacket::ACTION_START_GLIDE:
						$ev = new PlayerToggleGlideEvent($this, true);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setGliding(true);
						}
						break 2;
					case PlayerActionPacket::ACTION_STOP_GLIDE:
						$ev = new PlayerToggleGlideEvent($this, false);
						$this->server->getPluginManager()->callEvent($ev);
						if($ev->isCancelled()){
							$this->sendData($this);
						}else{
							$this->setGliding(false);
						}
						break 2;
					case PlayerActionPacket::ACTION_CONTINUE_BREAK:
						$block = $this->level->getBlock($pos);
						$this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK, $block->getId() | ($block->getDamage() << 8) | ($packet->face << 16));
						break;
					default:
						assert(false, "Unhandled player action " . $packet->action . " from " . $this->getName());
				}

				$this->startAction = -1;
				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
				break;

			case ProtocolInfo::REMOVE_BLOCK_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}
				$this->craftingType = self::CRAFTING_SMALL;

				$vector = new Vector3($packet->x, $packet->y, $packet->z);

				$item = $this->inventory->getItemInHand();
				$oldItem = clone $item;

				if($this->canInteract($vector->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 7) and $this->level->useBreakOn($vector, $item, $this, $this->server->destroyBlockParticle)){
					if($this->isSurvival()){
						if(!$item->equals($oldItem) or $item->getCount() !== $oldItem->getCount()){
							$this->inventory->setItemInHand($item);
							$this->inventory->sendHeldItem($this);
						}

						$this->exhaust(0.025, PlayerExhaustEvent::CAUSE_MINING);
					}
					break;
				}

				$this->inventory->sendContents($this);
				$this->inventory->sendHeldItem($this);

				$target = $this->level->getBlock($vector);
				/** @var Block[] $blocks */
				$blocks = $target->getAllSides();
				$blocks[] = $target;

				$this->level->sendBlocks([$this], $blocks, UpdateBlockPacket::FLAG_ALL_PRIORITY);

				foreach($blocks as $b){
				    $tile = $this->level->getTile($b);
					if($tile instanceof Spawnable){
						$tile->spawnTo($this);
					}
				}
				
				break;
			case ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET:
				//This packet is ignored. Armour changes are also sent by ContainerSetSlotPackets, and are handled there instead.
				break;

			case ProtocolInfo::INTERACT_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}

				$this->craftingType = self::CRAFTING_SMALL;

				$target = $this->level->getEntity($packet->target);

				$cancelled = false;

				if($target instanceof Player and $this->server->getConfigBoolean("pvp", true) === false){
					$cancelled = true;
				}

				if($target instanceof Boat or ($target instanceof Minecart and $target->getType() == Minecart::TYPE_NORMAL)){
					if($packet->action === InteractPacket::ACTION_RIGHT_CLICK){
						$this->linkEntity($target);
					}elseif($packet->action === InteractPacket::ACTION_LEFT_CLICK){
						if($this->linkedEntity === $target){
							$target->setLinked(0, $this);
						}
						$target->close();
					}elseif($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
						$this->setLinked(0, $target);
					}
					return;
				}

				if($packet->action === InteractPacket::ACTION_RIGHT_CLICK){
					if($target instanceof Animal and $this->getInventory()->getItemInHand()){
						//TODO: Feed
					}
					break;
				}elseif($packet->action === InteractPacket::ACTION_MOUSEOVER){
					break;
				}

				if($target instanceof Entity and $this->getGamemode() !== Player::VIEW and $this->isAlive() and $target->isAlive()){
					if($target instanceof DroppedItem or $target instanceof Arrow){
						$this->kick("Attempting to attack an invalid entity");
						$this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString("pocketmine.player.invalidEntity", [$this->getName()]));
						break;
					}

					$item = $this->inventory->getItemInHand();
					$damage = [
						EntityDamageEvent::MODIFIER_BASE => $item->getModifyAttackDamage($target),
					];

					if(!$this->canInteract($target, 8) or $this->isSpectator()){
						$cancelled = true;
					}elseif($target instanceof Player){
						if(($target->getGamemode() & 0x01) > 0){
							break;
						}elseif($this->server->getConfigBoolean("pvp") !== true or $this->server->getDifficulty() === 0){
							$cancelled = true;
						}
					}

					$ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage, 0.4 + $item->getEnchantmentLevel(Enchantment::TYPE_WEAPON_KNOCKBACK) * 0.15);
					if($cancelled){
						$ev->setCancelled();
					}

					if($target->attack($ev->getFinalDamage(), $ev) === true){
						$fireAspectL = $item->getEnchantmentLevel(Enchantment::TYPE_WEAPON_FIRE_ASPECT);
						if($fireAspectL > 0){
							$fireEv = new EntityCombustByEntityEvent($this, $target, $fireAspectL * 4, $ev->getFireProtectL());
							Server::getInstance()->getPluginManager()->callEvent($fireEv);
							if(!$fireEv->isCancelled()){
								$target->setOnFire($fireEv->getDuration());
							}
						}
						//Thorns
						if($this->isSurvival()){
							$ev->createThornsDamage();
							if($ev->getThornsDamage() > 0){
								$thornsEvent = new EntityDamageByEntityEvent($target, $this, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $ev->getThornsDamage(), 0);
								if(!$thornsEvent->isCancelled()){
									if($this->attack($thornsEvent->getFinalDamage(), $thornsEvent) === true){
										$thornsEvent->useArmors();
										$ev->setThornsArmorUse();
									}
								}
							}
						}
						$ev->useArmors();
					}

					if(!$this->isSprinting() and !$this->isFlying() and $this->fallDistance > 0 and !$this->hasEffect(Effect::BLINDNESS) and !$this->isInsideOfWater()){
						$ev->setDamage($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
					}

					if($ev->isCancelled()){
						if($item->isTool() and $this->isSurvival()){
							$this->inventory->sendContents($this);
						}
						break;
					}

					if($ev->getDamage(EntityDamageEvent::MODIFIER_CRITICAL) > 0){
						$pk = new AnimatePacket();
						$pk->action = AnimatePacket::ACTION_CRITICAL_HIT;
						$pk->eid = $target->getId();
						$this->server->broadcastPacket($target->getViewers(), $pk);
						if($target instanceof Player){
							$target->dataPacket($pk);
						}
					}

					if($this->isSurvival()){
						if($item->isTool()){
							if($item->useOn($target) and $item->getDamage() >= $item->getMaxDurability()){
								$this->inventory->setItemInHand(Item::get(Item::AIR, 0, 1));
							}else{
								$this->inventory->setItemInHand($item);
							}
						}

						$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_ATTACK);
					}
				}


				break;
			case ProtocolInfo::ANIMATE_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}

				$this->server->getPluginManager()->callEvent($ev = new PlayerAnimationEvent($this, $packet->action));
				if($ev->isCancelled()){
					break;
				}

				$pk = new AnimatePacket();
				$pk->eid = $this->getId();
				$pk->action = $ev->getAnimationType();
				$this->server->broadcastPacket($this->getViewers(), $pk);
				break;
			case ProtocolInfo::SET_HEALTH_PACKET: //Not used
				break;
			case ProtocolInfo::ENTITY_EVENT_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}
				$this->craftingType = self::CRAFTING_SMALL;

				$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false); //TODO: check if this should be true

				switch($packet->event){
					case EntityEventPacket::EATING_ITEM:
				        if($packet->data === 0){
					        return false;
				        }

				        $this->dataPacket($packet);
				        $this->server->broadcastPacket($this->getViewers(), $packet);

				        break;
					case EntityEventPacket::USE_ITEM: //Eating
						$slot = $this->inventory->getItemInHand();

						if($slot->canBeConsumed()){
							$ev = new PlayerItemConsumeEvent($this, $slot);
							if(!$slot->canBeConsumedBy($this)){
								$ev->setCancelled();
							}
							$this->server->getPluginManager()->callEvent($ev);
							if(!$ev->isCancelled()){
								$slot->onConsume($this);
							}else{
								$this->inventory->sendContents($this);
							}
						}
						break;
				}
				break;
			case ProtocolInfo::DROP_ITEM_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}
				if($packet->item->getId() === Item::AIR){
					/**
					 * This is so stupid it's unreal.
					 * Windows 10 Edition Beta drops the contents of the crafting grid when the inventory closes - including air.
					 */
					break;
				}

				if(($this->isCreative() and $this->server->limitedCreative)){
					break;
				}

				$this->getTransactionQueue()->addTransaction(new DropItemTransaction($packet->item));
				break;
			case ProtocolInfo::COMMAND_STEP_PACKET:
				/** @var CommandStepPacket $packet */
				if($this->spawned === false or !$this->isAlive()){
					break;
				}
				$this->craftingType = 0;
				$commandText = $packet->command;
				if($packet->inputJson !== null){
					if (is_countable($packet->inputJson)) {
					    if(count($packet->inputJson) > 15){
						    $this->server->getNetwork()->blockAddress($this->getAddress(), 1200);
						    $this->close("", "", false);
						    return;
						}
					}

					foreach($packet->inputJson as $arg){ //command ordering will be an issue
						if(!is_object($arg)) //anti bot
							$commandText .= " " . $arg;
					}
				}
				$this->server->getPluginManager()->callEvent($ev = new PlayerCommandPreprocessEvent($this, "/" . $commandText));
				if($ev->isCancelled()){
					break;
				}

				Timings::$playerCommandTimer->startTiming();
				$this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
				Timings::$playerCommandTimer->stopTiming();
				break;
			case ProtocolInfo::TEXT_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}
				$this->craftingType = self::CRAFTING_SMALL;
				if($packet->type === TextPacket::TYPE_CHAT){
					$packet->message = TextFormat::clean($packet->message, $this->removeFormat);
					foreach(explode("\n", $packet->message) as $message){
						if(trim($message) != "" and strlen($message) <= 255 and $this->messageCounter-- > 0){
							if(substr($message, 0, 2) === "./"){ //Command (./ = fast hack for old plugins post 0.16)
								$message = substr($message, 1);
							}

							$ev = new PlayerCommandPreprocessEvent($this, $message);

							if(mb_strlen($ev->getMessage(), "UTF-8") > 320){
								$ev->setCancelled();
							}
							$this->server->getPluginManager()->callEvent($ev);

							if($ev->isCancelled()){
								break;
							}

							if(substr($ev->getMessage(), 0, 1) === "/"){
								Timings::$playerCommandTimer->startTiming();
								$this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
								Timings::$playerCommandTimer->stopTiming();
							}else{
								$this->server->getPluginManager()->callEvent($ev = new PlayerChatEvent($this, $ev->getMessage()));
								if(!$ev->isCancelled()){
									$this->server->broadcastMessage($this->getServer()->getLanguage()->translateString($ev->getFormat(), [
										$ev->getPlayer()->getDisplayName(),
										$ev->getMessage()
									]), $ev->getRecipients());
								}
							}
						}
					}
				}
				break;
			case ProtocolInfo::CONTAINER_CLOSE_PACKET:
				if($this->spawned === false or $packet->windowid === 0){
					break;
				}
				$this->craftingType = self::CRAFTING_SMALL;
				if(isset($this->windowIndex[$packet->windowid])){
					$this->server->getPluginManager()->callEvent(new InventoryCloseEvent($this->windowIndex[$packet->windowid], $this));
					$this->removeWindow($this->windowIndex[$packet->windowid]);
				}

				foreach($this->getFloatingInventory()->getContents() as $item){
					$this->getFloatingInventory()->removeItem($item);
					$this->getInventory()->addItem($item);
				}
				break;

			case ProtocolInfo::CRAFTING_EVENT_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}
				$recipe = $this->server->getCraftingManager()->getRecipe($packet->id);

				if($this->craftingType === self::CRAFTING_ANVIL){
					$anvilInventory = $this->windowIndex[$packet->windowId] ?? null;
					if($anvilInventory === null){
						foreach($this->windowIndex as $window){
							if($window instanceof AnvilInventory){
								$anvilInventory = $window;
								break;
							}
						}
						if($anvilInventory === null){ //If it's _still_ null, then the player doesn't have a valid anvil window, cannot proceed.
							$this->getServer()->getLogger()->debug("Couldn't find an anvil window for " . $this->getName() . ", exiting");
							$this->inventory->sendContents($this);
							break;
						}
					}

					if($recipe === null){
						if($packet->output[0]->getId() > 0 && $packet->output[1] === 0){ //物品重命名
							$anvilInventory->onRename($this, $packet->output[0]);
						}elseif($packet->output[0]->getId() > 0 && $packet->output[1] > 0){ //附魔书
							$anvilInventory->process($this, $packet->output[0], $packet->output[1]);
						}
					}
					break;
				}elseif(($recipe instanceof BigShapelessRecipe or $recipe instanceof BigShapedRecipe) and $this->craftingType === 0){
					$this->server->getLogger()->debug("Received big crafting recipe from " . $this->getName() . " with no crafting table open");
					$this->inventory->sendContents($this);
					break;
				}elseif($recipe === null){
					$this->server->getLogger()->debug("Null (unknown) crafting recipe received from " . $this->getName() . " for " . $packet->output[0]);
					$this->inventory->sendContents($this);
					break;
				}

				$canCraft = true;

				if(count($packet->input) === 0){
					$possibleRecipes = $this->server->getCraftingManager()->getRecipesByResult($packet->output[0]);
					if(!$packet->output[0]->equals($recipe->getResult())){
						$this->server->getLogger()->debug("Mismatched desktop recipe received from player " . $this->getName() . ", expected " . $recipe->getResult() . ", got " . $packet->output[0]);
					}
					$recipe = null;
					foreach($possibleRecipes as $r){
						/* Check the ingredient list and see if it matches the ingredients we've put into the crafting grid
						 * As soon as we find a recipe that we have all the ingredients for, take it and run with it. */

						//Make a copy of the floating inventory that we can make changes to.
						$floatingInventory = clone $this->floatingInventory;
						$ingredients = $r->getIngredientList();

						//Check we have all the necessary ingredients.
						foreach($ingredients as $ingredient){
							if(!$floatingInventory->contains($ingredient)){
								//We're short on ingredients, try the next recipe
								$canCraft = false;
								break;
							}
							//This will only be reached if we have the item to take away.
							$floatingInventory->removeItem($ingredient);
						}
						if($canCraft){
							//Found a recipe that works, take it and run with it.
							$recipe = $r;
							break;
						}
					}

					if($recipe !== null){
						$this->server->getPluginManager()->callEvent($ev = new CraftItemEvent($this, $ingredients, $recipe));

						if($ev->isCancelled()){
							$this->inventory->sendContents($this);
							break;
						}

						$this->floatingInventory = $floatingInventory; //Set player crafting inv to the idea one created in this process
						$this->floatingInventory->addItem(clone $recipe->getResult()); //Add the result to our picture of the crafting inventory
					}else{
						$this->server->getLogger()->debug("Unmatched desktop crafting recipe " . $packet->id . " from player " . $this->getName());
						$this->inventory->sendContents($this);
						break;
					}
				}else{
					if($recipe instanceof ShapedRecipe){
						for($x = 0; $x < 3 and $canCraft; ++$x){
							for($y = 0; $y < 3; ++$y){
								$item = $packet->input[$y * 3 + $x];
								$ingredient = $recipe->getIngredient($x, $y);
								if($item->getCount() > 0 and $item->getId() > 0){
									if($ingredient == null){
										$canCraft = false;
										break;
									}
									if($ingredient->getId() != 0 and !$ingredient->equals($item, !$ingredient->hasAnyDamageValue(), $ingredient->hasCompoundTag())){
										$canCraft = false;
										break;
									}

								}elseif($ingredient !== null and $item->getId() !== 0){
									$canCraft = false;
									break;
								}
							}
						}
					}elseif($recipe instanceof ShapelessRecipe){
						$needed = $recipe->getIngredientList();

						for($x = 0; $x < 3 and $canCraft; ++$x){
							for($y = 0; $y < 3; ++$y){
								$item = clone $packet->input[$y * 3 + $x];

								foreach($needed as $k => $n){
									if($n->equals($item, !$n->hasAnyDamageValue(), $n->hasCompoundTag())){
										$remove = min($n->getCount(), $item->getCount());
										$n->setCount($n->getCount() - $remove);
										$item->setCount($item->getCount() - $remove);

										if($n->getCount() === 0){
											unset($needed[$k]);
										}
									}
								}

								if($item->getCount() > 0){
									$canCraft = false;
									break;
								}
							}
						}
						if(count($needed) > 0){
							$canCraft = false;
						}
					}else{
						$canCraft = false;
					}

					/** @var Item[] $ingredients */
					$ingredients = $packet->input;
					$result = $packet->output[0];

					if(!$canCraft or !$recipe->getResult()->equals($result)){
						$this->server->getLogger()->debug("Unmatched recipe " . $recipe->getId() . " from player " . $this->getName() . ": expected " . $recipe->getResult() . ", got " . $result . ", using: " . implode(", ", $ingredients));
						$this->inventory->sendContents($this);
						break;
					}

					$used = array_fill(0, $this->inventory->getSize(), 0);

					foreach($ingredients as $ingredient){
						$slot = -1;
						foreach($this->inventory->getContents() as $index => $item){
							if($ingredient->getId() !== 0 and $ingredient->equals($item, !$ingredient->hasAnyDamageValue(), $ingredient->hasCompoundTag()) and ($item->getCount() - $used[$index]) >= 1){
								$slot = $index;
								$used[$index]++;
								break;
							}
						}

						if($ingredient->getId() !== 0 and $slot === -1){
							$canCraft = false;
							break;
						}
					}

					if(!$canCraft){
						$this->server->getLogger()->debug("Unmatched recipe " . $recipe->getId() . " from player " . $this->getName() . ": client does not have enough items, using: " . implode(", ", $ingredients));
						$this->inventory->sendContents($this);
						break;
					}

					$this->server->getPluginManager()->callEvent($ev = new CraftItemEvent($this, $ingredients, $recipe));

					if($ev->isCancelled()){
						$this->inventory->sendContents($this);
						break;
					}

					foreach($used as $slot => $count){
						if($count === 0){
							continue;
						}

						$item = $this->inventory->getItem($slot);

						if($item->getCount() > $count){
							$newItem = clone $item;
							$newItem->setCount($item->getCount() - $count);
						}else{
							$newItem = Item::get(Item::AIR, 0, 0);
						}

						$this->inventory->setItem($slot, $newItem);
					}

					$extraItem = $this->inventory->addItem($recipe->getResult());
					if(count($extraItem) > 0 and !$this->isCreative()){ //Could not add all the items to our inventory (not enough space)
						foreach($extraItem as $item){
							$this->level->dropItem($this, $item);
						}
					}
				}

				switch($recipe->getResult()->getId()){
					case Item::WORKBENCH:
						$this->awardAchievement("buildWorkBench");
						break;
					case Item::WOODEN_PICKAXE:
						$this->awardAchievement("buildPickaxe");
						break;
					case Item::FURNACE:
						$this->awardAchievement("buildFurnace");
						break;
					case Item::WOODEN_HOE:
						$this->awardAchievement("buildHoe");
						break;
					case Item::BREAD:
						$this->awardAchievement("makeBread");
						break;
					case Item::CAKE:
						//TODO: detect complex recipes like cake that leave remains
						$this->awardAchievement("bakeCake");
						$this->inventory->addItem(Item::get(Item::BUCKET, 0, 3));
						break;
					case Item::STONE_PICKAXE:
					case Item::GOLD_PICKAXE:
					case Item::IRON_PICKAXE:
					case Item::DIAMOND_PICKAXE:
						$this->awardAchievement("buildBetterPickaxe");
						break;
					case Item::WOODEN_SWORD:
						$this->awardAchievement("buildSword");
						break;
					case Item::DIAMOND:
						$this->awardAchievement("diamond");
						break;
				}

				break;

			case ProtocolInfo::CONTAINER_SET_SLOT_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}

				/** @var ContainerSetSlotPacket $packet */
				if($packet->slot < 0){
					break;
				}

				if($packet->windowid === 0){ //Our inventory
					if($packet->slot >= $this->inventory->getSize()){
						break;
					}

					$transaction = new BaseTransaction($this->inventory, $packet->slot, $packet->item);
				}elseif($packet->windowid === ContainerSetContentPacket::SPECIAL_ARMOR){ //Our armor
					if($packet->slot >= 4){
						break;
					}

					$transaction = new BaseTransaction($this->inventory, $packet->slot + $this->inventory->getSize(), $packet->item);
				}elseif(isset($this->windowIndex[$packet->windowid])){
					//Transaction for non-player-inventory window, such as anvil, chest, etc.

					$inv = $this->windowIndex[$packet->windowid];
					$achievements = [];

					if($inv instanceof FurnaceInventory and $inv->getItem($packet->slot)->getId() === Item::IRON_INGOT and $packet->slot === FurnaceInventory::RESULT){
						$achievements[] = "acquireIron";

					}elseif($inv instanceof EnchantInventory and $packet->item->hasEnchantments()){
						$inv->onEnchant($this, $inv->getItem($packet->slot), $packet->item);
					}

					$transaction = new BaseTransaction($inv, $packet->slot, $packet->item, $achievements);
				}else{
					//Client sent a transaction for a window which the server doesn't think they have open
					break;
				}

				$this->getTransactionQueue()->addTransaction($transaction);

				break;
			case ProtocolInfo::BLOCK_ENTITY_DATA_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}
				$this->craftingType = self::CRAFTING_SMALL;

				$pos = new Vector3($packet->x, $packet->y, $packet->z);
				if($pos->distanceSquared($this) > 10000 or $this->level->checkSpawnProtection($this, $pos)){
					break;
				}

				$t = $this->level->getTile($pos);
				if($t instanceof Spawnable){
					$nbt = new NBT(NBT::LITTLE_ENDIAN);
					$nbt->read($packet->namedtag, false, true);
					$nbt = $nbt->getData();
					if(!$t->updateCompoundTag($nbt, $this)){
						$t->spawnTo($this);
					}
				}
				break;
			case ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET:
				$this->setViewDistance($packet->radius);
				break;
			case ProtocolInfo::SET_PLAYER_GAME_TYPE_PACKET:
				if($packet->gamemode !== $this->gamemode){
					//Set this back to default. TODO: handle this properly
					$this->sendGamemode();
					$this->sendSettings();
				}
				break;
			case ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET:
				if($this->spawned === false or !$this->isAlive()){
					break;
				}

		        $tile = $this->level->getTileAt($packet->x, $packet->y, $packet->z);
				if($tile instanceof ItemFrame){
					$this->server->getPluginManager()->callEvent($ev = new ItemFrameDropItemEvent($this, $tile->getBlock(), $tile, $tile->getItem()));
					if($this->isSpectator() or $ev->isCancelled()){
						$tile->spawnTo($this);
						break;
					}

					if(lcg_value() <= $tile->getItemDropChance()){
						$this->level->dropItem($tile->getBlock(), $tile->getItem());
					}
					$tile->setItem(null);
					$tile->setItemRotation(0);
				}

				break;
			default:
				break;
		}

		$timings->stopTiming();
	}

	/**
	 * Kicks a player from the server
	 *
	 * @param string $reason
	 * @param bool   $isAdmin
	 *
	 * @return bool
	 */
	public function kick(string $reason = "", bool $isAdmin = true, $quitMessage = null) : bool{
		$this->server->getPluginManager()->callEvent($ev = new PlayerKickEvent($this, $reason, $quitMessage ?? $this->getLeaveMessage()));
		if(!$ev->isCancelled()){
			$reason = $ev->getReason();
			$message = $reason;
			if($isAdmin){
				if(!$this->isBanned()){
					$message = "Kicked by admin." . ($reason !== "" ? " Reason: " . $reason : "");
				}
			}else{
				if($reason === ""){
					$message = "disconnectionScreen.noReason";
				}
			}
			$this->close($ev->getQuitMessage(), $message);

			return true;
		}

		return false;
	}

	/** @var string[] */
	private $messageQueue = [];

	/**
	 * @param Item $item
	 *
	 * Drops the specified item in front of the player.
	 */
	public function dropItem(Item $item){
		if($this->spawned === false or !$this->isAlive()){
			return;
		}

		if(($this->isCreative() and $this->server->limitedCreative) or $this->isSpectator()){
			//Ignore for limited creative
			return;
		}

		if($item->getId() === Item::AIR or $item->getCount() < 1){
			//Ignore dropping air or items with bad counts
			return;
		}

		$ev = new PlayerDropItemEvent($this, $item);
		$this->server->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()){
			$this->getFloatingInventory()->removeItem($item);
			$this->getInventory()->addItem($item);
			return;
		}

		$motion = $this->getDirectionVector()->multiply(0.4);

		$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
	}

	/**
	 * Adds a title text to the user's screen, with an optional subtitle.
	 *
	 * @param string $title
	 * @param string $subtitle
	 * @param int    $fadeIn  Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
	 * @param int    $stay    Duration in ticks to stay on screen for
	 * @param int    $fadeOut Duration in ticks for fade-out.
	 */
	public function sendActionBar(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1){
		$this->setTitleDuration($fadeIn, $stay, $fadeOut);
		if($subtitle !== ""){
			$this->sendTitleText($subtitle, SetTitlePacket::TYPE_SUB_TITLE);
		}
		$this->sendTitleText($title, SetTitlePacket::TYPE_TITLE);
	}

	/*********/
	/**
	 * @param string $title
	 * @param string $subtitle
	 * @param int    $fadeIn
	 * @param int    $stay
	 * @param int    $fadeOut
	 */
	public function addTitle(string $title, string $subtitle = "", int $fadeIn = -1, int $stay = -1, int $fadeOut = -1){
		$this->setTitleDuration($fadeIn, $stay, $fadeOut);
		if($subtitle !== ""){
			$this->sendTitleText($subtitle, SetTitlePacket::TYPE_SUB_TITLE);
		}
		$this->sendTitleText($title, SetTitlePacket::TYPE_TITLE);
	}

	/**
	 * Adds small text to the user's screen.
	 *
	 * @param string $message
	 */
	public function addActionBarMessage(string $message){
		$this->sendTitleText($message, SetTitlePacket::TYPE_ACTION_BAR);
	}

	/**
	 * Removes the title from the client's screen.
	 */
	public function removeTitles(){
		$pk = new SetTitlePacket();
		$pk->type = SetTitlePacket::TYPE_CLEAR;
		$this->dataPacket($pk);
	}

	/**
	 * Sets the title duration.
	 *
	 * @param int $fadeIn  Title fade-in time in ticks.
	 * @param int $stay    Title stay time in ticks.
	 * @param int $fadeOut Title fade-out time in ticks.
	 */
	public function setTitleDuration(int $fadeIn, int $stay, int $fadeOut){
		if($fadeIn >= 0 and $stay >= 0 and $fadeOut >= 0){
			$pk = new SetTitlePacket();
			$pk->type = SetTitlePacket::TYPE_TIMES;
			$pk->fadeInDuration = $fadeIn;
			$pk->duration = $stay;
			$pk->fadeOutDuration = $fadeOut;
			$this->dataPacket($pk);
		}
	}

	/**
	 * Internal function used for sending titles.
	 *
	 * @param string $title
	 * @param int    $type
	 */
	protected function sendTitleText(string $title, int $type){
		$pk = new SetTitlePacket();
		$pk->type = $type;
		$pk->title = $title;
		$this->dataPacket($pk);
	}

	protected function respawn() : void{
		if($this->server->isHardcore()){
			$this->setBanned(true);
			return;
		}

		$this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $this->getSpawn()));

		$realSpawn = Position::fromObject($ev->getRespawnPosition()->add(0.5, 0, 0.5), $ev->getRespawnPosition()->getLevel());
		$this->teleport($realSpawn);

		$this->setSprinting(false);
		$this->setSneaking(false);

		$this->extinguish();
		$this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, 400);
		$this->deadTicks = 0;
		$this->noDamageTicks = 60;

		$this->setHealth($this->getMaxHealth());
		$this->setFood(20);
		$this->starvationTick = 0;
		$this->foodTick = 0;
		$this->foodUsageTime = 0;

		foreach($this->attributeMap->getAll() as $attr){
			$attr->resetToDefault();
		}

		$this->sendData($this);
		$this->sendData($this->getViewers());

		$this->sendSettings();
		$this->inventory->sendContents($this);
		$this->inventory->sendArmorContents($this);
		$this->offhandInventory->sendContents($this);

		$this->spawnToAll();
		$this->scheduleUpdate();
	}

	/**
	 * Transfers a player to another server.
	 *
	 * @param string $address The IP address or hostname of the destination server
	 * @param int    $port    The destination port, defaults to 19132
	 * @param string $message Message to show in the console when closing the player
	 *
	 * @return bool if transfer was successful.
	 */
	public function transfer(string $address, int $port = 19132, string $message = "transfer") : bool{
		$this->server->getPluginManager()->callEvent($ev = new PlayerTransferEvent($this, $address, $port, $message));
		if(!$ev->isCancelled()){
			$pk = new TransferPacket();
			$pk->address = $ev->getAddress();
			$pk->port = $ev->getPort();
			$this->directDataPacket($pk);
			$this->close("", $ev->getMessage(), false);

			return true;
		}

		return false;
	}

	/**
	 * Sends a direct chat message to a player
	 *
	 * @param string|TextContainer $message
	 *
	 * @return bool
	 */
	public function sendMessage($message){
		if($message instanceof TextContainer){
			if($message instanceof TranslationContainer){
				$this->sendTranslation($message->getText(), $message->getParameters());
				return false;
			}

			$message = $message->getText();
		}

		//TODO: Remove this workaround (broken client MCPE 1.0.0)
		$this->messageQueue[] = $this->server->getLanguage()->translateString($message);
		/*
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $this->server->getLanguage()->translateString($message);
		$this->dataPacket($pk);
		*/
	}

	/**
	 * @param       $message
	 * @param array $parameters
	 *
	 * @return bool
	 */
	public function sendTranslation($message, array $parameters = []){
		$pk = new TextPacket();
		if(!$this->server->isLanguageForced()){
			$pk->type = TextPacket::TYPE_TRANSLATION;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters, "pocketmine.");
			foreach($parameters as $i => $p){
				$parameters[$i] = $this->server->getLanguage()->translateString($p, $parameters, "pocketmine.");
			}
			$pk->parameters = $parameters;
		}else{
			$pk->type = TextPacket::TYPE_RAW;
			$pk->message = $this->server->getLanguage()->translateString($message, $parameters);
		}

		$ev = new PlayerTextPreSendEvent($this, $pk->message, PlayerTextPreSendEvent::TRANSLATED_MESSAGE);
		$this->server->getPluginManager()->callEvent($ev);
		if(!$ev->isCancelled()){
			$this->dataPacket($pk);
			return true;
		}
		return false;
	}

	/**
	 * @param        $message
	 * @param string $subtitle
	 *
	 * @return bool
	 */
	public function sendPopup($message, $subtitle = ""){
		$ev = new PlayerTextPreSendEvent($this, $message, PlayerTextPreSendEvent::POPUP);
		$this->server->getPluginManager()->callEvent($ev);
		if(!$ev->isCancelled()){
			$pk = new TextPacket();
			$pk->type = TextPacket::TYPE_POPUP;
			$pk->source = $ev->getMessage();
			$pk->message = $subtitle;
			$this->dataPacket($pk);
			return true;
		}
		return false;
	}

	/**
	 * @param $message
	 *
	 * @return bool
	 */
	public function sendTip($message){
		$ev = new PlayerTextPreSendEvent($this, $message, PlayerTextPreSendEvent::TIP);
		$this->server->getPluginManager()->callEvent($ev);
		if(!$ev->isCancelled()){
			$pk = new TextPacket();
			$pk->type = TextPacket::TYPE_TIP;
			$pk->message = $ev->getMessage();
			$this->dataPacket($pk);
			return true;
		}
		return false;
	}

	/**
	 * Send a title text or/and with/without a sub title text to a player
	 *
	 * @param        $title
	 * @param string $subtitle
	 * @param int    $fadein
	 * @param int    $fadeout
	 * @param int    $duration
	 *
	 * @return bool
	 */
	public function sendTitle($title, $subtitle = "", $fadein = 20, $fadeout = 20, $duration = 5){
		return $this->addTitle($title, $subtitle, $fadein, $duration, $fadeout);
	}

	/**
	 * Note for plugin developers: use kick() with the isAdmin
	 * flag set to kick without the "Kicked by admin" part instead of this method.
	 *
	 * @param string $message Message to be broadcasted
	 * @param string $reason  Reason showed in console
	 * @param bool   $notify
	 */
	public final function close($message = "", string $reason = "generic reason", bool $notify = true){
		if($this->connected and !$this->closed){
			if($notify and strlen($reason) > 0){
				$pk = new DisconnectPacket();
				$pk->message = $reason;
				$this->directDataPacket($pk);
			}

			//$this->setLinked();

			if($this->fishingHook instanceof FishingHook){
				$this->fishingHook->close();
				$this->fishingHook = null;
			}

			$this->removeEffect(Effect::HEALTH_BOOST);

			$this->interface->close($this, $notify ? $reason : "");
			$this->connected = false;

			$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
			$this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

			$this->stopSleep();

			if($this->spawned){
				$this->server->getPluginManager()->callEvent($ev = new PlayerQuitEvent($this, $message, true));
				if(isset($ev) and $this->username != "" and $ev->getQuitMessage() != ""){
					if($this->server->playerMsgType === Server::PLAYER_MSG_TYPE_MESSAGE) $this->server->broadcastMessage($ev->getQuitMessage());
				    elseif($this->server->playerMsgType === Server::PLAYER_MSG_TYPE_TIP) $this->server->broadcastTip(str_replace("@player", $this->getName(), $this->server->playerLogoutMsg));
				    elseif($this->server->playerMsgType === Server::PLAYER_MSG_TYPE_POPUP) $this->server->broadcastPopup(str_replace("@player", $this->getName(), $this->server->playerLogoutMsg));
				}

				if($ev->getAutoSave()){
					$this->save();
				}
			}

			if($this->isValid()){
			    foreach($this->usedChunks as $index => $d){
				    Level::getXZ($index, $chunkX, $chunkZ);
				    $this->level->unregisterChunkLoader($this, $chunkX, $chunkZ);
				    foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
					    $entity->despawnFrom($this);
				    }
				    unset($this->usedChunks[$index]);
				}
			}
			$this->usedChunks = [];
			$this->loadQueue = [];

			if($this->loggedIn){
			    foreach($this->server->getOnlinePlayers() as $player){
				    if(!$player->canSee($this)){
					    $player->showPlayer($this);
					}
				}
				$this->hiddenPlayers = [];
			}

			foreach($this->windowIndex as $window){
				$this->removeWindow($window);
			}
			$this->windows = [];
			$this->windowIndex = [];

			if($this->constructed){
				parent::close();
			}
			$this->spawned = false;

			if($this->loggedIn){
				$this->loggedIn = false;
				$this->server->removeOnlinePlayer($this);
			}

			$this->server->removePlayer($this);

			$this->server->getLogger()->info($this->getServer()->getLanguage()->translateString("pocketmine.player.logOut", [
				TextFormat::AQUA . $this->getName() . TextFormat::WHITE,
				$this->ip,
				$this->port,
				$this->getServer()->getLanguage()->translateString($reason)
			]));

			$this->spawnPosition = null;

			if($this->perm !== null){
			    $this->perm->clearPermissions();
			    $this->perm = null;
		    }

		    $this->transactionQueue = null;
			
			if($this->server->dserverConfig["enable"] and $this->server->dserverConfig["queryAutoUpdate"]) $this->server->updateQuery();
		}
	}

	/**
	 * @return mixed[]
	 */
	public function __debugInfo(){
		return [];
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function setCanSaveWithChunk(bool $value) : void{
		throw new \BadMethodCallException("Players can't be saved with chunks");
	}

	/**
	 * Handles player data saving
	 */
	public function save(){
		if($this->closed){
			throw new \InvalidStateException("Tried to save closed player");
		}

		parent::saveNBT();

		if($this->isValid()){
			$this->namedtag->Level = new StringTag("Level", $this->level->getFolderName());
		}

		if($this->hasValidSpawnPosition()){
			$this->namedtag["SpawnLevel"] = $this->spawnPosition->getLevel()->getFolderName();
			$this->namedtag["SpawnX"] = $this->spawnPosition->getFloorX();
			$this->namedtag["SpawnY"] = $this->spawnPosition->getFloorY();
			$this->namedtag["SpawnZ"] = $this->spawnPosition->getFloorZ();

			if(!$this->isAlive()){
				//hack for respawn after quit
				$this->namedtag->setTag(new ListTag("Pos", [
					new DoubleTag("", $this->spawnPosition->x),
					new DoubleTag("", $this->spawnPosition->y),
					new DoubleTag("", $this->spawnPosition->z)
				]));
			}
		}

		foreach($this->achievements as $achievement => $status){
			$this->namedtag->Achievements[$achievement] = new ByteTag($achievement, $status === true ? 1 : 0);
		}

		$this->namedtag["playerGameType"] = $this->gamemode;
		$this->namedtag["lastPlayed"] = new LongTag("lastPlayed", floor(microtime(true) * 1000));
		$this->namedtag["Health"] = new ShortTag("Health", $this->getHealth());
		$this->namedtag["MaxHealth"] = new ShortTag("MaxHealth", $this->getMaxHealth());

		if($this->username != ""){
			$this->server->saveOfflinePlayerData($this->username, $this->namedtag);
		}
	}

	/**
	 * Gets the username
	 *
	 * @return string
	 */
	public function getName(){
		return $this->username;
	}

	public function kill(){
		if(!$this->spawned){
			return;
		}

		parent::kill();

		$this->sendRespawnPacket($this->getSpawn());
	}

	protected function callDeathEvent(){
		$message = "death.attack.generic";

		$params = [
			$this->getDisplayName()
		];

		$cause = $this->getLastDamageCause();

		switch($cause === null ? EntityDamageEvent::CAUSE_CUSTOM : $cause->getCause()){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
				if($cause instanceof EntityDamageByEntityEvent){
					$e = $cause->getDamager();
					if($e instanceof Player){
						$message = "death.attack.player";
						$params[] = $e->getDisplayName();
						break;
					}elseif($e instanceof Living){
						$message = "death.attack.mob";
						$params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
						break;
					}else{
						$params[] = "Unknown";
					}
				}
				break;
			case EntityDamageEvent::CAUSE_PROJECTILE:
				if($cause instanceof EntityDamageByEntityEvent){
					$e = $cause->getDamager();
					if($e instanceof Player){
						$message = "death.attack.arrow";
						$params[] = $e->getDisplayName();
					}elseif($e instanceof Living){
						$message = "death.attack.arrow";
						$params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
						break;
					}else{
						$params[] = "Unknown";
					}
				}
				break;
			case EntityDamageEvent::CAUSE_SUICIDE:
				$message = "death.attack.generic";
				break;
			case EntityDamageEvent::CAUSE_VOID:
				$message = "death.attack.outOfWorld";
				break;
			case EntityDamageEvent::CAUSE_FALL:
				if($cause instanceof EntityDamageEvent){
					if($cause->getFinalDamage() > 2){
						$message = "death.fell.accident.generic";
						break;
					}
				}
				$message = "death.attack.fall";
				break;

			case EntityDamageEvent::CAUSE_SUFFOCATION:
				$message = "death.attack.inWall";
				break;

			case EntityDamageEvent::CAUSE_LAVA:
				$message = "death.attack.lava";
				break;

			case EntityDamageEvent::CAUSE_FIRE:
				$message = "death.attack.onFire";
				break;

			case EntityDamageEvent::CAUSE_FIRE_TICK:
				$message = "death.attack.inFire";
				break;

			case EntityDamageEvent::CAUSE_DROWNING:
				$message = "death.attack.drown";
				break;

			case EntityDamageEvent::CAUSE_CONTACT:
				if($cause instanceof EntityDamageByBlockEvent){
					if($cause->getDamager()->getId() === Block::CACTUS){
						$message = "death.attack.cactus";
					}
				}
				break;

			case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
			case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
				if($cause instanceof EntityDamageByEntityEvent){
					$e = $cause->getDamager();
					if($e instanceof Player){
						$message = "death.attack.explosion.player";
						$params[] = $e->getDisplayName();
					}elseif($e instanceof Living){
						$message = "death.attack.explosion.player";
						$params[] = $e->getNameTag() !== "" ? $e->getNameTag() : $e->getName();
						break;
					}
				}else{
					$message = "death.attack.explosion";
				}
				break;

			case EntityDamageEvent::CAUSE_MAGIC:
				$message = "death.attack.magic";
				break;

            default:
				break;
		}

		$ev = new PlayerDeathEvent($this, $this->getDrops(), new TranslationContainer($message, $params));
		$ev->setKeepInventory($this->server->keepInventory);
		$ev->setKeepExperience($this->server->keepExperience);
		$this->server->getPluginManager()->callEvent($ev);

		if(!$ev->getKeepInventory()){
			foreach($ev->getDrops() as $item){
				$this->level->dropItem($this, $item);
			}

			if($this->floatingInventory !== null){		
				$this->floatingInventory->clearAll();		
			}

			if($this->inventory !== null){
				$this->inventory->clearAll();
			}

			if($this->offhandInventory !== null){
			    $this->offhandInventory->clearAll();
            }
		}

		if($this->server->expEnabled and !$ev->getKeepExperience()){
			$exp = min(91, $this->getTotalXp()); 
			$this->getLevel()->spawnXPOrb($this->add(0, 0.2, 0), $exp);
			$this->removeAllEffects();
			$this->setTotalXp(0, true);
		}

		if($ev->getDeathMessage() != ""){
			$this->server->broadcast($ev->getDeathMessage(), Server::BROADCAST_CHANNEL_USERS);
		}

		$this->setHealth(0);
	}

	/**
	 * @param int $amount
	 */
	public function setHealth($amount){
		parent::setHealth($amount);
		if($this->spawned === true){
			$this->foodTick = 0;
			$this->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue($this->getMaxHealth())->setValue($amount, true);
		}
	}

	/**
	 * @param float             $damage
	 * @param EntityDamageEvent $source
	 *
	 * @return bool
	 */
	public function attack($damage, EntityDamageEvent $source){
		if(!$this->isAlive()){
			return false;
		}

		if($this->isCreative()
			and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
			and $source->getCause() !== EntityDamageEvent::CAUSE_VOID
		){
			$source->setCancelled();
		}elseif($this->allowFlight and $source->getCause() === EntityDamageEvent::CAUSE_FALL){
			$source->setCancelled();
		}

		parent::attack($damage, $source);

		if($source->isCancelled()){
			return false;
		}elseif($this->getLastDamageCause() === $source and $this->spawned){
			$pk = new EntityEventPacket();
			$pk->eid = $this->id;
			$pk->event = EntityEventPacket::HURT_ANIMATION;
			$this->dataPacket($pk);

			$this->exhaust(0.3, PlayerExhaustEvent::CAUSE_DAMAGE);
		}
		return true;
	}

	public function getOffsetPosition(Vector3 $vector3) : Vector3{
		$result = parent::getOffsetPosition($vector3);
		$result->y += 0.001;
		return $result;
	}

	protected function checkChunks(){
		if($this->chunk === null or ($this->chunk->getX() !== ($this->x >> 8) or $this->chunk->getZ() !== ($this->z >> 5))){
			if($this->chunk !== null){
				$this->chunk->removeEntity($this);
			}
			$this->chunk = $this->level->getChunk($this->x >> 4, $this->z >> 4, true);

			if(!$this->justCreated){
				$newChunk = $this->level->getChunkPlayers($this->x >> 4, $this->z >> 4);
				unset($newChunk[$this->getLoaderId()]);

				/** @var Player[] $reload */
				$reload = [];
				foreach($this->hasSpawned as $player){
					if(!isset($newChunk[$player->getLoaderId()])){
						$this->despawnFrom($player);
					}else{
						unset($newChunk[$player->getLoaderId()]);
						$reload[] = $player;
					}
				}

				foreach($newChunk as $player){
					$this->spawnTo($player);
				}
			}

			if($this->chunk === null){
				return;
			}

			$this->chunk->addEntity($this);
		}
	}

	public function sendPosition(Vector3 $pos, float $yaw = null, float $pitch = null, int $mode = MovePlayerPacket::MODE_NORMAL, array $targets = null){
		$yaw = $yaw ?? $this->yaw;
		$pitch = $pitch ?? $this->pitch;

		$fix = $this->getOffsetPosition($pos);

		$pk = new MovePlayerPacket();
		$pk->eid = $this->getId();
		$pk->x = $fix->x;
		$pk->y = $fix->y;
		$pk->z = $fix->z;
		$pk->bodyYaw = $yaw;
		$pk->pitch = $pitch;
		$pk->yaw = $yaw;
		$pk->mode = $mode;
		$pk->onGround = $this->onGround;

		if($targets !== null){
			if(in_array($this, $targets, true)){
				$this->ySize = 0;
			}
			$this->server->broadcastPacket($targets, $pk);
		}else{
			$this->ySize = 0;
			$this->dataPacket($pk);
		}

		$this->newPosition = null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function teleport(Vector3 $pos, float $yaw = null, float $pitch = null) : bool{
		if(parent::teleport($pos, $yaw, $pitch)){

			foreach($this->windowIndex as $window){
				if($window === $this->inventory){
					continue;
				}
				$this->removeWindow($window);
			}

			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT);
			$this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_TELEPORT, $this->getViewers());

			$this->spawnToAll();

			$this->resetFallDistance();
			$this->nextChunkOrderRun = 0;
			if($this->spawnChunkLoadCount !== -2){
				$this->spawnChunkLoadCount = 0;
			}
			$this->newPosition = null;
			$this->stopSleep();

			$this->isTeleporting = true;

			$this->resetLastMovements();
			
			return true;
		}
		
		return false;
	}

	/**
	 * Returns the window ID which the inventory has for this player, or -1 if the window is not open to the player.
	 *
	 * @param Inventory $inventory
	 *
	 * @return int
	 */
	public function getWindowId(Inventory $inventory) : int{
		return $this->windows[spl_object_hash($inventory)] ?? -1;
	}

	/**
	 * Returns the created/existing window id
	 *
	 * @param Inventory $inventory
	 * @param int       $forceId
	 *
	 * @return int
	 */
	public function addWindow(Inventory $inventory, $forceId = null) : int{
		if(($id = $this->getWindowId($inventory)) !== -1){
			return $id;
		}

		if($forceId === null){
			$this->windowCnt = $cnt = max(2, ++$this->windowCnt % 99);
		}else{
			$cnt = (int) $forceId;
		}
		$this->windowIndex[$cnt] = $inventory;
		$this->windows[spl_object_hash($inventory)] = $cnt;
		if($inventory->open($this)){
			return $cnt;
		}else{
			$this->removeWindow($inventory);

			return -1;
		}
	}

	/**
	 * @param Inventory $inventory
	 */
	public function removeWindow(Inventory $inventory){
		if(isset($this->windows[$hash = spl_object_hash($inventory)])){
			$id = $this->windows[$hash];

			unset($this->windows[$hash], $this->windowIndex[$id]);
		}

		$inventory->close($this);
	}

	public function setMetadata(string $metadataKey, MetadataValue $newMetadataValue){
		$this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $newMetadataValue);
	}

	public function getMetadata(string $metadataKey){
		return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
	}

	public function hasMetadata(string $metadataKey) : bool{
		return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
	}

	public function removeMetadata(string $metadataKey, Plugin $owningPlugin){
		$this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $owningPlugin);
	}

	public function onChunkChanged(Chunk $chunk){
		$hasSent = $this->usedChunks[$hash = Level::chunkHash($chunk->getX(), $chunk->getZ())] ?? false;
		if($hasSent){
			$this->usedChunks[$hash] = false;
			$this->nextChunkOrderRun = 0;
		}
	}

	/**
	 * @param Chunk $chunk
	 */
	public function onChunkLoaded(Chunk $chunk){

	}

	/**
	 * @param Chunk $chunk
	 */
	public function onChunkPopulated(Chunk $chunk){

	}

	/**
	 * @param Chunk $chunk
	 */
	public function onChunkUnloaded(Chunk $chunk){

	}

	/**
	 * @param Vector3 $block
	 */
	public function onBlockChanged(Vector3 $block){

	}

	/**
	 * @return int|null
	 */
	public function getLoaderId(){
		return $this->loaderId;
	}

	/**
	 * @return bool
	 */
	public function isLoaderActive(){
		return $this->isConnected();
	}

	/**
	 * @param Effect $effect
	 *
	 * @return bool|void
	 * @internal param $Effect
	 */
	public function addEffect(Effect $effect){//Overwrite
		if($effect->isBad() && $this->isCreative()){
			return;
		}

		parent::addEffect($effect);
	}

	/**
	 * Validates the given username.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function isValidUserName(string $name) : bool{
		if($name === null){
			return false;
		}

		$lname = strtolower($name);
		$len = strlen($name);
		return $lname !== "rcon" and $lname !== "console" and $len >= 1 and $len <= 16 and preg_match("/[^A-Za-z0-9_]/", $name) === 0;
	}

	/**
	 * Gets client's language
	 *
	 * @return string
	 */
	public function getLanguageCode(): string{
		return $this->languageCode;
	}

	/**
	 * @return string
	 */
	public function getLowerCaseName() : string{
		return $this->iusername;
	}
}