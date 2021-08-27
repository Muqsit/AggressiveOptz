<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz;

use InvalidStateException;
use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase{

	private const COMPONENTS_CONFIG_FILE = "components.json";

	private AggressiveOptzAPI $api;

	protected function onLoad() : void{
		$this->saveResource(self::COMPONENTS_CONFIG_FILE);
		$this->api = new AggressiveOptzAPI($this);
	}

	protected function onEnable() : void{
		$this->api->load();

		$contents = file_get_contents($this->getDataFolder() . self::COMPONENTS_CONFIG_FILE);
		if($contents === false){
			throw new InvalidStateException("Failed to load default configuration file: " . self::COMPONENTS_CONFIG_FILE);
		}
		$this->loadComponentsFromConfig(json_decode($contents, true, 512, JSON_THROW_ON_ERROR));

		$this->api->init();
	}

	protected function onDisable() : void{
	}

	public function getApi() : AggressiveOptzAPI{
		return $this->api;
	}

	/**
	 * @param array<string, mixed> $config
	 *
	 * @phpstan-param array<string, array{enabled: bool, configuration: array<string, mixed>}> $config
	 */
	public function loadComponentsFromConfig(array $config) : void{
		$component_manager = $this->api->getComponentManager();
		foreach($config as $identifier => $data){
			if($data["enabled"]){
				$component_manager->enable($identifier, $data["configuration"]);
			}
		}
	}
}