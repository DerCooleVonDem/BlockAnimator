<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\task;

use JonasWindmann\BlockAnimator\animation\Animation;
use JonasWindmann\BlockAnimator\Main;
use pocketmine\scheduler\Task;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\sound\AnvilFallSound;

/**
 * Task to handle animation playback
 */
class AnimationPlaybackTask extends Task {
    /** @var Main */
    private Main $plugin;
    
    /** @var Animation */
    private Animation $animation;
    
    /**
     * AnimationPlaybackTask constructor
     * 
     * @param Main $plugin
     * @param Animation $animation
     */
    public function __construct(Main $plugin, Animation $animation) {
        $this->plugin = $plugin;
        $this->animation = $animation;
    }
    
    /**
     * Execute the task
     */
    public function onRun(): void {
        // If the animation is no longer playing, cancel the task
        if (!$this->animation->isPlaying()) {
            $this->getHandler()?->cancel();
            return;
        }
        
        // Get the current frame index
        $currentFrame = $this->animation->getCurrentFrame();
        
        // Get the frames
        $frames = $this->animation->getFrames();
        
        // If we've reached the end, loop back to the beginning
        if ($currentFrame >= count($frames)) {
            $currentFrame = 0;
            $this->animation->setCurrentFrame($currentFrame);
        }
        
        // Get the current frame
        $frame = $frames[$currentFrame];
        
        // Apply the frame
        $frame->apply($this->animation->getWorld());
        
        // Move to the next frame
        $this->animation->setCurrentFrame($currentFrame + 1);
    }
}
