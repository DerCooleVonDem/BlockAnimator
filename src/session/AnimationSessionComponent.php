<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\session;

use JonasWindmann\BlockAnimator\animation\AnimationFrame;
use JonasWindmann\CoreAPI\session\BasePlayerSessionComponent;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

/**
 * Session component for managing animation recording
 */
class AnimationSessionComponent extends BasePlayerSessionComponent {
    /** @var bool */
    private bool $isRecording = false;

    /** @var AnimationFrame[] */
    private array $frames = [];

    /** @var array<string, Block> Map of position keys to blocks for the current world state */
    private array $currentWorldState = [];

    /** @var array<array<string, Block>> Stack of previous world states for undo functionality */
    private array $undoStack = [];

    /** @var array<array<string, Block>> Stack of redone world states for redo functionality */
    private array $redoStack = [];

    /**
     * Get the unique identifier for this component
     */
    public function getId(): string {
        return "blockanimator:animation";
    }

    /**
     * Called when the component is added to a session
     */
    public function onCreate(): void {
        // Nothing to do here
    }

    /**
     * Called when the component is removed from a session
     */
    public function onRemove(): void {
        // Cancel any recording in progress
        $this->cancelRecording();
    }

    /**
     * Check if the player is recording
     * 
     * @return bool
     */
    public function isRecording(): bool {
        return $this->isRecording;
    }

    /**
     * Start recording a new frame
     * 
     * @return bool
     */
    public function startFrame(): bool {
        // If not already recording, initialize
        if (!$this->isRecording) {
            $this->isRecording = true;
            $this->frames = [];
            $this->currentWorldState = [];
            $this->undoStack = [];
            $this->redoStack = [];
            return true;
        }

        // Push current world state to undo stack before creating a new frame
        if (!empty($this->currentWorldState)) {
            $this->undoStack[] = $this->currentWorldState;
            // Clear redo stack when a new action is performed
            $this->redoStack = [];
        }

        // Create a new frame from the current world state
        $frame = new AnimationFrame();

        // Add all blocks in the current world state to the frame
        foreach ($this->currentWorldState as $posKey => $block) {
            $parts = explode(":", $posKey);
            $position = new Vector3((float)$parts[0], (float)$parts[1], (float)$parts[2]);
            $frame->addBlockState($position, $block);
        }

        // Add the frame to our list
        $this->frames[] = $frame;

        // Reset the current world state for the next frame
        $this->currentWorldState = [];

        return true;
    }

    /**
     * Record a block change
     * 
     * @param Position $position
     * @param Block $block
     */
    public function recordBlockChange(Position $position, Block $block): void {
        if (!$this->isRecording) {
            return;
        }

        // Store the block in the current world state
        $posKey = $this->getPositionKey($position);
        $this->currentWorldState[$posKey] = $block;
    }

    /**
     * Get a unique key for a position
     * 
     * @param Vector3 $position
     * @return string
     */
    private function getPositionKey(Vector3 $position): string {
        return $position->getX() . ":" . $position->getY() . ":" . $position->getZ();
    }

    /**
     * Complete the recording and get all frames
     * 
     * @return AnimationFrame[]
     */
    public function completeRecording(): array {
        // If we have pending changes, create a final frame
        if (!empty($this->currentWorldState)) {
            $this->startFrame();
        }

        $frames = $this->frames;

        // Reset the session
        $this->isRecording = false;
        $this->frames = [];
        $this->currentWorldState = [];
        $this->undoStack = [];
        $this->redoStack = [];

        return $frames;
    }

    /**
     * Cancel the recording
     */
    public function cancelRecording(): void {
        $this->isRecording = false;
        $this->frames = [];
        $this->currentWorldState = [];
        $this->undoStack = [];
        $this->redoStack = [];
    }

    /**
     * Get the number of frames recorded
     * 
     * @return int
     */
    public function getFrameCount(): int {
        return count($this->frames);
    }

    /**
     * Get the frames recorded so far
     * 
     * @return AnimationFrame[]
     */
    public function getFrames(): array {
        return $this->frames;
    }

    /**
     * Undo the last recorded world state
     * 
     * @return bool True if undo was successful, false if there's nothing to undo
     */
    public function undo(): bool {
        if (empty($this->undoStack)) {
            return false; // Nothing to undo
        }

        // Push current world state to redo stack
        if (!empty($this->currentWorldState)) {
            $this->redoStack[] = $this->currentWorldState;
        }

        // Pop the last world state from the undo stack
        $this->currentWorldState = array_pop($this->undoStack);

        // Apply the changes to the world
        $this->applyCurrentWorldState();

        return true;
    }

    /**
     * Apply the current world state to the actual world
     */
    private function applyCurrentWorldState(): void {
        $player = $this->getPlayer();
        if ($player === null || !$player->isConnected()) {
            return;
        }

        $world = $player->getWorld();

        foreach ($this->currentWorldState as $posKey => $block) {
            $parts = explode(":", $posKey);
            $position = new Vector3((float)$parts[0], (float)$parts[1], (float)$parts[2]);

            // Set the block in the world
            $world->setBlock($position, $block);
        }
    }

    /**
     * Redo the last undone world state
     * 
     * @return bool True if redo was successful, false if there's nothing to redo
     */
    public function redo(): bool {
        if (empty($this->redoStack)) {
            return false; // Nothing to redo
        }

        // Push current world state to undo stack
        if (!empty($this->currentWorldState)) {
            $this->undoStack[] = $this->currentWorldState;
        }

        // Pop the last world state from the redo stack
        $this->currentWorldState = array_pop($this->redoStack);

        // Apply the changes to the world
        $this->applyCurrentWorldState();

        return true;
    }

    /**
     * Check if undo is available
     * 
     * @return bool
     */
    public function canUndo(): bool {
        return !empty($this->undoStack);
    }

    /**
     * Check if redo is available
     * 
     * @return bool
     */
    public function canRedo(): bool {
        return !empty($this->redoStack);
    }
}
