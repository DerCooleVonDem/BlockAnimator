<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for stopping an animation
 */
class StopSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "stop",
            "Stop a playing animation",
            "/blockanimator stop <name>",
            1,
            1,
            "blockanimator.command.play"
        );
    }

    public function execute(CommandSender $sender, array $args): void
    {
        $name = $args[0];

        // Get the animation
        $animation = AnimationManager::getInstance()->getAnimation($name);
        if ($animation === null) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' not found");
            return;
        }

        // Check if it's playing
        if (!$animation->isPlaying()) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' is not currently playing");
            return;
        }

        // Stop the animation
        AnimationManager::getInstance()->stopAnimation($name);

        $sender->sendMessage(TextFormat::GREEN . "Stopped animation '" . $name . "'");
    }
}