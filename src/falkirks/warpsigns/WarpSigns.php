<?php
namespace falkirks\warpsigns;


use falkirks\simplewarp\api\SimpleWarpAPI;
use falkirks\simplewarp\Warp;
use pocketmine\block\SignPost;
use pocketmine\block\WallSign;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class WarpSigns extends PluginBase implements Listener, CommandExecutor{
    const WARP_SIGN_REQUEST_KEY = "[warp]";
    const WARP_SIGN_KEY = TextFormat::AQUA . "SimpleWarp" . TextFormat::RESET;

    public function onEnable(){
        if(!($this->getServer()->getPluginManager()->getPlugin("SimpleWarp") instanceof PluginBase)){
            $this->getLogger()->critical("Failed to connect to SimpleWarp instance.");
            $this->setEnabled(false);
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        foreach($this->getServer()->getLevels() as $level){
            foreach($level->getTiles() as $tile){
                if($tile instanceof Sign){
                    if($tile->getText()[0] == WarpSigns::WARP_SIGN_KEY){
                        $sender->sendMessage(TextFormat::GREEN . "Active" . TextFormat::RESET . " warp to " . ($tile->getText()[1] != null ? TextFormat::AQUA . $tile->getText()[1] . TextFormat::RESET : TextFormat::RED . "nowhere" . TextFormat::RESET) . " at X:" . $tile->getX() . " Y:" . $tile->getY() . " Z:" . $tile->getZ() . " LEVEL:" .$tile->getLevel()->getName());
                    }
                    elseif($tile->getText()[0] == WarpSigns::WARP_SIGN_REQUEST_KEY){
                        $sender->sendMessage(TextFormat::RED . "Inactive" . TextFormat::RESET . " warp to " . ($tile->getText()[1] != null ? TextFormat::AQUA . $tile->getText()[1] . TextFormat::RESET : TextFormat::RED . "nowhere" . TextFormat::RESET) . " at X:" . $tile->getX() . " Y:" . $tile->getY() . " Z:" . $tile->getZ() . " LEVEL:" .$tile->getLevel()->getName());
                    }
                }
            }
        }
    }

    public function onSignChange(SignChangeEvent $event){
        if($event->getLine(0) === WarpSigns::WARP_SIGN_KEY){
            $event->getPlayer()->sendMessage("That sign configuration is blocked by " . TextFormat::AQUA . "WarpSigns" . TextFormat::RESET . ", change your sign or uninstall the plugin.");
            $event->setCancelled(true);
        }
        elseif($event->getLine(0) === WarpSigns::WARP_SIGN_REQUEST_KEY) {
            if ($event->getPlayer()->hasPermission("warpsigns.create")) {
                if ($event->getLine(1) != null) {
                    $warp = SimpleWarpAPI::getInstance($this)->getWarp($event->getLine(1));
                    if ($warp instanceof Warp) {
                        $event->setLine(0, WarpSigns::WARP_SIGN_KEY);
                        $event->getPlayer()->sendMessage("Warp sign created.");
                    }
                    else {
                        $event->getPlayer()->sendMessage("That warp doesn't exist.");
                    }
                }
                else {
                    $event->getPlayer()->sendMessage("You must specify a warp.");
                }
            }
        }
    }
    public function onPlayerInteract(PlayerInteractEvent $event){
        if($event->getPlayer()->hasPermission("warpsigns.use")) {
            if ($event->getBlock() instanceof SignPost || $event->getBlock() instanceof WallSign) {
                $tile = $event->getBlock()->getLevel()->getTile($event->getBlock());
                if ($tile instanceof Sign) {
                    $text = $tile->getText();
                    if ($text[0] === WarpSigns::WARP_SIGN_KEY) {
                        if ($text[1] != null) {
                            $warp = SimpleWarpAPI::getInstance($this)->getWarp($text[1]);
                            if($warp instanceof Warp) {
                                $warp->teleport($event->getPlayer());
                                //TODO figure out if this causes crashes on some clients
                                //$event->setCancelled();
                            }
                        }
                    }
                }
            }
        }
    }
}
