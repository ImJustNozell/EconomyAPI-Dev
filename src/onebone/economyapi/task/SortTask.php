<?php

namespace onebone\economyapi\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\player\Player;
use onebone\economyapi\EconomyAPI;

class SortTask extends AsyncTask
{
    private string $sender;
    private string $serializedMoneyData;
    private bool $addOp;
    private int $page;
    private string $serializedOps;
    private string $serializedBanList;

    private int $max = 0;
    private string $topList;

    public function __construct(string $sender, array $moneyData, bool $addOp, int $page, array $ops, array $banList)
    {
        $this->sender = $sender;
        $this->serializedMoneyData = serialize($moneyData);
        $this->addOp = $addOp;
        $this->page = $page;
        $this->serializedOps = serialize($ops);
        $this->serializedBanList = serialize($banList);
    }

    public function onRun(): void
    {
        $this->topList = serialize($this->getTopList());
    }

    private function getTopList(): array
    {
        $money = unserialize($this->serializedMoneyData);
        $banList = unserialize($this->serializedBanList);
        $ops = unserialize($this->serializedOps);

        arsort($money);

        $ret = [];
        $n = 1;
        $this->max = (int) ceil((count($money) - count($banList) - ($this->addOp ? 0 : count($ops))) / 5);
        $this->page = min($this->max, max(1, $this->page));

        foreach ($money as $p => $m) {
            $p = strtolower($p);
            if (isset($banList[$p])) continue;
            if (isset($ops[$p]) && !$this->addOp) continue;
            $current = (int) ceil($n / 5);
            if ($current === $this->page) {
                $ret[$n] = [$p, $m];
            } elseif ($current > $this->page) {
                break;
            }
            ++$n;
        }
        return $ret;
    }

    public function onCompletion(): void
    {
        if ($this->sender === "CONSOLE" || ($player = Server::getInstance()->getPlayerExact($this->sender)) instanceof Player) {
            $plugin = EconomyAPI::getInstance();

            $output = $plugin->getMessage("topmoney-tag", [$this->page, $this->max], $this->sender) . "\n";
            $message = $plugin->getMessage("topmoney-format", [], $this->sender) . "\n";

            foreach (unserialize($this->topList) as $n => $list) {
                $output .= str_replace(["%1", "%2", "%3"], [$n, $list[0], $list[1]], $message);
            }
            $output = substr($output, 0, -1);

            if ($this->sender === "CONSOLE") {
                $plugin->getLogger()->info($output);
            } else {
                $player->sendMessage($output);
            }
        }
    }
}
