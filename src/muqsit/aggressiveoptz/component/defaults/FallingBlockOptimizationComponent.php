<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component\defaults;

use Closure;
use InvalidArgumentException;
use InvalidStateException;
use muqsit\aggressiveoptz\AggressiveOptzAPI;
use muqsit\aggressiveoptz\component\defaults\utils\FallingBlockChunkInfo;
use muqsit\aggressiveoptz\component\OptimizationComponent;
use muqsit\aggressiveoptz\helper\world\AggressiveOptzChunkCache;
use pocketmine\block\BlockFactory;
use pocketmine\block\utils\Fallable;
use pocketmine\entity\object\FallingBlock;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\math\Vector3;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;
use function array_key_exists;

class FallingBlockOptimizationComponent implements OptimizationComponent{
	
	private const CACHE_KEY_FALLING_BLOCK_INFO = "aggressiveoptz:falling_block_info";

	public static function fromConfig(array $config) : FallingBlockOptimizationComponent{
		return new self($config["falling_block_queue_size"], $config["falling_block_max_height"], $config["falling_block_max_count"] ?? $config["falling_block_queue_size"]);
	}

	private int $falling_block_queue_size;
	private int $falling_block_max_height;
	private int $falling_block_max_count;

	/** @var Closure[] */
	private array $unregisters = [];

	/** @var int[] */
	private array $entity_spawn_chunks = [];

	public function __construct(int $falling_block_queue_size, int $falling_block_max_height, int $falling_block_max_count){
		if($falling_block_queue_size < 0){
			throw new InvalidArgumentException("Falling block queue size cannot be negative");
		}
		$this->falling_block_queue_size = $falling_block_queue_size;

		if($falling_block_max_height < 0){
			throw new InvalidArgumentException("Falling block queue size cannot be negative");
		}
		if($falling_block_queue_size > $falling_block_max_height){
			throw new InvalidArgumentException("Falling block queue size cannot be greater than falling block max height");
		}
		$this->falling_block_max_height = $falling_block_max_height >= World::Y_MAX ? -1 : $falling_block_max_height;

		$this->falling_block_max_count = $falling_block_max_count;
	}

	private function getChunkInfo(AggressiveOptzChunkCache $chunk) : FallingBlockChunkInfo{
		$info = $chunk->get(self::CACHE_KEY_FALLING_BLOCK_INFO);
		if($info === null){
			$chunk->set(self::CACHE_KEY_FALLING_BLOCK_INFO, $info = new FallingBlockChunkInfo());
		}
		return $info;
	}

