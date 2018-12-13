<?php
namespace StairSeatDown;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\block\Stair;
use pocketmine\entity\Entity;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\types\EntityLink;
class Main extends PluginBase implements Listener {
	public function onEnable() {
		$PluginName = "StairSeatDown";
		$version = "1.0.0";
    		$this->getServer()->getPluginManager()->registerEvents($this, $this);
    		$this->getlogger()->info($PluginName."Version:".$version."Kami mengisinya. Pengarang: gamesempatIRS, maru");
    		$this->getlogger()->warning("Plug-in ini didistribusikan di bawah lisensi LGPL.");
		$this->getLogger()->notice("Sumber kode plugin ini dikutip dari PmChair.");
    		$this->getlogger()->info("Saat menggunakan plugin ini, pasang nama plugin di suatu tempat [".$PluginName."] Dan nama penulis [gamesukimanIRS, maru] Disarankan untuk mendeskripsikan.");
		if(!file_exists($this->getDataFolder())){
         		mkdir($this->getDataFolder(), 0756, true);
       		}
       		$this->Config = new Config($this->getDataFolder() . "message.yml", Config::YAML, [
			'touch-popup' => '§bTekan ulang untuk duduk',
			'touch-popup-ver2' => '§bKetuk ulang untuk duduk',
			'seat-down' => '§aSaya duduk di tangga'
		]);
		if(isset($onChair)){
			unset($onChair);
		}
		if(isset($doubleTap)){
			unset($doubleTap);
		}
	}

	private $onChair = [ ];
	private $doubleTap = [ ];

	public function get($m) {
		return $this->Config->get($m);
	}
	public function onTouch(PlayerInteractEvent $event) {
		if($event->getPlayer()->getInventory()->getItemInHand()->getId() == 0) {
			$player = $event->getPlayer ();
			$block = $event->getBlock ();
			if ($block instanceof Stair) {
				if (isset($this->doubleTap[$player->getName()])) {
					if(!isset($this->onChair[$player->getName()])){
						$this->SeatDown($player, $block);
						unset($this->doubleTap[$player->getName()]);
					} else {
						$this->StandUp($player);
						unset ( $this->onChair [$player->getName ()] );
						unset ( $this->doubleTap[$player->getName()] );
						$this->SeatDown($player, $block);
					}
				}else{
					if(!isset($this->onChair[$player->getName()])){
						$this->doubleTap[$player->getName()] = "1stTapComplete";
						$player->sendPopup($this->get("touch-popup"));
					}else{
						$this->doubleTap [$player->getName ()] = "1stTapComplete";
						$player->sendPopup ($this->get("touch-popup-ver2"));
					}
				}
			}
		}
	}
	public function SeatDown($player, $stair){
		$sx = intval($stair->getX());
		$sy = intval($stair->getY());
		$sz = intval($stair->getZ());
		$nx = $sx + 0.5;
		$ny = $sy + 1.5;
		$nz = $sz + 0.5;
		$pk = new AddEntityPacket();
		$entityRuntimeId = $player->getId() + 10000;
		$this->onChair[$player->getName()] = $entityRuntimeId;
		$pk->entityRuntimeId = $entityRuntimeId;
		$pk->type = 84;
		$pk->position = new Vector3($nx, $ny, $nz);
		$pk->motion = new Vector3(0,0,0);
		$flags = (
			(1 << Entity::DATA_FLAG_IMMOBILE) | (1 << Entity::DATA_FLAG_INVISIBLE)
 		);
 		$pk->metadata = [
 			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
 		];
		$pk->links[] = new EntityLink($pk->entityRuntimeId,$player->getId(),2,false);
		Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $pk);
		$player->sendPopup($this->get("seat-down"));
	}
	public function StandUp($player){
		$removepk = new RemoveEntityPacket();
		$removepk->entityUniqueId = $this->onChair [$player->getName ()];
		Server::getInstance()->broadcastPacket ( Server::getInstance()->getOnlinePlayers (), $removepk );
	}
	public function onJump(DataPacketReceiveEvent $event) {
		$packet = $event->getPacket ();
		if ($packet instanceof PlayerActionPacket) {
			$player = $event->getPlayer ();
			if ($packet->action === PlayerActionPacket::ACTION_JUMP && isset ( $this->onChair [$player->getName ()] )) {
				$this->StandUp($player);
				unset ( $this->onChair [$player->getName ()] );
				unset($this->doubleTap[$player->getName()]);
			}
		}
	}
	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer ();
		if (isset ( $this->onChair [$player->getName ()] )) {
			$this->StandUp($player);
			unset ( $this->onChair [$player->getName ()] );
			unset($this->doubleTap[$player->getName()]);
		}
	}
}
