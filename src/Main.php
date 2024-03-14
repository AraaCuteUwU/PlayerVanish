<?php

namespace FiraAja\PlayerVanish;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Main extends PluginBase implements Listener
{

    /** @var array $vanished */
    private array $vanished = [];

    protected function onEnable(): void
    {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @param EntityDamageEvent $event
     * @return void
     */
    public function onDamage(EntityDamageEvent $event): void {
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if (!$damager instanceof Player) return;
            if (in_array($damager->getName(), $this->vanished) && !$damager->hasPermission("vanish.pvp")) {
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        if (in_array($player->getName(), $this->vanished) && !$player->hasPermission("vanish.pvp")) {
            $event->cancel();
        }
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($command->getName() === "vanish") {
            if (!$sender instanceof Player) return false;
            if (!$sender->hasPermission("vanish.command")) {
                $sender->sendMessage($this->getConfig()->get("no-permission"));
                return false;
            }
            if (isset($args[0])) {
                if (!$sender->hasPermission("vanish.command.other")) {
                    $sender->sendMessage($this->getConfig()->get("no-permission"));
                    return false;
                }
                $player = Server::getInstance()->getPlayerExact($args[0]) ?? Server::getInstance()->getPlayerByPrefix($args[0]);
                if ($player === null) {
                    $sender->sendMessage($this->getConfig()->get("player-null"));
                    return false;
                }
                if (!$player->getEffects()->has(VanillaEffects::INVISIBILITY()) && !in_array($player->getName(), $this->vanished)) {
                    $player->getEffects()->add(new EffectInstance(VanillaEffects::INVISIBILITY(), 2147483647, 0, !$player->hasPermission("vanish.effect")));
                    $this->vanished[$player->getName()] = true;
                    $sender->sendMessage(str_replace("{player}", $player->getName(), $this->getConfig()->get("vanish-enable-other")));
                    $player->sendMessage(str_replace("{sender}", $sender->getName(), $this->getConfig()->get("vanish-by-other")));
                } else {
                    $player->getEffects()->remove(VanillaEffects::INVISIBILITY());
                    unset($this->vanished[$player->getName()]);
                    $sender->sendMessage(str_replace("{player}", $player->getName(), $this->getConfig()->get("vanish-disable-other")));
                    $sender->sendMessage($this->getConfig()->get("vanish-disable"));
                }
            } else {
                if (!$sender->getEffects()->has(VanillaEffects::INVISIBILITY()) && !in_array($sender->getName(), $this->vanished)) {
                    $sender->getEffects()->add(new EffectInstance(VanillaEffects::INVISIBILITY(), 2147483647, 0, !$sender->hasPermission("vanish.effect")));
                    $this->vanished[$sender->getName()] = true;
                    $sender->sendMessage($this->getConfig()->get("vanish-enable"));
                } else {
                    $sender->getEffects()->remove(VanillaEffects::INVISIBILITY());
                    unset($this->vanished[$sender->getName()]);
                    $sender->sendMessage($this->getConfig()->get("vanish-disable"));
                }
            }
        }
        return false;
    }
}