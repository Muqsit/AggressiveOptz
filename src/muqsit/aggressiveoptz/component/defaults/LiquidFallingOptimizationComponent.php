<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component\defaults;

use Closure;
use LogicException;
use muqsit\aggressiveoptz\AggressiveOptzApi;
use muqsit\aggressiveoptz\component\OptimizationComponent;
use pocketmine\block\Liquid;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\math\Vector3;
use pocketmine\world\format\Chunk;
use function array_key_exists;

class LiquidFallingOptimizationComponent implements OptimizationComponent{

	public static function fromConfig(array $config) : LiquidFallingOptimizationComponent{
		return new self();
	}

	private ?Closure $unregister = null;

	public function __construct(){
	}

	public function enable(AggressiveOptzApi $api) : void{
		if($this->unregister !== null){
			throw new LogicException("Tried to register event handler twice");
		}

		$liquids = [];
		foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $block){
			if($block instanceof Liquid && $block->isFalling() && $block->getDecay() === 0){
				$liquids[$block->getStateId()] = true;
			}
		}

		$air_id = VanillaBlocks::AIR()->getStateId();
		$this->unregister = $api->registerEvent(function(BlockSpreadEvent $event) use($liquids, $air_id) : void{
			$new_state = $event->getNewState();
			if(!array_key_exists($new_state->getStateId(), $liquids)){
				return;
			}

			$pos = $new_state->getPosition();
			$world = $pos->getWorld();

			/** @var int $x */
			$x = $pos->x;
			/** @var int $y */
			$y = $pos->y;
			/** @var int $z */
			$z = $pos->z;

			$chunk = $world->getChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
			if($chunk === null){
				return;
			}

			$xc = $x & Chunk::COORD_MASK;
			$zc = $z & Chunk::COORD_MASK;
			$last_y = null;
			while(--$y >= 0){
				if($chunk->getBlockStateId($xc, $y, $zc) !== $air_id){
					break;
				}
				$world->setBlockAt($x, $y, $z, $new_state, false);
				$last_y = $y;
			}

			if($last_y !== null){
				$source = $event->getSource();
				if($source instanceof Liquid){
					$world->scheduleDelayedBlockUpdate(new Vector3($x, $last_y, $z), max(1, $source->tickRate()));
				}
			}
		});
	}

	public function disable(AggressiveOptzApi $api) : void{
		if($this->unregister === null){
			throw new LogicException("Tried to unregister an unregistered event handler");
		}

		($this->unregister)();
		$this->unregister = null;
	}
}