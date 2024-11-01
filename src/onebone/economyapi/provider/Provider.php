<?php


namespace onebone\economyapi\provider;

use onebone\economyapi\EconomyAPI;

interface Provider
{
	public function __construct(EconomyAPI $plugin);

	public function open();

	public function accountExists($player);

	public function createAccount($player, $defaultMoney = 1000);

	public function removeAccount($player);

	public function getMoney($player);

	public function setMoney($player, $amount);

	public function addMoney($player, $amount);

	public function reduceMoney($player, $amount);

	public function getAll();

	public function getName();

	public function save();
	public function close();
}
