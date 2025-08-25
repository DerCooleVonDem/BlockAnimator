<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command;

use JonasWindmann\BlockAnimator\animation\Animation;
use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\BlockAnimator\item\FrameCreatorItem;
use JonasWindmann\BlockAnimator\Main;
use JonasWindmann\BlockAnimator\session\SessionManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * Main command for the BlockAnimator plugin
 */
class BlockAnimatorCommand extends Command {
    /** @var Main */
    private Main $plugin;

    /**
     * BlockAnimatorCommand constructor
     *
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        parent::__construct(
            "blockanimator",
            "Manage block animations",
            "/blockanimator <frame|complete|start|stop|list|delete|autorun|item>",
            ["ba"]
        );

        $this->setPermission("blockanimator.command");
        $this->plugin = $plugin;
    }

    /**
     * Execute the command
     *
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (count($args) === 0) {
            $this->sendHelp($sender);
            return true;
        }

        $subCommand = strtolower($args[0]);

        switch ($subCommand) {
            case "frame":
                return $this->handleFrameCommand($sender);

            case "complete":
                return $this->handleCompleteCommand($sender, $args);

            case "start":
                return $this->handleStartCommand($sender, $args);

            case "stop":
                return $this->handleStopCommand($sender, $args);

            case "list":
                return $this->handleListCommand($sender);

            case "delete":
                return $this->handleDeleteCommand($sender, $args);

            case "autorun":
                return $this->handleAutorunCommand($sender, $args);

            case "item":
                return $this->handleItemCommand($sender);

            default:
                $sender->sendMessage(TextFormat::RED . "Unknown subcommand: " . $subCommand);
                $this->sendHelp($sender);
                return false;
        }
    }

    /**
     * Send help message to the sender
     *
     * @param CommandSender $sender
     */
    private function sendHelp(CommandSender $sender): void {
        $sender->sendMessage(TextFormat::GREEN . "=== BlockAnimator Commands ===");
        $sender->sendMessage(TextFormat::YELLOW . "/blockanimator frame" . TextFormat::WHITE . " - Record a new frame");
        $sender->sendMessage(TextFormat::YELLOW . "/blockanimator complete <name>" . TextFormat::WHITE . " - Complete and save the animation");
        $sender->sendMessage(TextFormat::YELLOW . "/blockanimator start <name> [speed]" . TextFormat::WHITE . " - Start playing an animation");
        $sender->sendMessage(TextFormat::YELLOW . "/blockanimator stop <name>" . TextFormat::WHITE . " - Stop a playing animation");
        $sender->sendMessage(TextFormat::YELLOW . "/blockanimator item" . TextFormat::WHITE . " - Get a frame creator item");
        $sender->sendMessage(TextFormat::YELLOW . "/blockanimator list" . TextFormat::WHITE . " - List all animations");
        $sender->sendMessage(TextFormat::YELLOW . "/blockanimator delete <name>" . TextFormat::WHITE . " - Delete an animation");
        $sender->sendMessage(TextFormat::YELLOW . "/blockanimator autorun <name> <true|false>" . TextFormat::WHITE . " - Set animation to run on server startup");
    }

