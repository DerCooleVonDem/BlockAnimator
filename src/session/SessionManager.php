<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\session;

use JonasWindmann\BlockAnimator\Main;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

/**
 * Manages all player sessions
 */
class SessionManager {
    use SingletonTrait;
    
    /** @var Main */
    private Main $plugin;
    
    /** @var PlayerSession[] */
    private array $sessions = [];
    
    /**
     * SessionManager constructor
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        self::setInstance($this);
    }
    
    /**
     * Get a player's session, creating it if it doesn't exist
     * 
     * @param Player $player
     * @return PlayerSession
     */
    public function getSession(Player $player): PlayerSession {
        $uuid = $player->getUniqueId()->toString();
        
        if (!isset($this->sessions[$uuid])) {
            $this->sessions[$uuid] = new PlayerSession($player);
        }
        
        return $this->sessions[$uuid];
    }
    
    /**
     * Remove a player's session
     * 
     * @param Player $player
     */
    public function removeSession(Player $player): void {
        $uuid = $player->getUniqueId()->toString();
        
        if (isset($this->sessions[$uuid])) {
            // Cancel any recording in progress
            $this->sessions[$uuid]->cancelRecording();
            
            // Remove the session
            unset($this->sessions[$uuid]);
        }
    }
    
    /**
     * Get all sessions
     * 
     * @return PlayerSession[]
     */
    public function getSessions(): array {
        return $this->sessions;
    }
}
