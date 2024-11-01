<?php

namespace onebone\economyapi\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;
use onebone\economyapi\event\money\PayMoneyEvent;

class PayCommand extends Command
{

	public function __construct()
	{
		$desc = EconomyAPI::getInstance()->getCommandMessage("pay");
		parent::__construct("pay", $desc["description"], $desc["usage"]);

		$this->setPermission("economyapi.command.pay");

	}

	public function execute(CommandSender $sender, string $label, array $params): bool
	{
		if (!EconomyAPI::getInstance()->isEnabled()) return false;
		if (!$this->testPermission($sender)) {
			return false;
		}

		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game.");
			return true;
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

		if (!$p instanceof Player and EconomyAPI::getInstance()->getConfig()->get("allow-pay-offline", true) === false) {
			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("player-not-connected", [$player], $sender->getName()));
			return true;
		}

		if (!EconomyAPI::getInstance()->accountExists($player)) {
			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("player-never-connected", [$player], $sender->getName()));
			return true;
		}

		$ev = new PayMoneyEvent(EconomyAPI::getInstance(), $sender->getName(), $player, $amount);
		$ev->call();

		$result = EconomyAPI::RET_CANCELLED;
		if (!$ev->isCancelled()) {
			$result = EconomyAPI::getInstance()->reduceMoney($sender, $amount);
		}

		if ($result === EconomyAPI::RET_SUCCESS) {
			EconomyAPI::getInstance()->addMoney($player, $amount, true);

			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("pay-success", [$amount, $player], $sender->getName()));
			if ($p instanceof Player) {
				$p->sendMessage(EconomyAPI::getInstance()->getMessage("money-paid", [$sender->getName(), $amount], $sender->getName()));
			}
		} else {
			$sender->sendMessage(EconomyAPI::getInstance()->getMessage("pay-failed", [$player, $amount], $sender->getName()));
		}
		return true;
	}
}
