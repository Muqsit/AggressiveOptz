<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\helper\world;

use muqsit\aggressiveoptz\AggressiveOptzAPI;
use pocketmine\event\EventPriority;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\world\World;

final class AggressiveOptzWorldCacheManager{

	/** @var AggressiveOptzWorldCache[] */
	private $worlds = [];

	public function __construct(){
	}

	public function init(AggressiveOptzAPI $api) : void{
		$api->registerEvent(function(WorldLoadEvent $event) : void{
			$this->onWorldLoad($event->getWorld());
		}, EventPriority::LOWEST);
		$api->registerEvent(function(WorldUnloadEvent $event) : void{
			$this->onWorldUnload($event->getWorld());
		}, EventPriority::MONITOR);
		$api->registerEvent(function(ChunkLoadEvent $event) : void{
			$chunk = $event->getChunk();
			$this->onChunkLoad($event->getWorld(), $chunk->getX(), $chunk->getZ());
		}, EventPriority::LOWEST);
		$api->registerEvent(function(ChunkUnloadEvent $event) : void{
			$chunk = $event->getChunk();
			$this->onChunkUnload($event->getWorld(), $chunk->getX(), $chunk->getZ());
		}, EventPriority::MONITOR);

		foreach($api->getServer()->getWorldManager()->getWorlds() as $world){
			$this->onWorldLoad($world);
		}
	}

	private function onWorldLoad(World $world) : void{
		$this->worlds[$world->getId()] = new AggressiveOptzWorldCache($world);
	}

	private function onWorldUnload(World $world) : void{
		unset($this->worlds[$world->getId()]);
	}

	private function onChunkLoad(World $world, int $x, int $z) : void{
		$this->worlds[$world->getId()]->onChunkLoad($x, $z);
	}

	private function onChunkUnload(World $world, int $x, int $z) : void{
		if(isset($this->worlds[$id = $world->getId()])){ // WorldUnloadEvent is called before ChunkUnloadEvent :(
			$this->worlds[$id]->onChunkUnload($x, $z);
		}
	}

	public function get(World $world) : AggressiveOptzWorldCache{
		return $this->worlds[$world->getId()];
	}
}