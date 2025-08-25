# BlockAnimator

A powerful Minecraft Bedrock plugin for creating and playing block animations in your world.

## This project depends on https://github.com/DerCooleVonDem/CoreAPI

## Overview

BlockAnimator allows server administrators and builders to create frame-by-frame animations using blocks in the world. These animations can be saved, played back, and even configured to run automatically when the server starts.

## Features

- **Frame-by-Frame Animation**: Record block changes as frames and play them back as animations
- **Easy-to-Use Commands**: Simple commands for creating, playing, and managing animations
- **Frame Creator Item**: Special item for recording frames without commands
- **Autorun Support**: Configure animations to play automatically on server startup
- **Customizable Playback**: Adjust animation speed and other playback settings

## Installation

1. Download the latest version of BlockAnimator
2. Place the plugin in your server's `plugins` folder
3. Restart your server
4. Configure the plugin settings in `plugin_data/BlockAnimator/config.yml` if needed

## Usage

### Creating an Animation

1. Start recording frames with `/blockanimator frame` or use the frame creator item
2. Make block changes in the world to create your first frame
3. Record additional frames with `/blockanimator frame` or the frame creator item
4. Complete and save your animation with `/blockanimator complete <name>`

### Playing Animations

- Start an animation: `/blockanimator start <name> [speed]`
- Stop an animation: `/blockanimator stop <name>`
- List all animations: `/blockanimator list`

### Managing Animations

- Delete an animation: `/blockanimator delete <name>`
- Configure autorun: `/blockanimator autorun <name> <true|false>`
- Get the frame creator item: `/blockanimator item`

## Commands

| Command | Description | Permission |
|---------|-------------|------------|
| `/blockanimator frame` | Record a new animation frame | blockanimator.command.create |
| `/blockanimator complete <name>` | Complete and save the animation | blockanimator.command.create |
| `/blockanimator start <name> [speed]` | Start playing an animation | blockanimator.command.play |
| `/blockanimator stop <name>` | Stop a playing animation | blockanimator.command.play |
| `/blockanimator list` | List all animations | blockanimator.command |
| `/blockanimator delete <name>` | Delete an animation | blockanimator.command.delete |
| `/blockanimator autorun <name> <true|false>` | Set animation to run on server startup | blockanimator.command.autorun |
| `/blockanimator item` | Get a frame creator item | blockanimator.command.item |

## Permissions

| Permission | Description | Default |
|------------|-------------|---------|
| blockanimator.command | Allows using the BlockAnimator commands | op |
| blockanimator.command.create | Allows creating animations | op |
| blockanimator.command.play | Allows playing animations | op |
| blockanimator.command.delete | Allows deleting animations | op |
| blockanimator.command.item | Allows getting the frame creator item | op |
| blockanimator.command.autorun | Allows configuring animations to run on startup | op |

## Configuration

The plugin's configuration file (`config.yml`) allows you to customize various aspects of the plugin:

```yaml
# Default playback settings
playback:
  # Default ticks between frames (20 ticks = 1 second)
  default_frame_delay: 10
  # Maximum number of frames per animation
  max_frames: 100
  # Whether to show particles during playback
  show_particles: true
  # Whether to play sounds during playback
  play_sounds: true

# Storage settings
storage:
  # Directory to save animations in (relative to plugin_data folder)
  animations_dir: "animations"
  # Whether to auto-save animations when created
  auto_save: true

# Autorun settings
autorun:
  # Whether to enable autorun functionality
  enabled: true
  # Delay in seconds before starting autorun animations after server start
  startup_delay: 5

# Debug settings
debug:
  # Whether to enable debug logging
  enabled: false
  # Whether to log frame data (can be verbose)
  log_frames: false
```

## Tips and Best Practices

1. **Plan Your Animations**: Before recording, plan out what you want to animate
2. **Use Simple Animations First**: Start with simple animations before attempting complex ones
3. **Frame Rate**: Remember that lower frame delays mean faster animations
4. **World Selection**: Create animations in a separate world to avoid interference
5. **Backup Regularly**: Always back up your animations by copying the JSON files

## Troubleshooting

- **Animation Not Playing**: Ensure the world where the animation was created is loaded
- **Missing Frames**: Make sure you've recorded all necessary frames before completing
- **Performance Issues**: Reduce the number of blocks per frame or increase the frame delay

## License

This plugin is released under the MIT license.

## Credits

Developed by the JumpAndRun Team
