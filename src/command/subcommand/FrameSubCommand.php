<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\session\SessionManager;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for recording a new animation frame
 */
class FrameSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "frame",
            "Record a new animation frame",
            "/blockanimator frame",
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

        // Get the player's session
        $session = SessionManager::getInstance()->getSession($player);

        // Start a new frame
        $session->startFrame();

        // If this is the first frame, tell them they're starting a new animation
        if ($session->getFrameCount() === 0) {
            $player->sendMessage(TextFormat::GREEN . "Started recording a new animation. Make changes and use /blockanimator frame again to record the next frame.");
        } else {
            $player->sendMessage(TextFormat::GREEN . "Frame " . $session->getFrameCount() . " recorded. Make changes and use /blockanimator frame again for the next frame, or use /blockanimator complete <name> to finish.");
        }
    }
}