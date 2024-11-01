<?php


namespace onebone\economyapi;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use onebone\economyapi\provider\Provider;
use onebone\economyapi\provider\YamlProvider;
use onebone\economyapi\provider\MySQLProvider;
use onebone\economyapi\event\money\SetMoneyEvent;
use onebone\economyapi\event\money\ReduceMoneyEvent;
use onebone\economyapi\event\money\AddMoneyEvent;
use onebone\economyapi\event\money\MoneyChangedEvent;
use onebone\economyapi\event\account\CreateAccountEvent;
use onebone\economyapi\task\SaveTask;
use pocketmine\utils\SingletonTrait;

class EconomyAPI extends PluginBase implements Listener
{
	use SingletonTrait;

	const API_VERSION = 4;
	const PACKAGE_VERSION = "5.7";

	const RET_NO_ACCOUNT = -3;
	const RET_CANCELLED = -2;
	const RET_NOT_FOUND = -1;
	const RET_INVALID = 0;
	const RET_SUCCESS = 1;


	private Provider $provider;

	private array $langList = [
		"def" => "Default",
		"user-define" => "User Defined",
		"ch" => "简体中文",
		"cs" => "Čeština",
		"en" => "English",
		"fr" => "Français",
		"id" => "Bahasa Indonesia",
		"it" => "Italiano",
		"ja" => "日本語",
		"ko" => "한국어",
		"nl" => "Nederlands",
		"ru" => "Русский",
		"uk" => "Українська",
		"zh" => "繁體中文",
	];
	private array $lang = [], $playerLang = [];

	public function getCommandMessage(string $command, $lang = false): array
	{
		if ($lang === false) {
			$lang = $this->getConfig()->get("default-lang");
		}
		$command = strtolower($command);
		if (isset($this->lang[$lang]["commands"][$command])) {
			return $this->lang[$lang]["commands"][$command];
		} else {
			return $this->lang["def"]["commands"][$command];
		}
	}

	public function getMessage(string $key, array $params = [], string $player = "console"): string
	{
		$player = strtolower($player);
		if (isset($this->lang[$this->playerLang[$player]][$key])) {
			return $this->replaceParameters($this->lang[$this->playerLang[$player]][$key], $params);
		} elseif (isset($this->lang["def"][$key])) {
			return $this->replaceParameters($this->lang["def"][$key], $params);
		}
		return "Language matching key \"$key\" does not exist.";
	}

	public function setPlayerLanguage(string $player, string $language): bool
	{
		$player = strtolower($player);
		$language = strtolower($language);
		if (isset($this->lang[$language])) {
			$this->playerLang[$player] = $language;
			return true;
		}
		return false;
	}

	public function getMonetaryUnit(): string
	{
		return $this->getConfig()->get("monetary-unit");
	}

	public function getAllMoney(): array
	{
		return $this->provider->getAll();
	}

