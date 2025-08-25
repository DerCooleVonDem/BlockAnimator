<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\animation;

use JonasWindmann\BlockAnimator\Main;
use JonasWindmann\BlockAnimator\task\AnimationPlaybackTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

/**
 * Manages all animations
 */
class AnimationManager {
    use SingletonTrait;

    /** @var Main */
    private Main $plugin;

    /** @var Animation[] */
    private array $animations = [];

    /**
     * AnimationManager constructor
     *
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        self::setInstance($this);

        // Create animations directory if it doesn't exist
        $animationsDir = $plugin->getDataFolder() . $plugin->getConfig()->get("storage.animations_dir", "animations");
        if (!is_dir($animationsDir)) {
            mkdir($animationsDir, 0777, true);
        }

        // Load all animations
        $this->loadAnimations();
    }

    /**
     * Load all animations from disk
     */
    public function loadAnimations(): void {
        $animationsDir = $this->plugin->getDataFolder() . $this->plugin->getConfig()->get("storage.animations_dir", "animations");

        // Check for old .yml files and convert them to .json
        $this->convertYamlToJson($animationsDir);

        // Get all .json files in the animations directory
        $files = glob($animationsDir . "/*.json");
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            $config = new Config($file, Config::JSON);
            $data = $config->getAll();

            // Skip if missing required data
            if (!isset($data['name'], $data['world'])) {
                $this->plugin->getLogger()->warning("Animation file " . basename($file) . " is missing required data");
                continue;
            }

            // Get the world
            $worldName = $data['world'];
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);

            // Skip if world doesn't exist
            if ($world === null) {
                $this->plugin->getLogger()->warning("Animation " . $data['name'] . " references non-existent world: " . $worldName);
                continue;
            }

            // Create the animation
            $animation = Animation::fromArray($data, $world);
            if ($animation !== null) {
                $this->animations[$animation->getName()] = $animation;
                $this->plugin->getLogger()->debug("Loaded animation: " . $animation->getName());
            }
        }

        $this->plugin->getLogger()->info("Loaded " . count($this->animations) . " animations");
    }

    /**
     * Save an animation to disk
     *
     * @param Animation $animation
     * @return bool
     */
    public function saveAnimation(Animation $animation): bool {
        $animationsDir = $this->plugin->getDataFolder() . $this->plugin->getConfig()->get("storage.animations_dir", "animations");
        $file = $animationsDir . "/" . $animation->getName() . ".json";

        $config = new Config($file, Config::JSON);
        $config->setAll($animation->toArray());
        $config->save();

        return true;
    }

    /**
     * Create a new animation
     *
     * @param string $name
     * @param World $world
     * @param int $frameDelay
     * @return Animation
     */
    public function createAnimation(string $name, World $world, int $frameDelay = 10): Animation {
        $animation = new Animation($name, $world, $frameDelay);
        $this->animations[$name] = $animation;

        // Auto-save if enabled
        if ($this->plugin->getConfig()->get("storage.auto_save", true)) {
            $this->saveAnimation($animation);
        }

        return $animation;
    }

    /**
     * Delete an animation
     *
     * @param string $name
     * @return bool
     */
    public function deleteAnimation(string $name): bool {
        if (!isset($this->animations[$name])) {
            return false;
        }

        // Stop the animation if it's playing
        $animation = $this->animations[$name];
        if ($animation->isPlaying()) {
            $this->stopAnimation($name);
        }

        // Remove from memory
        unset($this->animations[$name]);

        // Remove from disk
        $animationsDir = $this->plugin->getDataFolder() . $this->plugin->getConfig()->get("storage.animations_dir", "animations");
        $file = $animationsDir . "/" . $name . ".json";
        if (file_exists($file)) {
            unlink($file);
        }

        // Also check for old YAML file (just in case)
        $yamlFile = $animationsDir . "/" . $name . ".yml";
        if (file_exists($yamlFile)) {
            unlink($yamlFile);
        }

        return true;
    }

    /**
     * Get an animation by name
     *
     * @param string $name
     * @return Animation|null
     */
    public function getAnimation(string $name): ?Animation {
        return $this->animations[$name] ?? null;
    }

    /**
     * Get all animations
     *
     * @return Animation[]
     */
    public function getAnimations(): array {
        return $this->animations;
    }

    /**
     * Start playing an animation
     *
     * @param string $name
     * @param int|null $frameDelay Override the animation's frame delay
     * @return bool
     */
    public function startAnimation(string $name, ?int $frameDelay = null): bool {
        $animation = $this->getAnimation($name);
        if ($animation === null) {
            return false;
        }

        // Don't start if already playing
        if ($animation->isPlaying()) {
            return false;
        }

        // Set custom frame delay if provided
        if ($frameDelay !== null) {
            $animation->setFrameDelay($frameDelay);
        }

        // Reset to first frame
        $animation->setCurrentFrame(0);

        // Mark as playing
        $animation->setPlaying(true);

        // Start the playback task
        $task = new AnimationPlaybackTask($this->plugin, $animation);
        $taskHandler = $this->plugin->getScheduler()->scheduleRepeatingTask($task, $animation->getFrameDelay());
        $animation->setTaskHandler($taskHandler);

        return true;
    }

    /**
     * Stop playing an animation
     *
     * @param string $name
     * @return bool
     */
    public function stopAnimation(string $name): bool {
        $animation = $this->getAnimation($name);
        if ($animation === null) {
            return false;
        }

        // Not playing
        if (!$animation->isPlaying()) {
            return false;
        }

        // Cancel the task
        $taskHandler = $animation->getTaskHandler();
        if ($taskHandler !== null) {
            $taskHandler->cancel();
            $animation->setTaskHandler(null);
        }

        // Mark as not playing
        $animation->setPlaying(false);

        return true;
    }

    /**
     * Convert old YAML animation files to JSON format
     *
     * @param string $animationsDir
     */
    private function convertYamlToJson(string $animationsDir): void {
        // Get all .yml files in the animations directory
        $files = glob($animationsDir . "/*.yml");
        if (!is_array($files) || empty($files)) {
            return;
        }

        $this->plugin->getLogger()->info("Found " . count($files) . " old YAML animation files to convert to JSON");

        foreach ($files as $file) {
            // Load the YAML file
            $config = new Config($file, Config::YAML);
            $data = $config->getAll();

            // Skip if missing required data
            if (!isset($data['name'], $data['world'])) {
                $this->plugin->getLogger()->warning("Animation file " . basename($file) . " is missing required data, skipping conversion");
                continue;
            }

            // Create a new JSON file with the same name
            $jsonFile = str_replace(".yml", ".json", $file);
            $jsonConfig = new Config($jsonFile, Config::JSON);
            $jsonConfig->setAll($data);
            $jsonConfig->save();

            // Delete the old YAML file
            unlink($file);

            $this->plugin->getLogger()->info("Converted animation file " . basename($file) . " to JSON format");
        }
    }
}
