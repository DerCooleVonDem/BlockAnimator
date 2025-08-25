<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\session\AnimationSessionComponent;
use JonasWindmann\CoreAPI\command\SubCommand;
use JonasWindmann\CoreAPI\CoreAPI;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for undoing the last animation frame change
 */
class UndoSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "undo",
            "Undo the last animation frame change",
            "/blockanimator undo",
            0,
            0,
            "blockanimator.command.create"
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

        // Get the player's session from CoreAPI
        $session = CoreAPI::getInstance()->getSessionManager()->getSessionByPlayer($player);
        if ($session === null) {
            $sender->sendMessage(TextFormat::RED . "Failed to get your session. Please try again.");
            return;
        }

        // Get the animation component
        $component = $session->getComponent("blockanimator:animation");
        if ($component === null || !$component instanceof AnimationSessionComponent) {
            $sender->sendMessage(TextFormat::RED . "Failed to get animation component. Please try again.");
            return;
        }

        // Check if recording is active
        if (!$component->isRecording()) {
            $sender->sendMessage(TextFormat::RED . "You are not currently recording an animation.");
            return;
        }

        // Attempt to undo the last change
        if ($component->undo()) {
            $player->sendMessage(TextFormat::GREEN . "Successfully undid the last change.");
        } else {
            $player->sendMessage(TextFormat::RED . "Nothing to undo.");
        }
    }
}