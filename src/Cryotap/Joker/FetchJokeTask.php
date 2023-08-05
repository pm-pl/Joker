<?php

namespace Cryotap\Joker;

use pocketmine\player\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class FetchJokeTask extends AsyncTask {

	private $playerName;
	private $joke;

	public function __construct(string $playerName, string $url) {
		$this->playerName = $playerName;
		$this->url = $url;
	}

	public function onRun(): void {
		$response = @file_get_contents($this->url);
		if ($response !== false) {
			$this->joke = trim($response);
		}
	}

	public function onCompletion(): void {
		$server = Server::getInstance();
		$player = $server->getPlayerExact($this->playerName);
		$joke = $this->joke;

		if ($player instanceof Player) {
			if (!empty($joke)) {
				$player->sendMessage(TF::GREEN . $joke);
			} else {
				$player->sendMessage(TF::RED . "Failed to fetch a joke. Please try again later.");
			}
		}
	}
}
