<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for listing all animations
 */
class ListSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "list",
            "List all animations",
            "/blockanimator list",
            0,
            0,
            "blockanimator.command"
        );
    }

    public function execute(CommandSender $sender, array $args): void
    {
        // Get all animations
        $animations = AnimationManager::getInstance()->getAnimations();

        if (empty($animations)) {
            $sender->sendMessage(TextFormat::YELLOW . "No animations found");
            return;
        }

        $sender->sendMessage(TextFormat::GREEN . "=== Animations ===");

        foreach ($animations as $animation) {
            $playStatus = $animation->isPlaying() ? TextFormat::GREEN . "[PLAYING]" : TextFormat::RED . "[STOPPED]";
            $autorunStatus = $animation->shouldRunOnStartup() ? TextFormat::GREEN . "[AUTORUN]" : "";
            $sender->sendMessage(
                TextFormat::YELLOW . $animation->getName() .
                TextFormat::WHITE . " - " .
                $animation->getFrameCount() . " frames, " .
                "delay: " . $animation->getFrameDelay() . " ticks, " .
                "world: " . $animation->getWorld()->getFolderName() . " " .
                $playStatus . " " . $autorunStatus
            );
        }
    }
}