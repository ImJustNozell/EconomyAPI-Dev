<?php


namespace onebone\economyapi\provider;


use onebone\economyapi\EconomyAPI;
use onebone\economyapi\task\MySQLPingTask;
use pocketmine\player\Player;


class MySQLProvider implements Provider
{
	private $db;

	private $plugin;

	public function __construct(EconomyAPI $plugin)
	{
		$this->plugin = $plugin;
	}

	public function open()
	{
		$config = $this->plugin->getConfig()->get("provider-settings", []);

		$this->db = new \mysqli(
			$config["host"] ?? "127.0.0.1",
			$config["user"] ?? "onebone",
			$config["password"] ?? "hello_world",
			$config["db"] ?? "economyapi",
			$config["port"] ?? 3306
		);
		if ($this->db->connect_error) {
			$this->plugin->getLogger()->critical("Could not connect to MySQL server: " . $this->db->connect_error);
			return;
		}
		if (!$this->db->query("CREATE TABLE IF NOT EXISTS user_money(
			username VARCHAR(20) PRIMARY KEY,
			money FLOAT
		);")) {
			$this->plugin->getLogger()->critical("Error creating table: " . $this->db->error);
			return;
		}

		$this->plugin->getScheduler()->scheduleRepeatingTask(new MySQLPingTask($this->plugin, $this->db), 600);
	}

	public function accountExists($player)
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);

		$result = $this->db->query("SELECT * FROM user_money WHERE username='" . $this->db->real_escape_string($player) . "'");
		return $result->num_rows > 0 ? true : false;
	}

	public function createAccount($player, $defaultMoney = 1000.0)
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);

		if (!$this->accountExists($player)) {
			$this->db->query("INSERT INTO user_money (username, money) VALUES ('" . $this->db->real_escape_string($player) . "', $defaultMoney);");
			return true;
		}
		return false;
	}

	public function removeAccount($player)
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);

		if ($this->db->query("DELETE FROM user_money WHERE username='" . $this->db->real_escape_string($player) . "'") === true) return true;
		return false;
	}

	public function getMoney($player)
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);

		$res = $this->db->query("SELECT money FROM user_money WHERE username='" . $this->db->real_escape_string($player) . "'");
		$ret = $res->fetch_array()[0] ?? false;
		$res->free();
		return $ret;
	}

	public function setMoney($player, $amount)
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);

		$amount = (float) $amount;

		return $this->db->query("UPDATE user_money SET money = $amount WHERE username='" . $this->db->real_escape_string($player) . "'");
	}

	public function addMoney($player, $amount)
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);

		$amount = (float) $amount;

		return $this->db->query("UPDATE user_money SET money = money + $amount WHERE username='" . $this->db->real_escape_string($player) . "'");
	}

	public function reduceMoney($player, $amount)
	{
		if ($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);

		$amount = (float) $amount;

		return $this->db->query("UPDATE user_money SET money = money - $amount WHERE username='" . $this->db->real_escape_string($player) . "'");
	}

	public function getAll()
	{
		$res = $this->db->query("SELECT * FROM user_money");

		$ret = [];
		foreach ($res->fetch_all() as $val) {
			$ret[$val[0]] = $val[1];
		}

		$res->free();

		return $ret;
	}

	public function getName()
	{
		return "MySQL";
	}

	public function save() {}

	public function close()
	{
		if ($this->db instanceof \mysqli) {
			$this->db->close();
		}
	}
}
