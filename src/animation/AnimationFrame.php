<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\animation;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\math\Vector3;
use pocketmine\world\World;

/**
 * Represents a single frame in an animation
 */
class AnimationFrame {
    /** @var array<string, array> Map of position strings to block data */
    private array $blockStates = [];

    /**
     * Add a block state to the frame
     *
     * @param Vector3 $position
     * @param Block $block
     */
    public function addBlockState(Vector3 $position, Block $block): void {
        $posKey = $this->getPositionKey($position);
        $this->blockStates[$posKey] = [
            'stateId' => $block->getStateId(),
            'x' => $position->getX(),
            'y' => $position->getY(),
            'z' => $position->getZ()
        ];
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
     * Get all block states in this frame
     *
     * @return array<string, array>
     */
    public function getBlockStates(): array {
        return $this->blockStates;
    }

    /**
     * Get the number of blocks in this frame
     *
     * @return int
     */
    public function getBlockCount(): int {
        return count($this->blockStates);
    }

    /**
     * Apply this frame to a world
     *
     * @param World $world
     */
    public function apply(World $world): void {
        foreach ($this->blockStates as $posKey => $blockData) {
            $position = new Vector3($blockData['x'], $blockData['y'], $blockData['z']);

            // Create the block from saved state ID
            $block = RuntimeBlockStateRegistry::getInstance()->fromStateId($blockData['stateId']);

            // Set the block in the world
            $world->setBlock($position, $block);
        }
    }

    /**
     * Convert the frame to an array for storage
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'blockStates' => $this->blockStates
        ];
    }

    /**
     * Create a frame from an array
     *
     * @param array $data
     * @return AnimationFrame|null
     */
    public static function fromArray(array $data): ?AnimationFrame {
        if (!isset($data['blockStates'])) {
            return null;
        }

        $frame = new AnimationFrame();

        // Convert old format (id + meta) to new format (stateId) if needed
        $blockStates = $data['blockStates'];
        foreach ($blockStates as $posKey => $blockData) {
            // Check if we need to convert from old format
            if (isset($blockData['id'], $blockData['meta']) && !isset($blockData['stateId'])) {
                // We can't reliably convert old format to new format
                // Just remove this block from the animation
                unset($blockStates[$posKey]);
            }
        }

        $frame->blockStates = $blockStates;

        return $frame;
    }
}
