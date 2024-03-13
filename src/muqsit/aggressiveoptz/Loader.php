<?php

declare(strict_types=1);

namespace muqsit\aggressiveoptz;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Filesystem;

final class Loader extends PluginBase{

	private const COMPONENTS_CONFIG_FILE = "components.json";

	private AggressiveOptzApi $api;

	protected function onLoad() : void{
		$this->saveResource(self::COMPONENTS_CONFIG_FILE);
		$this->api = new AggressiveOptzApi($this);
	}

	protected function onEnable() : void{
		$this->api->load();

		$contents = Filesystem::fileGetContents($this->getDataFolder() . self::COMPONENTS_CONFIG_FILE);
		$this->loadComponentsFromConfig(json_decode($contents, true, 512, JSON_THROW_ON_ERROR));

		$this->api->init();
	}

	protected function onDisable() : void{
	}

	public function getApi() : AggressiveOptzApi{
		return $this->api;
	}

	/**
	 * @param array<string, array{enabled: bool, configuration: array<string, mixed>}> $config
	 */
	public function loadComponentsFromConfig(array $config) : void{
		$component_manager = $this->api->getComponentManager();
		foreach($config as $identifier => $data){
			if(!$this->api->getComponentFactory()->exists($identifier)){
				$this->getLogger()->warning("Component {$identifier} is not registered. This configuration entry will be skipped.");
				continue;
			}
			if($data["enabled"]){
				$component_manager->enable($identifier, $data["configuration"]);
			}
		}
	}
}