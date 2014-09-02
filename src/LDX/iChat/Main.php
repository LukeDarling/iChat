<?php
namespace LDX\iChat;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerChatEvent;
class Main extends PluginBase implements Listener {
  public function onEnable() {
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
  }
  public function onCommand(CommandSender $i,Command $cmd,$label,array $args) {
    switch(strtolower($cmd->getName())) {
      case "chat":
        if($i->hasPermission("ichat") || $i->hasPermission("ichat.*") || $i->hasPermission("ichat.command") || $i->hasPermission("ichat.command.*") || $i->hasPermission("ichat.command.chat")) {
          if(!($i instanceof Player)) {
            $i->sendMessage("[iChat] Command must be used in-game.");
            return true;
          }
          if(!isset($args[0])) {
            return false;
          }
          $s = strtolower($args[0]);
          if($s == "on") {
            $o = $this->enableChat($i);
          } else if($s == "off") {
            $o = $this->disableChat($i);
          } else {
            return false;
          }
          $i->sendMessage($o);
          return true;
        } else {
          $i->sendMessage("[iChat] You don't have permission to use this command.");
          return true;
        }
      break;
      case "mute":
        if($i->hasPermission("ichat") || $i->hasPermission("ichat.*") || $i->hasPermission("ichat.command") || $i->hasPermission("ichat.command.*") || $i->hasPermission("ichat.command.mute")) {
          if(!isset($args[0])) {
            return false;
          }
          $p = $this->getServer()->getPlayer($args[0]);
          if(!($p instanceof Player)) {
            $i->sendMessage("[iChat] Player not connected.");
            return true;
          }
          if($this->mute($p)) {
            $i->sendMessage("[iChat] " . $p->getName() . " has been muted.");
          } else {
            $i->sendMessage("[iChat] " . $p->getName() . " was already muted.");
          }
          return true;
        } else {
          $i->sendMessage("[iChat] You don't have permission to use this command.");
          return true;
        }
      break;
      case "unmute":
        if($i->hasPermission("ichat") || $i->hasPermission("ichat.*") || $i->hasPermission("ichat.command") || $i->hasPermission("ichat.command.*") || $i->hasPermission("ichat.command.unmute")) {
          if(!isset($args[0])) {
            return false;
          }
          $p = $this->getServer()->getPlayer($args[0]);
          if(!($p instanceof Player)) {
            $i->sendMessage("[iChat] Player not connected.");
            return true;
          }
          if($this->unmute($p)) {
            $i->sendMessage("[iChat] " . $p->getName() . " has been unmuted.");
          } else {
            $i->sendMessage("[iChat] " . $p->getName() . " wasn't muted.");
          }
          return true;
        } else {
          $i->sendMessage("[iChat] You don't have permission to use this command.");
          return true;
        }
      break;
    }
  }
  /**
  * @param PlayerChatEvent $event
  *
  * @priority HIGHEST
  * @ignoreCancelled true
  */
  public function onChat(PlayerChatEvent $event) {
    $this->checkData();
    if(!isset($this->data["mute"][strtolower($event->getPlayer()->getName())])) {
      if($this->checkMessage($event->getMessage()) || ($event->getPlayer()->hasPermission("ichat") || $event->getPlayer()->hasPermission("ichat.*") || $event->getPlayer()->hasPermission("ichat.bypass"))) {
        if(!isset($this->data["chat"][strtolower($event->getPlayer()->getName())])) {
          $r = array();
          foreach($this->getServer()->getOnlinePlayers() as $p) {
            if(!isset($this->data["chat"][strtolower($p->getName())])) {
              $r[] = $p;
            }
          }
          $event->setRecipients($r);
        } else {
          $event->getPlayer()->sendMessage("[iChat] You disabled your chat.");
          $event->setCancelled();
        }
      } else {
        $event->getPlayer()->sendMessage("[iChat] Message blocked.");
        $event->setCancelled();
      }
    } else {
      $event->getPlayer()->sendMessage("[iChat] You are muted.");
      $event->setCancelled();
    }
  }
  public function enableChat($p) {
    if(isset($this->data["chat"][strtolower($p->getName())])) {
      unset($this->data["chat"][strtolower($p->getName())]);
      $this->saveData();
      return "[iChat] Chat has been enabled.";
    } else {
      return "[iChat] Chat was already enabled.";
    }
  }
  public function disableChat($p) {
    if(isset($this->data["chat"][strtolower($p->getName())])) {
      return "[iChat] Chat was already disabled.";
    } else {
      $this->data["chat"][strtolower($p->getName())] = array();
      $this->saveData();
      return "[iChat] Chat has been disabled.";
    }
  }
  public function mute($p) {
    if(!isset($this->data["mute"][strtolower($p->getName())])) {
      $this->data["mute"][strtolower($p->getName())] = array();
      $this->saveData();
      return true;
    } else {
      return false;
    }
  }
  public function unmute($p) {
    if(isset($this->data["mute"][strtolower($p->getName())])) {
      unset($this->data["mute"][strtolower($p->getName())]);
      $this->saveData();
      return true;
    } else {
      return false;
    }
  }
  public function checkMessage($m) {
    $m = str_replace(array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","!","&","~","$","#","@","%","^","&","*","(",")","-","_","[","]","{","}","?","/","\\",".",",","`","|","=","+","<",">","\"","'",":",";"," "),"",$m);
    /* |      If anyone has a better method for finding only capital letters, please email me at htmlguy7@gmail.com.      |
       | I am aware that this method doesn't filter out all non-capital letters. I can't figure out preg_replace. Thanks. | */
    if(strlen($m) > 6) {
      return false;
    } else {
      return true;
    }
  }
  public function saveData() {
    $this->checkData();
    file_put_contents($this->getDataFolder() . "data.bin",yaml_emit($this->data));
  }
  public function checkData() {
    @mkdir($this->getDataFolder());
    if(!file_exists($this->getDataFolder() . "data.bin")) {
      file_put_contents($this->getDataFolder() . "data.bin",yaml_emit(array("mute" => array(),"chat" => array())));
    }
    if(!isset($this->data)) {
      $this->data = yaml_parse(file_get_contents($this->getDataFolder() . "data.bin"));
    }
  }
}
?>
