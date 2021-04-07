<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component\defaults\utils;

final class FallingBlockChunkInfo{

	public int $entity_count = 0;

	/**
	 * @var null[]
	 *
	 * @phpstan-var array<int, null>
	 */
	public array $queued = [];

	public function __construct(){
	}
}