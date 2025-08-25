<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator;

use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\BlockAnimator\command\BlockAnimatorCommand;
use JonasWindmann\BlockAnimator\session\SessionManager;
use JonasWindmann\CoreAPI\CoreAPI;
use JonasWindmann\CoreAPI\item\CustomItemManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {
    use SingletonTrait;

    /** @var AnimationManager */
    private AnimationManager $animationManager;

    /** @var SessionManager */
    private SessionManager $sessionManager;

    /** @var string The ID of the frame creator item */
    public const FRAME_CREATOR_ITEM_ID = "blockanimator:frame_creator";

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        // Save default config
        $this->saveDefaultConfig();

        // Initialize managers
        $this->sessionManager = new SessionManager($this);
        $this->animationManager = new AnimationManager($this);

        // Register the frame creator item with CoreAPI
        $this->registerFrameCreatorItem();

        // Register command
        $this->getServer()->getCommandMap()->register("blockanimator", new BlockAnimatorCommand($this));

        // Register event listener
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Start animations marked as "run on startup"
        $this->startAutoRunAnimations();

        $this->getLogger()->info("BlockAnimator has been enabled!");
    }

    /**
     * Register the frame creator item with CoreAPI
     */
    private function registerFrameCreatorItem(): void {
        $customItemManager = CoreAPI::getInstance()->getCustomItemManager();

        // Check if the item is already registered
        if ($customItemManager->getCustomItem(self::FRAME_CREATOR_ITEM_ID) !== null) {
            return;
        }

        // Create and register the frame creator item
        $customItemManager->createAndRegisterCustomItem(
            self::FRAME_CREATOR_ITEM_ID,
            TextFormat::RESET . TextFormat::GOLD . "Animation Frame Creator",
            "tool",
            VanillaItems::CLOCK(),
            ["BlockAnimator" => "FrameCreator"],
            [
                TextFormat::RESET . TextFormat::YELLOW . "Right-click to create a new animation frame",
                TextFormat::RESET . TextFormat::YELLOW . "Use /blockanimator complete <name> when finished"
            ]
        );

        $this->getLogger()->debug("Registered frame creator item with CoreAPI");
    }

    protected function onDisable(): void {
        // Save all animations
        foreach ($this->animationManager->getAnimations() as $animation) {
            if ($animation->isPlaying()) {
                $this->animationManager->stopAnimation($animation->getName());
            }
            $this->animationManager->saveAnimation($animation);
        }

        $this->getLogger()->info("BlockAnimator has been disabled!");
    }

    /**
     * Start animations marked as "run on startup"
     */
    private function startAutoRunAnimations(): void {
        // Check if autorun is enabled in config
        if (!$this->getConfig()->getNested("autorun.enabled", true)) {
            $this->getLogger()->info("Autorun is disabled in config, skipping autorun animations");
            return;
        }

        // Get the startup delay from config (in seconds)
        $startupDelay = (int) $this->getConfig()->getNested("autorun.startup_delay", 5);

        // Count animations that should run on startup
        $autorunCount = 0;
        foreach ($this->animationManager->getAnimations() as $animation) {
            if ($animation->shouldRunOnStartup()) {
                $autorunCount++;
            }
        }

        if ($autorunCount === 0) {
            return; // No animations to autorun
        }

        $this->getLogger()->info("Found $autorunCount animations marked to run on startup, will start in $startupDelay seconds");

        // Schedule a delayed task to start the animations
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function() use ($autorunCount): void {
                $startedCount = 0;

                // Get all animations
                foreach ($this->animationManager->getAnimations() as $animation) {
                    // Check if it should run on startup
                    if ($animation->shouldRunOnStartup()) {
                        // Start the animation
                        $this->animationManager->startAnimation($animation->getName());
                        $startedCount++;
                    }
                }

                if ($startedCount > 0) {
                    $this->getLogger()->info("Started $startedCount animations marked to run on startup");
                }
            }
        ), $startupDelay * 20); // Convert seconds to ticks (20 ticks = 1 second)
    }

    /**
     * Handle player quit event
     *
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        // Remove the player's session
        $this->sessionManager->removeSession($event->getPlayer());
    }

    /**
     * Handle block place event
     *
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();

        // Get the player's session
        $session = $this->sessionManager->getSession($player);

        // If they're not recording, we don't need to process anything
        if (!$session->isRecording()) {
            return;
        }

        // Get the transaction from the event
        $transaction = $event->getTransaction();

        // Loop through all blocks in the transaction
        foreach ($transaction->getBlocks() as $blockData) {
            // BlockTransaction::getBlocks() returns [x, y, z, block]
            $x = $blockData[0];
            $y = $blockData[1];
            $z = $blockData[2];
            $block = $blockData[3];

            // Create a position object for the block
            $position = new \pocketmine\world\Position($x, $y, $z, $player->getWorld());

            // Record the block change
            $session->recordBlockChange($position, $block);
        }
    }

    /**
     * Handle block break event
     *
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        // Get the player's session
        $session = $this->sessionManager->getSession($player);

        // If they're recording, record the block change (as air)
        if ($session->isRecording()) {
            // Record the block as air (broken)
            $session->recordBlockChange($block->getPosition(), \pocketmine\block\VanillaBlocks::AIR());
        }
    }

    /**
     * Handle item use event for frame creator
     *
     * @param PlayerItemUseEvent $event
     */
    public function onItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $customItemManager = CoreAPI::getInstance()->getCustomItemManager();

        // Check if the item is a frame creator
        if ($customItemManager->isCustomItem($item) && $customItemManager->getCustomItemId($item) === self::FRAME_CREATOR_ITEM_ID) {
            // Cancel the event to prevent normal item use
            $event->cancel();

            // Get the player's session
            $session = $this->sessionManager->getSession($player);

            // Start a new frame
            $session->startFrame();

            // If this is the first frame, tell them they're starting a new animation
            if ($session->getFrameCount() === 0) {
                $player->sendMessage(TextFormat::GREEN . "Started recording a new animation. Make changes and use this item again to record the next frame.");
            } else {
                $player->sendMessage(TextFormat::GREEN . "Frame " . $session->getFrameCount() . " recorded. Make changes and use this item again for the next frame, or use /blockanimator complete <name> to finish.");
            }
        }
    }
}
