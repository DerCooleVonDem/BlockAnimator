<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\Main;
use JonasWindmann\CoreAPI\CoreAPI;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for getting a frame creator item
 */
class ItemSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "item",
            "Get a frame creator item",
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
        $success = $customItemManager->giveCustomItem($player, Main::FRAME_CREATOR_ITEM_ID, 1);

        if (!$success) {
            $sender->sendMessage(TextFormat::RED . "Failed to give you the frame creator item. Your inventory might be full.");
            return;
        }

        // Send message to the player
        $sender->sendMessage(TextFormat::GREEN . "You have received a frame creator item. Use it to record animation frames!");
    }
}