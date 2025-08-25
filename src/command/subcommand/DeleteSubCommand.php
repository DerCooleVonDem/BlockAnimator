<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for deleting an animation
 */
class DeleteSubCommand extends SubCommand
{
    public function __construct()
    {
        parent::__construct(
            "delete",
            "Delete an animation",
            "/blockanimator delete <name>",
            1,
            1,
            "blockanimator.command.delete"
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

        // Delete the animation
        AnimationManager::getInstance()->deleteAnimation($name);

        $sender->sendMessage(TextFormat::GREEN . "Deleted animation '" . $name . "'");
    }
}