	public function enable(AggressiveOptzAPI $api) : void{
		if(count($this->unregisters) > 0){
			throw new InvalidStateException("Tried to register event handlers twice");
		}

		$world_cache_manager = $api->getHelper()->getWorldCacheManager();
		$this->unregisters = [
			$api->registerEvent(function(EntitySpawnEvent $event) use($world_cache_manager) : void{
				$entity = $event->getEntity();
				if($entity instanceof FallingBlock && !$entity->isClosed() && !$entity->isFlaggedForDespawn()){
					$real_pos = $entity->getPosition();
					$world = $real_pos->getWorld();

					$chunk = $world_cache_manager->get($world)->getChunk($chunkX = $real_pos->getFloorX() >> 4, $chunkZ = $real_pos->getFloorZ() >> 4);
					if($chunk !== null){
						$this->entity_spawn_chunks[$entity->getId()] = World::chunkHash($chunkX, $chunkZ);
						$info = $this->getChunkInfo($chunk);
						$count = ++$info->entity_count;
					}else{
						$count = 1;
					}

					$motion = $entity->getMotion();
					if($motion->x == 0.0 && $motion->z == 0.0){ // moved by gravitation only
						$iterator = new SubChunkExplorer($world);
						$pos = $real_pos->add(-$entity->size->getWidth() / 2, $entity->size->getHeight(), -$entity->size->getWidth() / 2)->floor();

						/** @var int $x */
						$x = $pos->x;
						/** @var int $y */
						$y = $pos->y;
						/** @var int $z */
						$z = $pos->z;

						$xc = $x & 0x0f;
						$zc = $z & 0x0f;

						static $not_replaceable = null;
						if($not_replaceable === null){
							$not_replaceable = [];
							foreach(BlockFactory::getInstance()->getAllKnownStates() as $state){
								if(!$state->canBeReplaced()){
									$not_replaceable[$state->getFullId()] = true;
								}
							}
						}

						if($count >= $this->falling_block_queue_size){
							while($y > 0){
								if($iterator->moveTo($x, $y, $z) === SubChunkExplorerStatus::INVALID){
									break;
								}

								assert($iterator->currentSubChunk !== null);
								if(array_key_exists($iterator->currentSubChunk->getFullBlock($xc, $y & 0x0f, $zc), $not_replaceable)){
									$entity->teleport(new Vector3($real_pos->x, $y + 1 + ($entity->size->getHeight() / 2), $real_pos->z));
									$entity->setMotion($motion);
									break;
								}
								--$y;
							}
						}elseif($this->falling_block_max_height !== -1){
							$begin = $y;
							while($y > 0){
								if($iterator->moveTo($x, $y, $z) === SubChunkExplorerStatus::INVALID){
									break;
								}

								assert($iterator->currentSubChunk !== null);
								if(array_key_exists($iterator->currentSubChunk->getFullBlock($xc, $y & 0x0f, $zc), $not_replaceable)){
									break;
								}

								--$y;
							}
							if($begin - $y >= $this->falling_block_max_height){
								$entity->teleport(new Vector3($real_pos->x, $y + 1 + ($entity->size->getHeight() / 2), $real_pos->z));
								$entity->setMotion($motion);
							}
						}
					}
				}
			}),

			$api->registerEvent(function(EntityDespawnEvent $event) use($world_cache_manager) : void{
				$entity = $event->getEntity();
				if(array_key_exists($id = $entity->getId(), $this->entity_spawn_chunks)){
					World::getXZ($this->entity_spawn_chunks[$id], $chunkX, $chunkZ);
					unset($this->entity_spawn_chunks[$id]);
					$chunk = $world_cache_manager->get($world = $entity->getWorld())->getChunk($chunkX, $chunkZ);

					if($chunk !== null){
						$info = $this->getChunkInfo($chunk);
						--$info->entity_count;
						if($world->isChunkLoaded($chunkX, $chunkZ)){
							if(($hash = array_key_first($info->queued)) !== null){
								/** @var int $hash */
								unset($info->queued[$hash]);

								World::getBlockXYZ($hash, $x, $y, $z);
								$block = $world->getBlockAt($x, $y, $z);
								if($block instanceof Fallable){
									($ev = new BlockUpdateEvent($block))->call();
									if(!$ev->isCancelled()){
										$block->onNearbyBlockChange();
									}
								}
							}
						}
					}
				}
			}),

			$api->registerEvent(function(BlockUpdateEvent $event) use($world_cache_manager) : void{
				$block = $event->getBlock();
				if($block instanceof Fallable){
					$pos = $block->getPos();
					/** @var int $x */
					$x = $pos->x;
					/** @var int $z */
					$z = $pos->z;
					$chunk = $world_cache_manager->get($pos->getWorld())->getChunk($x >> 4, $z >> 4);
					if($chunk !== null){
						$info = $this->getChunkInfo($chunk);

						/** @var int $y */
						$y = $pos->y;

						if($info->entity_count >= $this->falling_block_max_count){
							$event->cancel();
							$info->queued[World::blockHash($x, $y, $z)] = null;
						}else{
							unset($info->queued[World::blockHash($x, $y, $z)]);
						}
					}
				}
			})
		];
	}

	public function disable(AggressiveOptzAPI $api) : void{
		if(count($this->unregisters) === 0){
			throw new InvalidStateException("Tried to unregister an unregistered event handler");
		}

		foreach($this->unregisters as $unregister){
			$unregister();
		}
		$this->unregisters = [];
	}
}