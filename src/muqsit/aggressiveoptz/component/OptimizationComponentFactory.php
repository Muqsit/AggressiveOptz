<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component;

use InvalidArgumentException;
use function array_key_exists;

final class OptimizationComponentFactory{

	/** @var array<string, class-string<OptimizationComponent>> */
	private array $registered = [];

	public function exists(string $identifier) : bool{
		return array_key_exists($identifier, $this->registered);
	}

	/**
	 * @param string $identifier
	 * @param class-string<OptimizationComponent> $component
	 */
	public function register(string $identifier, string $component) : void{
		if($this->exists(($identifier))){
			throw new InvalidArgumentException("Tried to override an already existing component with the identifier \"{$identifier}\" ({$this->registered[$identifier]})");
		}

		$this->registered[$identifier] = $component;
	}

	/**
	 * @param string $identifier
	 * @param array<string, mixed> $config
	 * @return OptimizationComponent
	 */
	public function build(string $identifier, array $config) : OptimizationComponent{
		return $this->registered[$identifier]::fromConfig($config);
	}
}