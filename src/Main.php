<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator;

use JonasWindmann\BlockAnimator\animation\AnimationManager;
use JonasWindmann\BlockAnimator\command\BlockAnimatorCommand;
use JonasWindmann\BlockAnimator\session\AnimationSessionComponent;
use JonasWindmann\BlockAnimator\task\CreationParticleTask;
use JonasWindmann\CoreAPI\CoreAPI;
use JonasWindmann\CoreAPI\item\CustomItemManager;
use JonasWindmann\CoreAPI\session\SimpleComponentFactory;
use pocketmine\data\bedrock\block\BlockLegacyMetadata;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
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

    /** @var string The ID of the frame creator item */
    public const FRAME_CREATOR_ITEM_ID = "blockanimator:frame_creator";

    /** @var string The ID of the undo/redo item */
    public const UNDO_REDO_ITEM_ID = "blockanimator:undo_redo";

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        // Save default config
        $this->saveDefaultConfig();

        // Initialize managers
        $this->animationManager = new AnimationManager($this);

        // Register the animation session component with CoreAPI
        $sessionManager = CoreAPI::getInstance()->getSessionManager();
        $sessionManager->registerComponentFactory(
            SimpleComponentFactory::createFactory("blockanimator:animation", function() {
                return new AnimationSessionComponent();
            })
        );

        // Register the custom items with CoreAPI
        $this->registerFrameCreatorItem();
        $this->registerUndoRedoItem();

        // Register command
        $this->getServer()->getCommandMap()->register("blockanimator", new BlockAnimatorCommand($this));

        // Register event listener
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Start animations marked as "run on startup"
        $this->startAutoRunAnimations();

        // Start the creation particle task
        $this->startCreationParticleTask();

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

    /**
     * Register the undo/redo item with CoreAPI
     */
    private function registerUndoRedoItem(): void {
        $customItemManager = CoreAPI::getInstance()->getCustomItemManager();

        // Check if the item is already registered
        if ($customItemManager->getCustomItem(self::UNDO_REDO_ITEM_ID) !== null) {
            return;
        }

        // Create and register the undo/redo item
        $customItemManager->createAndRegisterCustomItem(
            self::UNDO_REDO_ITEM_ID,
            TextFormat::RESET . TextFormat::GOLD . "Animation Undo/Redo",
            "tool",
            VanillaItems::COMPASS(),
            ["BlockAnimator" => "UndoRedo"],
            [
                TextFormat::RESET . TextFormat::YELLOW . "Left-click to undo the last change",
                TextFormat::RESET . TextFormat::YELLOW . "Right-click to redo the last undone change"
            ]
        );

        $this->getLogger()->debug("Registered undo/redo item with CoreAPI");
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
     * Start the creation particle task
     */
    private function startCreationParticleTask(): void {
        // Create and schedule the task to run every 10 ticks (0.5 seconds)
        $this->getScheduler()->scheduleRepeatingTask(
            new CreationParticleTask($this),
            10
        );
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
        // CoreAPI's session manager automatically handles session cleanup
    }

    /**
     * Handle block place event
     *
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();

        // Get the player's session from CoreAPI
        $session = CoreAPI::getInstance()->getSessionManager()->getSessionByPlayer($player);
        if ($session === null) {
            return;
        }

        // Get the animation component
        $component = $session->getComponent("blockanimator:animation");
        if ($component === null || !$component instanceof AnimationSessionComponent) {
            return;
        }

        // If they're not recording, we don't need to process anything
        if (!$component->isRecording()) {
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
            $component->recordBlockChange($position, $block);
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

        // Get the player's session from CoreAPI
        $session = CoreAPI::getInstance()->getSessionManager()->getSessionByPlayer($player);
        if ($session === null) {
            return;
        }

        // Get the animation component
        $component = $session->getComponent("blockanimator:animation");
        if ($component === null || !$component instanceof AnimationSessionComponent) {
            return;
        }

        // If they're recording, record the block change (as air)
        if ($component->isRecording()) {
            // Record the block as air (broken)
            $component->recordBlockChange($block->getPosition(), \pocketmine\block\VanillaBlocks::AIR());
        }
    }

    /**
     * Handle item use event for frame creator and undo/redo (right-click)
     *
     * @param PlayerItemUseEvent $event
     */
    public function onItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $customItemManager = CoreAPI::getInstance()->getCustomItemManager();

        // Check if the item is a custom item
        if (!$customItemManager->isCustomItem($item)) {
            return;
        }

        $customItemId = $customItemManager->getCustomItemId($item);

        // Handle frame creator item
        if ($customItemId === self::FRAME_CREATOR_ITEM_ID) {
            // Cancel the event to prevent normal item use
            $event->cancel();

            // Get the player's session from CoreAPI
            $session = CoreAPI::getInstance()->getSessionManager()->getSessionByPlayer($player);
            if ($session === null) {
                return;
            }

            // Get the animation component
            $component = $session->getComponent("blockanimator:animation");
            if ($component === null || !$component instanceof AnimationSessionComponent) {
                return;
            }

            // Start a new frame
            $component->startFrame();

            // If this is the first frame, tell them they're starting a new animation
            if ($component->getFrameCount() === 0) {
                $player->sendMessage(TextFormat::GREEN . "Started recording a new animation. Make changes and use this item again to record the next frame.");
            } else {
                $player->sendMessage(TextFormat::GREEN . "Frame " . $component->getFrameCount() . " recorded. Make changes and use this item again for the next frame, or use /blockanimator complete <name> to finish.");
            }
        }
        // Handle undo/redo item (right-click = redo)
        else if ($customItemId === self::UNDO_REDO_ITEM_ID) {
            // Cancel the event to prevent normal item use
            $event->cancel();

            // Get the player's session from CoreAPI
            $session = CoreAPI::getInstance()->getSessionManager()->getSessionByPlayer($player);
            if ($session === null) {
                return;
            }

            // Get the animation component
            $component = $session->getComponent("blockanimator:animation");
            if ($component === null || !$component instanceof AnimationSessionComponent) {
                return;
            }

            // Check if recording is active
            if (!$component->isRecording()) {
                $player->sendMessage(TextFormat::RED . "You are not currently recording an animation.");
                return;
            }

            // Attempt to redo the last undone change
            if ($component->redo()) {
                $player->sendMessage(TextFormat::GREEN . "Successfully redid the last undone change.");
            } else {
                $player->sendMessage(TextFormat::RED . "Nothing to redo.");
            }
        }
    }
}