    /**
     * Handle the frame subcommand
     *
     * @param CommandSender $sender
     * @return bool
     */
    private function handleFrameCommand(CommandSender $sender): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
            return false;
        }

        if (!$sender->hasPermission("blockanimator.command.create")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to create animations");
            return false;
        }

        // Get the player's session
        $session = SessionManager::getInstance()->getSession($sender);

        // Start a new frame
        $session->startFrame();

        // If this is the first frame, tell them they're starting a new animation
        if ($session->getFrameCount() === 0) {
            $sender->sendMessage(TextFormat::GREEN . "Started recording a new animation. Make changes and use /blockanimator frame again to record the next frame.");
        } else {
            $sender->sendMessage(TextFormat::GREEN . "Frame " . $session->getFrameCount() . " recorded. Make changes and use /blockanimator frame again for the next frame, or use /blockanimator complete <name> to finish.");
        }

        return true;
    }

    /**
     * Handle the complete subcommand
     *
     * @param CommandSender $sender
     * @param array $args
     * @return bool
     */
    private function handleCompleteCommand(CommandSender $sender, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
            return false;
        }

        if (!$sender->hasPermission("blockanimator.command.create")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to create animations");
            return false;
        }

        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . "Usage: /blockanimator complete <name>");
            return false;
        }

        $name = $args[1];

        // Check if an animation with this name already exists
        if (AnimationManager::getInstance()->getAnimation($name) !== null) {
            $sender->sendMessage(TextFormat::RED . "An animation with the name '" . $name . "' already exists");
            return false;
        }

        // Get the player's session
        $session = SessionManager::getInstance()->getSession($sender);

        // Check if they're recording
        if (!$session->isRecording()) {
            $sender->sendMessage(TextFormat::RED . "You're not currently recording an animation. Use /blockanimator frame to start.");
            return false;
        }

        // Check if they have recorded any frames
        if ($session->getFrameCount() === 0) {
            $sender->sendMessage(TextFormat::RED . "You haven't recorded any frames yet. Use /blockanimator frame to record frames.");
            return false;
        }

        // Complete the recording and get the frames
        $frames = $session->completeRecording();

        // Get the default frame delay from config
        $frameDelay = $this->plugin->getConfig()->get("playback.default_frame_delay", 10);

        // Create a new animation
        $animation = AnimationManager::getInstance()->createAnimation($name, $sender->getWorld(), $frameDelay);

        // Add all frames to the animation
        foreach ($frames as $frame) {
            $animation->addFrame($frame);
        }

        // Save the animation
        AnimationManager::getInstance()->saveAnimation($animation);

        $sender->sendMessage(TextFormat::GREEN . "Animation '" . $name . "' created with " . count($frames) . " frames");

        return true;
    }

    /**
     * Handle the start subcommand
     *
     * @param CommandSender $sender
     * @param array $args
     * @return bool
     */
    private function handleStartCommand(CommandSender $sender, array $args): bool {
        if (!$sender->hasPermission("blockanimator.command.play")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to play animations");
            return false;
        }

        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . "Usage: /blockanimator start <name> [speed]");
            return false;
        }

        $name = $args[1];

        // Get the animation
        $animation = AnimationManager::getInstance()->getAnimation($name);
        if ($animation === null) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' not found");
            return false;
        }

        // Check if it's already playing
        if ($animation->isPlaying()) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' is already playing");
            return false;
        }

        // Get custom speed if provided
        $frameDelay = null;
        if (isset($args[2]) && is_numeric($args[2])) {
            $frameDelay = (int)$args[2];
            if ($frameDelay < 1) {
                $frameDelay = 1;
            }
        }

        // Start the animation
        AnimationManager::getInstance()->startAnimation($name, $frameDelay);

        $sender->sendMessage(TextFormat::GREEN . "Started playing animation '" . $name . "'");

        return true;
    }

    /**
     * Handle the stop subcommand
     *
     * @param CommandSender $sender
     * @param array $args
     * @return bool
     */
    private function handleStopCommand(CommandSender $sender, array $args): bool {
        if (!$sender->hasPermission("blockanimator.command.play")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to play animations");
            return false;
        }

        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . "Usage: /blockanimator stop <name>");
            return false;
        }

        $name = $args[1];

        // Get the animation
        $animation = AnimationManager::getInstance()->getAnimation($name);
        if ($animation === null) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' not found");
            return false;
        }

        // Check if it's playing
        if (!$animation->isPlaying()) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' is not currently playing");
            return false;
        }

        // Stop the animation
        AnimationManager::getInstance()->stopAnimation($name);

        $sender->sendMessage(TextFormat::GREEN . "Stopped animation '" . $name . "'");

        return true;
    }

    /**
     * Handle the list subcommand
     *
     * @param CommandSender $sender
     * @return bool
     */
    private function handleListCommand(CommandSender $sender): bool {
        // Get all animations
        $animations = AnimationManager::getInstance()->getAnimations();

        if (empty($animations)) {
            $sender->sendMessage(TextFormat::YELLOW . "No animations found");
            return true;
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

        return true;
    }

    /**
     * Handle the delete subcommand
     *
     * @param CommandSender $sender
     * @param array $args
     * @return bool
     */
    private function handleDeleteCommand(CommandSender $sender, array $args): bool {
        if (!$sender->hasPermission("blockanimator.command.delete")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to delete animations");
            return false;
        }

        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . "Usage: /blockanimator delete <name>");
            return false;
        }

        $name = $args[1];

        // Get the animation
        $animation = AnimationManager::getInstance()->getAnimation($name);
        if ($animation === null) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' not found");
            return false;
        }

        // Delete the animation
        AnimationManager::getInstance()->deleteAnimation($name);

        $sender->sendMessage(TextFormat::GREEN . "Deleted animation '" . $name . "'");

        return true;
    }

    /**
     * Handle the autorun subcommand
     *
     * @param CommandSender $sender
     * @param array $args
     * @return bool
     */
    private function handleAutorunCommand(CommandSender $sender, array $args): bool {
        if (!$sender->hasPermission("blockanimator.command.autorun")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to configure autorun settings");
            return false;
        }

        if (count($args) < 3) {
            $sender->sendMessage(TextFormat::RED . "Usage: /blockanimator autorun <name> <true|false>");
            return false;
        }

        $name = $args[1];
        $enableAutorun = strtolower($args[2]);

        // Validate the autorun value
        if ($enableAutorun !== "true" && $enableAutorun !== "false") {
            $sender->sendMessage(TextFormat::RED . "The autorun value must be 'true' or 'false'");
            return false;
        }

        // Convert string to boolean
        $enableAutorun = $enableAutorun === "true";

        // Get the animation
        $animation = AnimationManager::getInstance()->getAnimation($name);
        if ($animation === null) {
            $sender->sendMessage(TextFormat::RED . "Animation '" . $name . "' not found");
            return false;
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

        return true;
    }

    /**
     * Handle the item command
     * Gives the player a frame creator item
     *
     * @param CommandSender $sender
     * @return bool
     */
    private function handleItemCommand(CommandSender $sender): bool {
        // Check if the sender is a player
        if (!($sender instanceof Player)) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game");
            return false;
        }

        // Check if the player has permission
        if (!$sender->hasPermission("blockanimator.command.item")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command");
            return false;
        }

        // Create the frame creator item
        $item = FrameCreatorItem::create();

        // Give the item to the player
        $sender->getInventory()->addItem($item);

        // Send message to the player
        $sender->sendMessage(TextFormat::GREEN . "You have received a frame creator item. Use it to record animation frames!");

        return true;
    }
}
