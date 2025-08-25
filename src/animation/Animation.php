<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\animation;

use pocketmine\math\Vector3;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\World;

/**
 * Represents a block animation with multiple frames
 */
class Animation {
    /** @var string */
    private string $name;

    /** @var AnimationFrame[] */
    private array $frames = [];

    /** @var World */
    private World $world;

    /** @var int */
    private int $frameDelay;

    /** @var bool */
    private bool $isPlaying = false;

    /** @var int */
    private int $currentFrame = 0;

    /** @var TaskHandler|null */
    private ?TaskHandler $taskHandler = null;

    /** @var bool */
    private bool $runOnStartup = false;

    /**
     * Animation constructor
     *
     * @param string $name The name of the animation
     * @param World $world The world the animation is in
     * @param int $frameDelay Delay between frames in ticks
     */
    public function __construct(string $name, World $world, int $frameDelay = 10) {
        $this->name = $name;
        $this->world = $world;
        $this->frameDelay = $frameDelay;
    }

    /**
     * Get the name of the animation
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get the world the animation is in
     *
     * @return World
     */
    public function getWorld(): World {
        return $this->world;
    }

    /**
     * Get the delay between frames
     *
     * @return int
     */
    public function getFrameDelay(): int {
        return $this->frameDelay;
    }

    /**
     * Set the delay between frames
     *
     * @param int $frameDelay
     */
    public function setFrameDelay(int $frameDelay): void {
        $this->frameDelay = $frameDelay;
    }

    /**
     * Add a frame to the animation
     *
     * @param AnimationFrame $frame
     */
    public function addFrame(AnimationFrame $frame): void {
        $this->frames[] = $frame;
    }

    /**
     * Get all frames in the animation
     *
     * @return AnimationFrame[]
     */
    public function getFrames(): array {
        return $this->frames;
    }

    /**
     * Get the number of frames in the animation
     *
     * @return int
     */
    public function getFrameCount(): int {
        return count($this->frames);
    }

    /**
     * Check if the animation is currently playing
     *
     * @return bool
     */
    public function isPlaying(): bool {
        return $this->isPlaying;
    }

    /**
     * Set whether the animation is playing
     *
     * @param bool $isPlaying
     */
    public function setPlaying(bool $isPlaying): void {
        $this->isPlaying = $isPlaying;
    }

    /**
     * Get the current frame index
     *
     * @return int
     */
    public function getCurrentFrame(): int {
        return $this->currentFrame;
    }

    /**
     * Set the current frame index
     *
     * @param int $currentFrame
     */
    public function setCurrentFrame(int $currentFrame): void {
        $this->currentFrame = $currentFrame;
    }

    /**
     * Get the task handler for the animation playback
     *
     * @return TaskHandler|null
     */
    public function getTaskHandler(): ?TaskHandler {
        return $this->taskHandler;
    }

    /**
     * Set the task handler for the animation playback
     *
     * @param TaskHandler|null $taskHandler
     */
    public function setTaskHandler(?TaskHandler $taskHandler): void {
        $this->taskHandler = $taskHandler;
    }

    /**
     * Check if the animation should run on startup
     *
     * @return bool
     */
    public function shouldRunOnStartup(): bool {
        return $this->runOnStartup;
    }

    /**
     * Set whether the animation should run on startup
     *
     * @param bool $runOnStartup
     */
    public function setRunOnStartup(bool $runOnStartup): void {
        $this->runOnStartup = $runOnStartup;
    }

    /**
     * Convert the animation to an array for storage
     *
     * @return array
     */
    public function toArray(): array {
        $frames = [];
        foreach ($this->frames as $frame) {
            $frames[] = $frame->toArray();
        }

        return [
            'name' => $this->name,
            'world' => $this->world->getFolderName(),
            'frameDelay' => $this->frameDelay,
            'runOnStartup' => $this->runOnStartup,
            'frames' => $frames
        ];
    }

    /**
     * Create an animation from an array
     *
     * @param array $data
     * @param World $world
     * @return Animation|null
     */
    public static function fromArray(array $data, World $world): ?Animation {
        if (!isset($data['name'], $data['frameDelay'], $data['frames'])) {
            return null;
        }

        $animation = new Animation($data['name'], $world, $data['frameDelay']);

        // Set runOnStartup if it exists in the data
        if (isset($data['runOnStartup'])) {
            $animation->setRunOnStartup((bool)$data['runOnStartup']);
        }

        foreach ($data['frames'] as $frameData) {
            $frame = AnimationFrame::fromArray($frameData);
            if ($frame !== null) {
                $animation->addFrame($frame);
            }
        }

        return $animation;
    }
}
