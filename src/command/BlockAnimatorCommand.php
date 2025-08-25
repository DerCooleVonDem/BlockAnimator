<?php

declare(strict_types=1);

namespace JonasWindmann\BlockAnimator\command;

use JonasWindmann\BlockAnimator\command\subcommand\AutorunSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\CompleteSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\DeleteSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\FrameSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\ItemSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\ListSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\RedoSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\StartSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\StopSubCommand;
use JonasWindmann\BlockAnimator\command\subcommand\UndoSubCommand;
use JonasWindmann\BlockAnimator\Main;
use JonasWindmann\CoreAPI\command\BaseCommand;

/**
 * Main command for the BlockAnimator plugin
 */
class BlockAnimatorCommand extends BaseCommand {
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
            "/blockanimator <subcommand> [args...]",
            ["ba"],
            "blockanimator.command"
        );

        $this->plugin = $plugin;

        // Register subcommands
        $this->registerSubCommands([
            new FrameSubCommand(),
            new CompleteSubCommand($plugin),
            new StartSubCommand(),
            new StopSubCommand(),
            new ListSubCommand(),
            new DeleteSubCommand(),
            new AutorunSubCommand(),
            new ItemSubCommand(),
            new UndoSubCommand(),
            new RedoSubCommand()
        ]);
    }

}
