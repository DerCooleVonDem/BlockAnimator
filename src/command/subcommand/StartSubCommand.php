<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for starting an animation
 */
class StartSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "start",
            "Start playing an animation",
            "/blockanimator start <name> [speed]",
            1,
            2,
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

        // Check if it's already playing
        if ($animation->isPlaying()) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' is already playing");
            return;
        }

        // Get custom speed if provided
        $frameDelay = null;
        if (isset($args[1]) && is_numeric($args[1])) {
            $frameDelay = (int)$args[1];
            if ($frameDelay < 1) {
                $frameDelay = 1;
            }
        }

        // Start the animation
        AnimationManager::getInstance()->startAnimation($name, $frameDelay);

        $sender->sendMessage(TextFormat::GREEN . "Started playing animation '" . $name . "'");
    }
}