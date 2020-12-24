<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component;

use InvalidStateException;
use Logger;
use muqsit\aggressiveoptz\AggressiveOptzAPI;
use PrefixedLogger;

final class OptimizationComponentManager{

	/** @var AggressiveOptzAPI */
	private $api;

	/** @var Logger */
	private $logger;

	/** @var OptimizationComponent[] */
	private $enabled = [];

	public function __construct(AggressiveOptzAPI $api){
		$this->api = $api;
		$this->logger = new PrefixedLogger($api->getLogger(), "OC-Manager");
	}

	public function getLogger() : Logger{
		return $this->logger;
	}

	public function isEnabled(string $identifier) : bool{
		return isset($this->enabled[$identifier]);
	}

	/**
	 * @param string $identifier
	 * @param array<string, mixed> $config
	 */
	public function enable(string $identifier, array $config) : void{
		if($this->isEnabled($identifier)){
			throw new InvalidStateException("Tried to enable an already enabled component: {$identifier}");
		}

		$this->enabled[$identifier] = $this->api->getComponentFactory()->build($identifier, $config);
		$this->enabled[$identifier]->enable($this->api);
		$this->logger->debug("Enabled component: {$identifier}");
	}

	public function disable(string $identifier) : void{
		if(!$this->isEnabled($identifier)){
			throw new InvalidStateException("Tried to disable an already disabled component: {$identifier}");
		}

		$this->enabled[$identifier]->disable($this->api);
		unset($this->enabled[$identifier]);
		$this->logger->debug("Disabled component: {$identifier}");
	}
}