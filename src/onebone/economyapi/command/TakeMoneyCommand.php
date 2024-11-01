<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;

class TakeMoneyCommand extends Command
{

	public function __construct()
	{
		$desc = EconomyAPI::getInstance()->getCommandMessage("takemoney");
		parent::__construct("takemoney", $desc["description"], $desc["usage"]);

		$this->setPermission("economyapi.command.takemoney");

	}

	public function execute(CommandSender $sender, string $label, array $params): bool
	{
		if (!EconomyAPI::getInstance()->isEnabled()) return false;
		if (!$this->testPermission($sender)) {
			return false;
		}

		$player = array_shift($params);
		$amount = array_shift($params);

		if (!is_numeric($amount)) {
			$sender->sendMessage(TextFormat::RED . "Usage: " . $this->getUsage());
			return true;
		}

		if (($p = EconomyAPI::getInstance()->getServer()->getPlayerByPrefix($player)) instanceof Player) {
			$player = $p->getName();
		}

		if ($amount < 0) {
			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("takemoney-invalid-number", [$amount], $sender->getName()));
			return true;
		}

		$result = EconomyAPI::getInstance()->reduceMoney($player, $amount);
		switch ($result) {
			case EconomyAPI::RET_INVALID:
				$sender->sendMessage(EconomyAPI::getInstance()->getMessage("takemoney-player-lack-of-money", [$player, $amount, EconomyAPI::getInstance()->myMoney($player)], $sender->getName()));
				break;
			case EconomyAPI::RET_SUCCESS:
				$sender->sendMessage(EconomyAPI::getInstance()->getMessage("takemoney-took-money", [$player, $amount], $sender->getName()));

				if ($p instanceof Player) {
					$p->sendMessage(EconomyAPI::getInstance()->getMessage("takemoney-money-taken", [$amount], $sender->getName()));
				}
				break;
			case EconomyAPI::RET_CANCELLED:
				$sender->sendMessage(EconomyAPI::getInstance()->getMessage("takemoney-failed", [], $sender->getName()));
				break;
			case EconomyAPI::RET_NO_ACCOUNT:
				$sender->sendMessage(EconomyAPI::getInstance()->getMessage("player-never-connected", [$player], $sender->getName()));
				break;
		}

		return true;
	}
}
