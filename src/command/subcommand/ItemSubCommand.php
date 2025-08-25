<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\Main;
use JonasWindmann\CoreAPI\CoreAPI;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for getting animation creation items
 */
class ItemSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "item",
            "Get animation creation items",
            "/blockanimator item",
            0,
            0,
            "blockanimator.command.item"
        );
    }

    public function execute(CommandSender $sender, array $args): void
    {
        try {
            $player = $this->senderToPlayer($sender);
        } catch (\InvalidArgumentException $e) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
            return;
        }

        // Get the custom item manager
        $customItemManager = CoreAPI::getInstance()->getCustomItemManager();

        // Give the frame creator item to the player
        $successFrameCreator = $customItemManager->giveCustomItem($player, Main::FRAME_CREATOR_ITEM_ID, 1);

        if (!$successFrameCreator) {
            $sender->sendMessage(TextFormat::RED . "Failed to give you the animation items. Your inventory might be full.");
            return;
        }

        // Give the undo/redo item to the player
        $successUndoRedo = $customItemManager->giveCustomItem($player, Main::UNDO_REDO_ITEM_ID, 1);

        if (!$successUndoRedo) {
            $sender->sendMessage(TextFormat::RED . "Failed to give you the undo/redo item. Your inventory might be full.");
            return;
        }

        // Send message to the player
        $sender->sendMessage(TextFormat::GREEN . "You have received animation creation items:");
        $sender->sendMessage(TextFormat::GREEN . "- Frame Creator: Use it to record animation frames");
        $sender->sendMessage(TextFormat::GREEN . "- Undo/Redo Tool: Left-click to undo, right-click to redo");
    }
}
