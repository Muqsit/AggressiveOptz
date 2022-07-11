<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component;

use muqsit\aggressiveoptz\AggressiveOptzApi;

interface OptimizationComponent{

	/**
	 * @param array<string, mixed> $config
	 * @return static
	 */
	public static function fromConfig(array $config);

	public function enable(AggressiveOptzApi $api) : void;

	public function disable(AggressiveOptzApi $api) : void;
}