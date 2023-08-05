<?php

namespace Cryotap\Joker;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use Vecnavium\FormsUI\SimpleForm;

class Main extends PluginBase {

	/** @var Config */
	private $config;

	private $allowProgramming;
	private $allowMiscellaneous;
	private $allowDark;
	private $allowPun;
	private $allowSpooky;
	private $allowChristmas;

	public function onEnable(): void {
		$this->saveDefaultConfig();
		$this->config = $this->getConfig();
		$this->loadConfigValues();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		if ($command->getName() === "joker") {
			if (!$sender instanceof Player) {
				$sender->sendMessage(TF::RED . "This command can only be used in-game.");
				return true;
			}

			if (!$sender->hasPermission("joker.use")) {
				$sender->sendMessage(TF::RED . "You do not have permission to access the settings editor.");
				return true;
			}

			if (isset($args[0]) && $args[0] === "editor") {
				if (!$sender->hasPermission("joker.editor")) {
					$sender->sendMessage(TF::RED . "You do not have permission to access the settings editor.");
					return true;
				}

				$this->sendJokeOptionsForm($sender);
			} else {
				$selectedOptions = $this->getPlayerSettings($sender->getName());
				$this->fetchAndSendJoke($sender, $selectedOptions);
			}

			return true;
		}
		return false;
	}

	private function loadConfigValues(): void {
		$this->reloadConfig();
		$this->allowProgramming = (bool) $this->getConfig()->get("Programming", false);
		$this->allowMiscellaneous = (bool) $this->getConfig()->get("Miscellaneous", false);
		$this->allowDark = (bool) $this->getConfig()->get("Dark", false);
		$this->allowPun = (bool) $this->getConfig()->get("Pun", true);
		$this->allowSpooky = (bool) $this->getConfig()->get("Spooky", false);
		$this->allowChristmas = (bool) $this->getConfig()->get("Christmas", false);
	}

	public function sendJokeOptionsForm(Player $player): void {
		$form = new SimpleForm(function (Player $player, $data) {
			if ($data !== null) {
				$this->toggleConfigSetting($data);
				$this->loadConfigValues();
				$player->sendMessage(TF::GREEN . "Joke settings updated successfully!");
			}
		});

		$form->setTitle(TF::DARK_RED . "Joke Options");
		$form->setContent(TF::RED . "Toggle joke options:");

		$form->addButton(TF::DARK_PURPLE . "Programming", -1, "", "Programming", $this->allowProgramming);
		$form->addButton(TF::DARK_PURPLE . "Miscellaneous", -1, "", "Miscellaneous", $this->allowMiscellaneous);
		$form->addButton(TF::DARK_PURPLE . "Dark", -1, "", "Dark", $this->allowDark);
		$form->addButton(TF::DARK_PURPLE . "Pun", -1, "", "Pun", $this->allowPun);
		$form->addButton(TF::DARK_PURPLE . "Spooky", -1, "", "Spooky", $this->allowSpooky);
		$form->addButton(TF::DARK_PURPLE . "Christmas", -1, "", "Christmas", $this->allowChristmas);

		$form->sendToPlayer($player);
	}

	private function toggleConfigSetting(string $configKey): void {
		$currentValue = $this->getConfig()->get($configKey);
		$newValue = !$currentValue; // Toggle the value
		$this->getConfig()->set($configKey, $newValue);
		$this->saveConfig();
	}

	public function fetchAndSendJoke(Player $player, array $selectedOptions): void {
		$options = [];

		if ($this->allowProgramming) {
			$options[] = "Programming";
		}
		if ($this->allowMiscellaneous) {
			$options[] = "Miscellaneous";
		}
		if ($this->allowDark) {
			$options[] = "Dark";
		}
		if ($this->allowPun) {
			$options[] = "Pun";
		}
		if ($this->allowSpooky) {
			$options[] = "Spooky";
		}
		if ($this->allowChristmas) {
			$options[] = "Christmas";
		}

		$enabledJokes = implode(",", $options);
		$url = "https://v2.jokeapi.dev/joke/{$enabledJokes}?blacklistFlags=nsfw,religious,political,racist,sexist,explicit&format=txt";

		$this->getServer()->getAsyncPool()->submitTask(new FetchJokeTask($player->getName(), $url));
	}

	public function getPlayerSettings(string $playerName): array{
		$settings = [];
		$options = ["Programming", "Miscellaneous", "Dark", "Pun", "Spooky", "Christmas"];

		foreach ($options as $index => $option) {
			if ($this->config->get($option, false)) {
				$settings[] = $index;
			}
		}

		return $settings;
	}
}
