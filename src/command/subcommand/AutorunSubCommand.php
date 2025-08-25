<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for configuring an animation to run on server startup
 */
class AutorunSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "autorun",
            "Set animation to run on server startup",
            "/blockanimator autorun <name> <true|false>",
            2,
            2,
            "blockanimator.command.autorun"
        );
    }

    public function execute(CommandSender $sender, array $args): void
    {
        $name = $args[0];
        $enableAutorun = strtolower($args[1]);

        // Validate the autorun value
        if ($enableAutorun !== "true" && $enableAutorun !== "false") {
            $sender->sendMessage(TextFormat::RED . "The autorun value must be 'true' or 'false'");
            return;
        }

        // Convert string to boolean
        $enableAutorun = $enableAutorun === "true";

        // Get the animation
        $animation = AnimationManager::getInstance()->getAnimation($name);
        if ($animation === null) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' not found");
            return;
        }

        // Set the autorun value
        $animation->setRunOnStartup($enableAutorun);

        // Save the animation
        AnimationManager::getInstance()->saveAnimation($animation);

        if ($enableAutorun) {
            $sender->sendMessage(TextFormat::GREEN . "Animation '" . $name . "' will now run automatically on server startup");
        } else {
            $sender->sendMessage(TextFormat::GREEN . "Animation '" . $name . "' will no longer run automatically on server startup");
        }
    }
}