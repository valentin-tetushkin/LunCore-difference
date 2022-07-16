<?php

namespace pocketmine\entity;

use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};

class EnderCrystal extends Vehicle{
	
   const NETWORK_ID = 71;

   public $height = 0.7;
   public $width = 1.6;
   public $gravity = 0.5;
   public $drag = 0.1;

   public function __construct(Level $level, CompoundTag $nbt){
    parent::__construct($level, $nbt);
}

public function spawnTo(Player $p){
    $packet = new AddEntityPacket();
	$packet->eid = $this->getId();
	$packet->type = EnderCrystal::NETWORK_ID;
	$packet->x = $this->x;
	$packet->y = $this->y;
	$packet->z = $this->z;
	$packet->speedX = 0;
	$packet->speedY = 0;
	$packet->speedZ = 0;
	$packet->yaw = 0;
	$packet->pitch = 0;
	$packet->metadata = $this->dataProperties;
	$p->dataPacket($packet);
	parent::spawnTo($p);
}

public function onTap(PlayerInteractEvent $e){
    $p = $e->getPlayer();
    $block = $e->getBlock();
    $Name = strtolower($p->getName());
    $x = $block->getX() + 0.5;
    $y = $block->getY() + 0.5;
    $z = $block->getZ() + 0.5;
      if($e->getItem()->getId() == 426) {
         if($block->getId() == 49){
          $tag = new CompoundTag("", [
                 new ListTag("Pos", [
                 new DoubleTag("", $block->getX() + 0.5),
                 new DoubleTag("", $block->getY() + 0.5),
                 new DoubleTag("", $block->getZ() + 0.5)
                 ]),
        
                 new ListTag("Motion", [
                 new DoubleTag("", 0.0),
                 new DoubleTag("", 0.0),
                 new DoubleTag("", 0.0)
                 ]),
                
                 new ListTag("Rotation", [
                 new FloatTag("", $p->getYaw()),
                 new FloatTag("", $p->getPitch())
                 ])
          ]);
	         $p->getInventory()->removeItem(Item::get(426, 0, 1));
}
}
}

public function onDamage(EntityDamageEvent $e){
      if($e instanceof EntityDamageByEntityEvent){
         if($e->getEntity() instanceof EnderCrystal){
          $p = $e->getDamager();
          $e->getEntity()->Kill();
          $p->getLevel()->addSound(new ExplodeSound($p));
          $pos = $e->getEntity()->asPosition();
          $explode = new Explosion($pos, 7);
          $explode->explodeB();
          $p->getLevel()->setBlock(new Vector3($e->getEntity()->getX(), $e->getEntity()->getY(), $e->getEntity()->getZ()), Block::get(Block::AIR));
}
}
}
}