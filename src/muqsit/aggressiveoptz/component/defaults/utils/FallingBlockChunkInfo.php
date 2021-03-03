<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz\component\defaults\utils;

final class FallingBlockChunkInfo{

	/** @var int */
	public $entity_count = 0;

	/**
	 * @var null[]
	 *
	 * @phpstan-var array<int, null>
	 */
	public $queued = [];

	public function __construct(){
	}
}