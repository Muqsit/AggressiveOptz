<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component\defaults;

use Closure;
use LogicException;
use muqsit\aggressiveoptz\AggressiveOptzApi;
use muqsit\aggressiveoptz\component\OptimizationComponent;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\Armor;
use pocketmine\item\Banner;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use function assert;

class NetworkItemOptimizationComponent implements OptimizationComponent{

	public static function fromConfig(array $config) : NetworkItemOptimizationComponent{
		return new self();
	}

	private ?Closure $unregister = null;

	public function __construct(){
	}

	public function enable(AggressiveOptzApi $api) : void{
		if($this->unregister !== null){
			throw new LogicException("Tried to register event handler twice");
		}

		$this->unregister = $api->registerEvent(function(DataPacketSendEvent $event) : void{
			$targets = $event->getTargets();
			foreach($event->getPackets() as $packet){
				if($packet instanceof MobEquipmentPacket){
					foreach($targets as $target){
						if($packet->actorRuntimeId === $target->getPlayer()?->getId()){
							continue 2;
						}
					}
					$nbt = $packet->item->getItemStack()->getNbt();
					if($nbt !== null){
						$this->cleanItemStackNbt($nbt);
					}
				}elseif($packet instanceof MobArmorEquipmentPacket){
					foreach($targets as $target){
						if($packet->actorRuntimeId === $target->getPlayer()?->getId()){
							continue 2;
						}
					}

					$nbt = $packet->head->getItemStack()->getNbt();
					if($nbt !== null){
						$this->cleanItemStackNbt($nbt);
					}
					$nbt = $packet->chest->getItemStack()->getNbt();
					if($nbt !== null){
						$this->cleanItemStackNbt($nbt);
					}
					$nbt = $packet->legs->getItemStack()->getNbt();
					if($nbt !== null){
						$this->cleanItemStackNbt($nbt);
					}
					$nbt = $packet->feet->getItemStack()->getNbt();
					if($nbt !== null){
						$this->cleanItemStackNbt($nbt);
					}
				}
			}
		}, EventPriority::HIGHEST);
	}

	private function cleanItemStackNbt(CompoundTag $nbt) : void{
		foreach($nbt->getValue() as $name => $tag){
			if(
				$name === Armor::TAG_CUSTOM_COLOR ||
				$name === Banner::TAG_PATTERNS ||
				$name === Banner::TAG_PATTERN_COLOR ||
				$name === Banner::TAG_PATTERN_NAME
			){
				continue;
			}

			if($name === Item::TAG_ENCH){
				assert($tag instanceof ListTag);
				for($i = $tag->count() - 1; $i > 0; $i--){
					$tag->remove($i);
				}
				continue;
			}

			$nbt->removeTag($name);
		}
	}

	public function disable(AggressiveOptzApi $api) : void{
		if($this->unregister === null){
			throw new LogicException("Tried to unregister an unregistered event handler");
		}

		($this->unregister)();
		$this->unregister = null;
	}
}