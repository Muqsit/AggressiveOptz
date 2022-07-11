<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz;

use Closure;
use InvalidArgumentException;
use Logger;
use muqsit\aggressiveoptz\component\defaults\FallingBlockOptimizationComponent;
use muqsit\aggressiveoptz\component\defaults\LiquidFallingOptimizationComponent;
use muqsit\aggressiveoptz\component\OptimizationComponentFactory;
use muqsit\aggressiveoptz\component\OptimizationComponentManager;
use muqsit\aggressiveoptz\helper\AggressiveOptzHelper;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerListManager;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;

final class AggressiveOptzAPI{

	private Loader $loader;
	private AggressiveOptzHelper $helper;
	private OptimizationComponentFactory $component_factory;
	private OptimizationComponentManager $component_manager;

	public function __construct(Loader $loader){
		$this->loader = $loader;
		$this->helper = new AggressiveOptzHelper();
	}

	public function load() : void{
		$this->loadComponent();
	}

	private function loadComponent() : void{
		$prefix = strtolower($this->loader->getName());

		$this->component_factory = new OptimizationComponentFactory();
		$this->component_factory->register("{$prefix}:falling_block", FallingBlockOptimizationComponent::class);
		$this->component_factory->register("{$prefix}:liquid_falling", LiquidFallingOptimizationComponent::class);

		$this->component_manager = new OptimizationComponentManager($this);
	}

	public function init() : void{
		$this->helper->init($this);
	}

	public function getHelper() : AggressiveOptzHelper{
		return $this->helper;
	}

	public function getServer() : Server{
		return $this->loader->getServer();
	}

	public function getScheduler() : TaskScheduler{
		return $this->loader->getScheduler();
	}

	public function getLogger() : Logger{
		return $this->loader->getLogger();
	}

	public function getComponentFactory() : OptimizationComponentFactory{
		return $this->component_factory;
	}

	public function getComponentManager() : OptimizationComponentManager{
		return $this->component_manager;
	}

	/**
	 * Registers an event handler and returns a closure which unregisters
	 * the handler.
	 *
	 * @template TEvent of \pocketmine\event\Event
	 * @param Closure(TEvent) : void $event_handler
	 * @param int $priority
	 * @param bool $handleCancelled
	 * @return Closure() : void
	 */
	public function registerEvent(Closure $event_handler, int $priority = EventPriority::NORMAL, bool $handleCancelled = false) : Closure{
		try{
			$event_class_instance = (new ReflectionFunction($event_handler))->getParameters()[0]->getType();
			if($event_class_instance === null || !($event_class_instance instanceof ReflectionNamedType)){
				throw new InvalidArgumentException("Invalid parameter #1 supplied to event handler");
			}

			/** @var class-string<TEvent> $event_class */
			$event_class = $event_class_instance->getName();

			$this->getServer()->getPluginManager()->registerEvent($event_class, $event_handler, $priority, $this->loader, $handleCancelled);

			$listener = null;
			foreach(HandlerListManager::global()->getListFor($event_class)->getListenersByPriority($priority) as $entry){
				if($entry->getHandler() === $event_handler){
					$listener = $entry;
					break;
				}
			}
			assert($listener !== null);

			return static function() use($event_class, $listener) : void{
				HandlerListManager::global()->getListFor($event_class)->unregister($listener);
			};
		}catch(ReflectionException $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}
}