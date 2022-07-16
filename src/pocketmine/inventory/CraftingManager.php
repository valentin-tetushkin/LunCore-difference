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

namespace pocketmine\inventory;

use pocketmine\event\Timings;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\Potion;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\UUID;

class CraftingManager {
	/** @var Recipe[] */
	public $recipes = [];

	/** @var Recipe[][] */
	protected $recipeLookup = [];

	/** @var FurnaceRecipe[] */
	public $furnaceRecipes = [];

	/** @var BrewingRecipe[] */
	public $brewingRecipes = [];

	private static $RECIPE_COUNT = 0;

	/** @var BatchPacket */
	private $craftingDataCache;

	/**
	 * CraftingManager constructor.
	 */
	public function __construct(){
		$this->init();
	}

	public function init() : void{
		$this->registerBrewingStand();

		// load recipes from src/pocketmine/resources/recipes.json
		$recipes = new Config(Server::getInstance()->getFilePath() . "src/pocketmine/resources/recipes.json", Config::JSON, []);

		foreach($recipes->getAll() as $recipe){
			switch($recipe["type"]){
				case 0:
					// TODO: handle multiple result items
					$first = $recipe["output"][0];
					$result = new ShapelessRecipe(Item::get($first["id"], $first["damage"], $first["count"], $first["nbt"]));

					foreach($recipe["input"] as $ingredient){
						$result->addIngredient(Item::get($ingredient["id"], $ingredient["damage"], $ingredient["count"], $first["nbt"]));
					}
					$this->registerRecipe($result);
					break;
				case 1:
					// TODO: handle multiple result items
					$first = $recipe["output"][0];
                    try {
                        $result = new ShapedRecipe(Item::get($first["id"], $first["damage"], $first["count"], $first["nbt"]), $recipe["height"], $recipe["width"]);
                    } catch (\Exception $e) {
                    }

                    $shape = array_chunk($recipe["input"], $recipe["width"]);
					foreach($shape as $y => $row){
						foreach($row as $x => $ingredient){
							$result->addIngredient($x, $y, Item::get($ingredient["id"], ($ingredient["damage"] < 0 ? -1 : $ingredient["damage"]), $ingredient["count"], $ingredient["nbt"]));
						}
					}
					$this->registerRecipe($result);
					break;
				case 2:
				case 3:
					$result = $recipe["output"];
					$resultItem = Item::get($result["id"], $result["damage"], $result["count"], $result["nbt"]);
					$this->registerRecipe(new FurnaceRecipe($resultItem, Item::get($recipe["inputId"], $recipe["inputDamage"] ?? -1)));
					break;
				default:
					break;
			}
		}

		$this->buildCraftingDataCache();
	}

	/**
	 * Rebuilds the cached CraftingDataPacket.
	 */
	public function buildCraftingDataCache(){
		Timings::$craftingDataCacheRebuildTimer->startTiming();
		$pk = new CraftingDataPacket();
		$pk->cleanRecipes = true;

		foreach($this->recipes as $recipe){
			if($recipe instanceof ShapedRecipe){
				$pk->addShapedRecipe($recipe);
			}elseif($recipe instanceof ShapelessRecipe){
				$pk->addShapelessRecipe($recipe);
			}
		}

		foreach($this->furnaceRecipes as $recipe){
			$pk->addFurnaceRecipe($recipe);
		}

		$pk->encode();
		$pk->isEncoded = true;

		$batch = new BatchPacket();
		$batch->addPacket($pk);
		$batch->setCompressionLevel(Server::getInstance()->networkCompressionLevel);
		$batch->encode();

		$this->craftingDataCache = $batch;
		Timings::$craftingDataCacheRebuildTimer->stopTiming();
	}

	/**
	 * Returns a pre-compressed CraftingDataPacket for sending to players. Rebuilds the cache if it is not found.
	 *
	 * @return BatchPacket
	 */
	public function getCraftingDataPacket() : BatchPacket{
		if($this->craftingDataCache === null){
			$this->buildCraftingDataCache();
		}

		return $this->craftingDataCache;
	}

