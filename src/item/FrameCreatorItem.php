<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\item;

use JonasWindmann\BlockAnimator\Main;
use JonasWindmann\BlockAnimator\session\SessionManager;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

/**
 * Special item that creates animation frames when used
 */
class FrameCreatorItem {
    /**
     * Create a new frame creator item
     */
    public static function create(): Item {
        $item = VanillaItems::CLOCK();
        
        // Set custom name and lore
        $item->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Animation Frame Creator");
        
        $lore = [
            TextFormat::RESET . TextFormat::YELLOW . "Right-click to create a new animation frame",
            TextFormat::RESET . TextFormat::YELLOW . "Use /blockanimator complete <name> when finished"
        ];
        
        $item->setLore($lore);
        
        // Set custom NBT data to identify the item
        $nbt = $item->getNamedTag();
        $nbt->setString("BlockAnimator", "FrameCreator");
        
        return $item;
    }
    
    /**
     * Check if an item is a frame creator
     */
    public static function isFrameCreator(Item $item): bool {
        $nbt = $item->getNamedTag();
        return $nbt->getString("BlockAnimator", "") === "FrameCreator";
    }
    
    /**
     * Handle item use
     */
    public static function handleUse(Item $item, \pocketmine\player\Player $player): void {
        // Get the player's session
        $session = SessionManager::getInstance()->getSession($player);
        
        // Start a new frame
        $session->startFrame();
        
        // If this is the first frame, tell them they're starting a new animation
        if ($session->getFrameCount() === 0) {
            $player->sendMessage(TextFormat::GREEN . "Started recording a new animation. Make changes and use this item again to record the next frame.");
        } else {
            $player->sendMessage(TextFormat::GREEN . "Frame " . $session->getFrameCount() . " recorded. Make changes and use this item again for the next frame, or use /blockanimator complete <name> to finish.");
        }
    }
}
