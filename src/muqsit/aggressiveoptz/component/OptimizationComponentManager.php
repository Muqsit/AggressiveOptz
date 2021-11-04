<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component;

use InvalidArgumentException;
use Logger;
use muqsit\aggressiveoptz\AggressiveOptzAPI;
use PrefixedLogger;
use function array_key_exists;

final class OptimizationComponentManager{

	private AggressiveOptzAPI $api;
	private Logger $logger;

	/** @var OptimizationComponent[] */
	private array $enabled = [];

	public function __construct(AggressiveOptzAPI $api){
		$this->api = $api;
		$this->logger = new PrefixedLogger($api->getLogger(), "OC-Manager");
	}

	public function getLogger() : Logger{
		return $this->logger;
	}

	public function isEnabled(string $identifier) : bool{
		return array_key_exists($identifier, $this->enabled);
	}

	/**
	 * @param string $identifier
	 * @param array<string, mixed> $config
	 */
	public function enable(string $identifier, array $config) : void{
		if($this->isEnabled($identifier)){
			throw new InvalidArgumentException("Tried to enable an already enabled component: {$identifier}");
		}

		$this->enabled[$identifier] = $this->api->getComponentFactory()->build($identifier, $config);
		$this->enabled[$identifier]->enable($this->api);
		$this->logger->debug("Enabled component: {$identifier}");
	}

	public function disable(string $identifier) : void{
		if(!$this->isEnabled($identifier)){
			throw new InvalidArgumentException("Tried to disable an already disabled component: {$identifier}");
		}

		$this->enabled[$identifier]->disable($this->api);
		unset($this->enabled[$identifier]);
		$this->logger->debug("Disabled component: {$identifier}");
	}
}