	protected function registerBrewingStand(){
		//Potion
		//WATER_BOTTLE
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::AWKWARD), Item::get(ItemIds::NETHER_WART), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::THICK), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::MUNDANE_EXTENDED), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::WEAKNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::MUNDANE), Item::get(ItemIds::GHAST_TEAR), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::MUNDANE), Item::get(ItemIds::GLISTERING_MELON), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::MUNDANE), Item::get(ItemIds::BLAZE_POWDER), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::MUNDANE), Item::get(ItemIds::MAGMA_CREAM), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::MUNDANE), Item::get(ItemIds::SUGAR), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::MUNDANE), Item::get(ItemIds::SPIDER_EYE), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::MUNDANE), Item::get(ItemIds::RABBIT_FOOT), Item::get(ItemIds::POTION, Potion::WATER_BOTTLE)));
		//To WEAKNESS
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::WEAKNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::MUNDANE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::WEAKNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::THICK)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::WEAKNESS_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::MUNDANE_EXTENDED)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::WEAKNESS_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::WEAKNESS)));
		//GHAST_TEAR and BLAZE_POWDER
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::REGENERATION), Item::get(ItemIds::GHAST_TEAR), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::REGENERATION_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::REGENERATION)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::REGENERATION_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::POTION, Potion::REGENERATION)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::STRENGTH), Item::get(ItemIds::BLAZE_POWDER), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::STRENGTH_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::STRENGTH)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::STRENGTH_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::POTION, Potion::STRENGTH)));
		//SPIDER_EYE GLISTERING_MELON and PUFFERFISH
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::POISON), Item::get(ItemIds::SPIDER_EYE), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::POISON_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::POISON)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::POISON_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::POTION, Potion::POISON)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::HEALING), Item::get(ItemIds::GLISTERING_MELON), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::HEALING_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::POTION, Potion::HEALING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::WATER_BREATHING), Item::get(ItemIds::PUFFER_FISH), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::WATER_BREATHING_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::WATER_BREATHING)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::HARMING), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::WATER_BREATHING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::HARMING), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::HEALING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::HARMING), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::POISON)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::HARMING_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::POTION, Potion::HARMING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::HARMING_TWO), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::HEALING_TWO)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::HARMING_TWO), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::POISON_T)));
		//SUGAR MAGMA_CREAM and RABBIT_FOOT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SWIFTNESS), Item::get(ItemIds::SUGAR), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SWIFTNESS_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::SWIFTNESS)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SWIFTNESS_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::POTION, Potion::SWIFTNESS)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::FIRE_RESISTANCE), Item::get(ItemIds::MAGMA_CREAM), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::FIRE_RESISTANCE_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::FIRE_RESISTANCE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::LEAPING), Item::get(ItemIds::RABBIT_FOOT), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::LEAPING_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::LEAPING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::LEAPING_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::POTION, Potion::LEAPING)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SLOWNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::FIRE_RESISTANCE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SLOWNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::SWIFTNESS)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SLOWNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::LEAPING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SLOWNESS_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::FIRE_RESISTANCE_T)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SLOWNESS_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::LEAPING_T)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SLOWNESS_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::SWIFTNESS_T)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::SLOWNESS_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::SLOWNESS)));
		//GOLDEN_CARROT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::NIGHT_VISION), Item::get(ItemIds::GOLDEN_CARROT), Item::get(ItemIds::POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::NIGHT_VISION_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::NIGHT_VISION)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::INVISIBILITY), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::NIGHT_VISION)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::INVISIBILITY_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::POTION, Potion::INVISIBILITY)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::POTION, Potion::INVISIBILITY_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::POTION, Potion::NIGHT_VISION_T)));
		//===================================================================分隔符=======================================================================
		//SPLASH_POTION
		//WATER_BOTTLE
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD), Item::get(ItemIds::NETHER_WART), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::THICK), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE_EXTENDED), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::WEAKNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE), Item::get(ItemIds::GHAST_TEAR), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE), Item::get(ItemIds::GLISTERING_MELON), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE), Item::get(ItemIds::BLAZE_POWDER), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE), Item::get(ItemIds::MAGMA_CREAM), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE), Item::get(ItemIds::SUGAR), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE), Item::get(ItemIds::SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE), Item::get(ItemIds::RABBIT_FOOT), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BOTTLE)));
		//To WEAKNESS
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::WEAKNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::WEAKNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::THICK)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::WEAKNESS_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::MUNDANE_EXTENDED)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::WEAKNESS_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::WEAKNESS)));
		//GHAST_TEAR and BLAZE_POWDER
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::REGENERATION), Item::get(ItemIds::GHAST_TEAR), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::REGENERATION_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::REGENERATION)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::REGENERATION_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::REGENERATION)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::STRENGTH), Item::get(ItemIds::BLAZE_POWDER), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::STRENGTH_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::STRENGTH)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::STRENGTH_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::STRENGTH)));
		//SPIDER_EYE GLISTERING_MELON and PUFFERFISH
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::POISON), Item::get(ItemIds::SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::POISON_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::POISON)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::POISON_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::POISON)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::HEALING), Item::get(ItemIds::GLISTERING_MELON), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::HEALING_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::HEALING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BREATHING), Item::get(ItemIds::PUFFER_FISH), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BREATHING_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BREATHING)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::HARMING), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::WATER_BREATHING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::HARMING), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::HEALING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::HARMING), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::POISON)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::HARMING_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::HARMING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::HARMING_TWO), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::HEALING_TWO)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::HARMING_TWO), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::POISON_T)));
		//SUGAR MAGMA_CREAM and RABBIT_FOOT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SWIFTNESS), Item::get(ItemIds::SUGAR), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SWIFTNESS_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::SWIFTNESS)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SWIFTNESS_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::SWIFTNESS)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::FIRE_RESISTANCE), Item::get(ItemIds::MAGMA_CREAM), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::FIRE_RESISTANCE_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::FIRE_RESISTANCE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::LEAPING), Item::get(ItemIds::RABBIT_FOOT), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::LEAPING_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::LEAPING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::LEAPING_TWO), Item::get(ItemIds::GLOWSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::LEAPING)));

		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::FIRE_RESISTANCE)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::SWIFTNESS)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::LEAPING)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::FIRE_RESISTANCE_T)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::LEAPING_T)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::SWIFTNESS_T)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::SLOWNESS)));
		//GOLDEN_CARROT
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::NIGHT_VISION), Item::get(ItemIds::GOLDEN_CARROT), Item::get(ItemIds::SPLASH_POTION, Potion::AWKWARD)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::NIGHT_VISION_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::NIGHT_VISION)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::INVISIBILITY), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::NIGHT_VISION)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::INVISIBILITY_T), Item::get(ItemIds::REDSTONE_DUST), Item::get(ItemIds::SPLASH_POTION, Potion::INVISIBILITY)));
		$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, Potion::INVISIBILITY_T), Item::get(ItemIds::FERMENTED_SPIDER_EYE), Item::get(ItemIds::SPLASH_POTION, Potion::NIGHT_VISION_T)));
		//===================================================================分隔符=======================================================================
		//普通药水升级成喷溅
		foreach(Potion::POTIONS as $potion => $effect){
			$this->registerBrewingRecipe(new BrewingRecipe(Item::get(ItemIds::SPLASH_POTION, $potion), Item::get(ItemIds::GUNPOWDER), Item::get(ItemIds::POTION, $potion)));
		}
	}

	/**
	 * @param Item $i1
	 * @param Item $i2
	 *
	 * @return int
	 */
	public function sort(Item $i1, Item $i2){
		if($i1->getId() > $i2->getId()){
			return 1;
		}elseif($i1->getId() < $i2->getId()){
			return -1;
		}elseif($i1->getDamage() > $i2->getDamage()){
			return 1;
		}elseif($i1->getDamage() < $i2->getDamage()){
			return -1;
		}elseif($i1->getCount() > $i2->getCount()){
			return 1;
		}elseif($i1->getCount() < $i2->getCount()){
			return -1;
		}else{
			return 0;
		}
	}

	/**
	 * @param UUID $id
	 *
	 * @return Recipe
	 */
	public function getRecipe(UUID $id){
		$index = $id->toBinary();
		return $this->recipes[$index] ?? null;
	}

	/**
	 * @return Recipe[]
	 */
	public function getRecipes(){
		return $this->recipes;
	}

	/**
	 * @param Item $item
	 *
	 * @return array
	 */
	public function getRecipesByResult(Item $item){
		return @array_values($this->recipeLookup[$item->getId() . ":" . $item->getDamage()]) ?? [];
	}

	/**
	 * @return FurnaceRecipe[]
	 */
	public function getFurnaceRecipes(){
		return $this->furnaceRecipes;
	}

	/**
	 * @param Item $input
	 *
	 * @return FurnaceRecipe
	 */
	public function matchFurnaceRecipe(Item $input) : ?FurnaceRecipe{
		return $this->furnaceRecipes[$input->getId() . ":" . $input->getDamage()] ?? $this->furnaceRecipes[$input->getId() . ":?"] ?? null;
	}


	/**
	 * @param Item $input
	 * @param Item $potion
	 *
	 * @return BrewingRecipe
	 */
	public function matchBrewingRecipe(Item $input, Item $potion){
		$subscript = $input->getId() . ":" . ($input->getDamage() === null ? "0" : $input->getDamage()) . ":" . $potion->getId() . ":" . ($potion->getDamage() === null ? "0" : $potion->getDamage());
		if(isset($this->brewingRecipes[$subscript])){
			return $this->brewingRecipes[$subscript];
		}
		return null;
	}

	/**
	 * @param ShapedRecipe $recipe
	 */
	public function registerShapedRecipe(ShapedRecipe $recipe){
		$result = $recipe->getResult();
		$this->recipes[$recipe->getId()->toBinary()] = $recipe;
		$ingredients = $recipe->getIngredientMap();
		$hash = "";
		foreach($ingredients as $v){
			foreach($v as $item){
				if($item !== null){
					/** @var Item $item */
					$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
				}
			}
			$hash .= ";";
		}
		$this->recipeLookup[$result->getId() . ":" . $result->getDamage()][$hash] = $recipe;
		$this->craftingDataCache = null;
	}

	/**
	 * @param ShapelessRecipe $recipe
	 */
	public function registerShapelessRecipe(ShapelessRecipe $recipe){
		$result = $recipe->getResult();
		$this->recipes[$recipe->getId()->toBinary()] = $recipe;
		$hash = "";
		$ingredients = $recipe->getIngredientList();
		usort($ingredients, [$this, "sort"]);
		foreach($ingredients as $item){
			$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
		}
		$this->recipeLookup[$result->getId() . ":" . $result->getDamage()][$hash] = $recipe;
		$this->craftingDataCache = null;
	}

	/**
	 * @param FurnaceRecipe $recipe
	 */
	public function registerFurnaceRecipe(FurnaceRecipe $recipe){
		$input = $recipe->getInput();
		$this->furnaceRecipes[$input->getId() . ":" . ($input->hasAnyDamageValue() ? "?" : $input->getDamage())] = $recipe;
		$this->craftingDataCache = null;
	}

	/**
	 * @param BrewingRecipe $recipe
	 */
	public function registerBrewingRecipe(BrewingRecipe $recipe){
		$input = $recipe->getInput();
		$potion = $recipe->getPotion();
		$this->brewingRecipes[$input->getId() . ":" . ($input->getDamage() === null ? "0" : $input->getDamage()) . ":" . $potion->getId() . ":" . ($potion->getDamage() === null ? "0" : $potion->getDamage())] = $recipe;
	}

	/**
	 * @param ShapelessRecipe $recipe
	 *
	 * @return bool
	 */
	public function matchRecipe(ShapelessRecipe $recipe){
		if(!isset($this->recipeLookup[$idx = $recipe->getResult()->getId() . ":" . $recipe->getResult()->getDamage()])){
			return false;
		}
		$hash = "";
		$ingredients = $recipe->getIngredientList();
		usort($ingredients, [$this, "sort"]);
		foreach($ingredients as $item){
			$hash .= $item->getId() . ":" . ($item->hasAnyDamageValue() ? "?" : $item->getDamage()) . "x" . $item->getCount() . ",";
		}
		if(isset($this->recipeLookup[$idx][$hash])){
			return true;
		}
		$hasRecipe = null;
		foreach($this->recipeLookup[$idx] as $recipe){
			if($recipe instanceof ShapelessRecipe){
				if($recipe->getIngredientCount() !== count($ingredients)){
					continue;
				}
				$checkInput = $recipe->getIngredientList();
				foreach($ingredients as $item){
					$amount = $item->getCount();
					foreach($checkInput as $k => $checkItem){
						if($checkItem->equals($item, !$checkItem->hasAnyDamageValue(), $checkItem->hasCompoundTag())){
							$remove = min($checkItem->getCount(), $amount);
							$checkItem->setCount($checkItem->getCount() - $remove);
							if($checkItem->getCount() === 0){
								unset($checkInput[$k]);
							}
							$amount -= $remove;
							if($amount === 0){
								break;
							}
						}
					}
				}
				if(count($checkInput) === 0){
					$hasRecipe = $recipe;
					break;
				}
			}
			if($hasRecipe instanceof Recipe){
				break;
			}
		}
		return $hasRecipe !== null;
	}

	/**
	 * @param Recipe $recipe
	 */
	public function registerRecipe(Recipe $recipe){
		$recipe->setId(UUID::fromData((string) ++self::$RECIPE_COUNT, (string) $recipe->getResult()->getId(), (string) $recipe->getResult()->getDamage(), (string) $recipe->getResult()->getCount(), $recipe->getResult()->getCompoundTag()));
		if($recipe instanceof ShapedRecipe){
			$this->registerShapedRecipe($recipe);
		}elseif($recipe instanceof ShapelessRecipe){
			$this->registerShapelessRecipe($recipe);
		}elseif($recipe instanceof FurnaceRecipe){
			$this->registerFurnaceRecipe($recipe);
		}
	}
}
