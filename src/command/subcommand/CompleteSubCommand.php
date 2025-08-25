<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command\subcommand;

use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\BlockAnimator\Main;
use JonasWindmann\BlockAnimator\session\SessionManager;
use JonasWindmann\CoreAPI\command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * Subcommand for completing and saving an animation
 */
class CompleteSubCommand extends SubCommand
{
    /** @var Main */
    private Main $plugin;

    public function __construct(Main $plugin)
    {
        parent::__construct(
            "complete",
            "Complete and save the animation",
            "/blockanimator complete <name>",
            1,
            1,
            "blockanimator.command.create"
        );
        
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, array $args): void
    {
        try {
            $player = $this->senderToPlayer($sender);
        } catch (\InvalidArgumentException $e) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
            return;
        }

        $name = $args[0];

        // Check if an animation with this name already exists
        if (AnimationManager::getInstance()->getAnimation($name) !== null) {
            $sender->sendMessage(TextFormat::RED . "An animation with the name '" . $name . "' already exists");
            return;
        }

        // Get the player's session
        $session = SessionManager::getInstance()->getSession($player);

        // Check if they're recording
        if (!$session->isRecording()) {
            $sender->sendMessage(TextFormat::RED . "You're not currently recording an animation. Use /blockanimator frame to start.");
            return;
        }

        // Check if they have recorded any frames
        if ($session->getFrameCount() === 0) {
            $sender->sendMessage(TextFormat::RED . "You haven't recorded any frames yet. Use /blockanimator frame to record frames.");
            return;
        }

        // Complete the recording and get the frames
        $frames = $session->completeRecording();

        // Get the default frame delay from config
        $frameDelay = $this->plugin->getConfig()->get("playback.default_frame_delay", 10);

        // Create a new animation
        $animation = AnimationManager::getInstance()->createAnimation($name, $player->getWorld(), $frameDelay);

        // Add all frames to the animation
        foreach ($frames as $frame) {
            $animation->addFrame($frame);
        }

        // Save the animation
        AnimationManager::getInstance()->saveAnimation($animation);

        $sender->sendMessage(TextFormat::GREEN . "Animation '" . $name . "' created with " . count($frames) . " frames");
    }
}