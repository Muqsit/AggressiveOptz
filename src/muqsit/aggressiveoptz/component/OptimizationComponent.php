<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component;

use muqsit\aggressiveoptz\AggressiveOptzAPI;

interface OptimizationComponent{

	/**
	 * @param array<string, mixed> $config
	 * @return static
	 */
	public static function fromConfig(array $config);

	public function enable(AggressiveOptzAPI $api) : void;

	public function disable(AggressiveOptzAPI $api) : void;
}