	public function createAccount($player, $defaultMoney = false, bool $force = false): bool
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);

		if (!$this->provider->accountExists($player)) {
			$defaultMoney = ($defaultMoney === false) ? $this->getConfig()->get("default-money") : $defaultMoney;
			$ev = $ev = new CreateAccountEvent($this, $player, $defaultMoney, "none");
			$ev->call();
			if (!$ev->isCancelled() or $force === true) {
				$this->provider->createAccount($player, $ev->getDefaultMoney());
			}
		}
		return false;
	}

	public function accountExists($player): bool
	{
		return $this->provider->accountExists($player);
	}

	public function myMoney($player)
	{
		return $this->provider->getMoney($player);
	}

	public function setMoney($player, $amount, bool $force = false, string $issuer = "none"): int
	{
		if ($amount < 0) {
			return self::RET_INVALID;
		}

		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);
		if ($this->provider->accountExists($player)) {
			$amount = round($amount, 2);
			if ($amount > $this->getConfig()->get("max-money")) {
				return self::RET_INVALID;
			}

			$ev = new SetMoneyEvent($this, $player, $amount, $issuer);
			$ev->call();
			if (!$ev->isCancelled() or $force === true) {
				$this->provider->setMoney($player, $amount);
				$ev2 = new MoneyChangedEvent($this, $player, $amount, $issuer);
				$ev2->call();
				return self::RET_SUCCESS;
			}
			return self::RET_CANCELLED;
		}
		return self::RET_NO_ACCOUNT;
	}

	public function addMoney($player, $amount, bool $force = false, $issuer = "none"): int
	{
		if ($amount < 0) {
			return self::RET_INVALID;
		}
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);
		if (($money = $this->provider->getMoney($player)) !== false) {
			$amount = round($amount, 2);
			if ($money + $amount > $this->getConfig()->get("max-money")) {
				return self::RET_INVALID;
			}

			$ev = new AddMoneyEvent($this, $player, $amount, $issuer);
			$ev->call();
			if (!$ev->isCancelled() or $force === true) {
				$this->provider->addMoney($player, $amount);
				$ev2 = new MoneyChangedEvent($this, $player, $amount + $money, $issuer);
				$ev2->call();
				return self::RET_SUCCESS;
			}
			return self::RET_CANCELLED;
		}
		return self::RET_NO_ACCOUNT;
	}

	public function reduceMoney($player, $amount, bool $force = false, $issuer = "none"): int
	{
		if ($amount < 0) {
			return self::RET_INVALID;
		}
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);
		if (($money = $this->provider->getMoney($player)) !== false) {
			$amount = round($amount, 2);
			if ($money - $amount < 0) {
				return self::RET_INVALID;
			}

			$ev = new ReduceMoneyEvent($this, $player, $amount, $issuer);
			$ev->call();
			if (!$ev->isCancelled() or $force === true) {
				$this->provider->reduceMoney($player, $amount);
				$ev2 = new MoneyChangedEvent($this, $player, $money - $amount, $issuer);
				$ev2->call();
				return self::RET_SUCCESS;
			}
			return self::RET_CANCELLED;
		}
		return self::RET_NO_ACCOUNT;
	}

	public function onEnable(): void
	{
		self::setInstance($this);
		$this->saveDefaultConfig();

		if (!is_file($this->getDataFolder() . "PlayerLang.dat")) {
			file_put_contents($this->getDataFolder() . "PlayerLang.dat", serialize([]));
		}
		$this->playerLang = unserialize(file_get_contents($this->getDataFolder() . "PlayerLang.dat"));

		if (!isset($this->playerLang["console"])) {
			$this->playerLang["console"] = $this->getConfig()->get("default-lang");
		}
		if (!isset($this->playerLang["rcon"])) {
			$this->playerLang["rcon"] = $this->getConfig()->get("default-lang");
		}
		$this->initialize();

		if ($this->getConfig()->get("auto-save-interval") > 0) {
			$this->getScheduler()->scheduleDelayedRepeatingTask(new SaveTask($this), $this->getConfig()->get("auto-save-interval") * 1200, $this->getConfig()->get("auto-save-interval") * 1200);
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onJoin(PlayerJoinEvent $event)
	{
		$player = $event->getPlayer();

		if (!isset($this->playerLang[strtolower($player->getName())])) {
			$this->playerLang[strtolower($player->getName())] = $this->getConfig()->get("default-lang");
		}
		if (!$this->provider->accountExists($player)) {
			$this->getLogger()->debug("Account of '" . $player->getName() . "' is not found. Creating account...");
			$this->createAccount($player, false, true);
		}
	}

	public function onDisable(): void
	{
		$this->saveAll();

		if ($this->provider instanceof Provider) {
			$this->provider->close();
		}
	}

	public function saveAll()
	{
		if ($this->provider instanceof Provider) {
			$this->provider->save();
		}
		file_put_contents($this->getDataFolder() . "PlayerLang.dat", serialize($this->playerLang));
	}

	private function replaceParameters($message, $params = [])
	{
		$search = ["%MONETARY_UNIT%"];
		$replace = [$this->getMonetaryUnit()];

		for ($i = 0; $i < count($params); $i++) {
			$search[] = "%" . ($i + 1);
			$replace[] = $params[$i];
		}

		$colors = [
			"0",
			"1",
			"2",
			"3",
			"4",
			"5",
			"6",
			"7",
			"8",
			"9",
			"a",
			"b",
			"c",
			"d",
			"e",
			"f",
			"k",
			"l",
			"m",
			"n",
			"o",
			"r"
		];
		foreach ($colors as $code) {
			$search[] = "&" . $code;
			$replace[] = TextFormat::ESCAPE . $code;
		}

		return str_replace($search, $replace, $message);
	}

	private function initialize()
	{
		switch (strtolower($this->getConfig()->get("provider"))) {
			case "yaml":
				$this->provider = new YamlProvider($this);
				break;
			case "mysql":
				$this->provider = new MySQLProvider($this);
				break;
			default:
				$this->getLogger()->critical("Invalid database was given.");
				return false;
		}
		$this->provider->open();

		$this->initializeLanguage();
		$this->getLogger()->notice("Database provider was set to: " . $this->provider->getName());
		$this->registerCommands();
	}

	public function openProvider()
	{
		if ($this->provider !== null)
			$this->provider->open();
	}


	private function registerCommands()
	{
		$map = $this->getServer()->getCommandMap();

		$commands = [
			"mymoney" => "\\onebone\\economyapi\\command\\MyMoneyCommand",
			"topmoney" => "\\onebone\\economyapi\\command\\TopMoneyCommand",
			"setmoney" => "\\onebone\\economyapi\\command\\SetMoneyCommand",
			"seemoney" => "\\onebone\\economyapi\\command\\SeeMoneyCommand",
			"givemoney" => "\\onebone\\economyapi\\command\\GiveMoneyCommand",
			"takemoney" => "\\onebone\\economyapi\\command\\TakeMoneyCommand",
			"pay" => "\\onebone\\economyapi\\command\\PayCommand",
			"setlang" => "\\onebone\\economyapi\\command\\SetLangCommand",
			"mystatus" => "\\onebone\\economyapi\\command\\MyStatusCommand"
		];
		foreach ($commands as $cmd => $class) {
			$map->register("economyapi", new $class($this));
		}
	}

	private function initializeLanguage()
	{
		foreach ($this->getResources() as $resource) {
			if ($resource->isFile() and substr(($filename = $resource->getFilename()), 0, 5) === "lang_") {
				$this->lang[substr($filename, 5, -5)] = json_decode(file_get_contents($resource->getPathname()), true);
			}
		}
		$this->lang["user-define"] = (new Config($this->getDataFolder() . "messages.yml", Config::YAML, $this->lang["def"]))->getAll();
	}
}
