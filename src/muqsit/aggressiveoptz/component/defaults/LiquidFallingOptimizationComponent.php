<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component\defaults;

use Closure;
use LogicException;
use muqsit\aggressiveoptz\AggressiveOptzAPI;
use muqsit\aggressiveoptz\component\OptimizationComponent;
use pocketmine\block\BlockFactory as VanillaBlockFactory;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\math\Vector3;
use ReflectionProperty;
use function array_key_exists;

class LiquidFallingOptimizationComponent implements OptimizationComponent{

	public static function fromConfig(array $config) : LiquidFallingOptimizationComponent{
		return new self();
	}

	private ?Closure $unregister = null;

	public function __construct(){
	}

	public function enable(AggressiveOptzAPI $api) : void{
		if($this->unregister !== null){
			throw new LogicException("Tried to register event handler twice");
		}

		$_falling = new ReflectionProperty(Liquid::class, "falling");
		$_falling->setAccessible(true);

		$_decay = new ReflectionProperty(Liquid::class, "decay");
		$_decay->setAccessible(true);

		$liquids = [];
		foreach(VanillaBlockFactory::getInstance()->getAllKnownStates() as $block){
			if($block instanceof Liquid && $_falling->getValue($block) && $_decay->getValue($block) === 0){
				$liquids[$block->getFullId()] = true;
			}
		}

		$air_id = VanillaBlocks::AIR()->getFullId();
		$this->unregister = $api->registerEvent(function(BlockSpreadEvent $event) use($liquids, $air_id) : void{
			$new_state = $event->getNewState();
			if(array_key_exists($new_state->getFullId(), $liquids)){
				$pos = $new_state->getPosition();
				$world = $pos->getWorld();

				/** @var int $x */
				$x = $pos->x;
				/** @var int $y */
				$y = $pos->y;
				/** @var int $z */
				$z = $pos->z;

				$chunk = $world->getChunk($x >> 4, $z >> 4);
				if($chunk !== null){
					$xc = $x & 0x0f;
					$zc = $z & 0x0f;
					$last_y = null;
					while(--$y >= 0){
						if($chunk->getFullBlock($xc, $y, $zc) !== $air_id){
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
				}
			}
		});
	}

	public function disable(AggressiveOptzAPI $api) : void{
		if($this->unregister === null){
			throw new LogicException("Tried to unregister an unregistered event handler");
		}

		($this->unregister)();
		$this->unregister = null;
	}
}