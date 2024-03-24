<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component\defaults;

use Closure;
use InvalidArgumentException;
use LogicException;
use muqsit\aggressiveoptz\AggressiveOptzApi;
use muqsit\aggressiveoptz\component\OptimizationComponent;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\world\World;
use function array_pop;
use function array_push;
use function count;

class BreakVerticallySupportedBlockOptimizationComponent implements OptimizationComponent{

	public static function fromConfig(array $config) : BreakVerticallySupportedBlockOptimizationComponent{
		$blocks = [];
		foreach($config["blocks"] as $block_string){
			$item = StringToItemParser::getInstance()->parse($block_string) ?? throw new InvalidArgumentException("Invalid block {$block_string}");
			$block = $item->getBlock();
			$block->getTypeId() !== BlockTypeIds::AIR || throw new InvalidArgumentException("Invalid block {$block_string}");
			$blocks[] = $block;
		}
		return new self($blocks);
	}

	/** @var array<int, true> */
	readonly private array $blocks;

	/** @var Closure */
	private array $unregisters = [];

	/**
	 * @param list<Block> $blocks
	 */
	public function __construct(array $blocks){
		$block_ids = [];
		foreach($blocks as $block){
			$block_ids[$block->getTypeId()] = true;
		}
		$this->blocks = $block_ids;
	}

	public function enable(AggressiveOptzApi $api) : void{
		if(count($this->unregisters) > 0){
			throw new LogicException("Tried to register event handlers twice");
		}
		$this->unregisters = [
			$api->registerEvent(function(BlockUpdateEvent $event) : void{
				$block = $event->getBlock();
				$type_id = $block->getTypeId();
				if(!isset($this->blocks[$type_id])){
					return;
				}
				if(!$block->getSide(Facing::DOWN)->canBeReplaced()){
					return;
				}
				$pos = $block->getPosition();

				/** @var list<Block> $unsupported_blocks */
				$unsupported_blocks = [];
				for($y = $pos->y; $y < World::Y_MAX; ++$y){
					$unsupported_block = $pos->world->getBlockAt($pos->x, $y, $pos->z);
					if($unsupported_block->getTypeId() !== $type_id){
						break;
					}
					$unsupported_blocks[] = $unsupported_block;
				}
				if(count($unsupported_blocks) === 0){
					return;
				}
				$event->cancel();

				$air_block = VanillaBlocks::AIR();
				$item = VanillaItems::AIR();
				$drops = [];
				foreach($unsupported_blocks as $unsupported_block){
					array_push($drops, ...$unsupported_block->getDrops($item));
					$pos = $unsupported_block->getPosition();
					$pos->world->setBlockAt($pos->x, $pos->y, $pos->z, $air_block, false);
				}

				$source = $block->getPosition()->add(0.5, 0.5, 0.5);
				foreach($this->compressItems($drops) as $item){
					$pos->world->dropItem($source, $item);
				}
			})
		];
	}

	/**
	 * @param list<Item> $items
	 * @return list<Item>
	 */
	private function compressItems(array $items) : array{
		/** @var array<int, list<Item>> $buckets */
		$buckets = []; // put items of same Item::getStateId() in one bucket to reduce Item::equals() calls
		while(($item = array_pop($items)) !== null){
			$state_id = $item->getStateId();
			if(!isset($buckets[$state_id])){
				$buckets[$state_id][] = $item;
				continue;
			}
			foreach($buckets[$state_id] as $entry){
				if($entry->equals($item)){
					$entry->setCount($entry->getCount() + $item->getCount());
				}
			}
		}
		// flatten buckets
		$result = [];
		foreach($buckets as $bucket){
			array_push($result, ...$bucket);
		}
		return $result;
	}

	public function disable(AggressiveOptzApi $api) : void{
		if(count($this->unregisters) === 0){
			throw new LogicException("Tried to unregister an unregistered event handler");
		}

		foreach($this->unregisters as $unregister){
			$unregister();
		}
		$this->unregisters = [];
	}
}