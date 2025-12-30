# GameRuleToggler

GameRuleToggler is a PocketMine-MP plugin that manages the following three Minecraft game rules:

* Locator Bar
* Immediate Respawn
* Show Coordinates

This plugin allows these features to be enabled on the server.

About 50 percent of this plugin was developed with the help of ChatGPT.

---

## Overview

When the plugin is installed, **all supported game rules are enabled by default**.

The standard Minecraft client-side toggle switches will also work.
However, only operators can use them.

All settings are stored **per player**.

This plugin may conflict with other game rule management plugins such as multiworld.

All settings can be edited via configuration files or in-game commands.

---

## Configuration

```yml
# GameRuleToggler - config.yml
# Controls server-wide behavior.
# Per-player settings are stored in json files.

version:
  generated: v1.0.0

rules:
  locator-bar:
    # Force this rule for all players
    force: false

    # Allow only operators to toggle this rule
    force-op-only: false

    # Value applied when force is enabled
    value: true

    # Default value for new players
    default: true

  show-coordinates:
    force: false
    force-op-only: false
    value: true
    default: true

  do-immediate-respawn:
    force: false
    force-op-only: false
    value: true
    default: true

autosave:
  # Interval in seconds (3600 = 1 hour)
  interval: 3600
```

At first glance, this looks like a normal plugin configuration.

Worried that editing config files manually might be painful?
No problem. This plugin supports **full in-game configuration**.

---

## Commands

### `/rtconfig`

Operator only.

Opens an in-game UI to edit server configuration dynamically.
You can choose which config section to edit.

---

### Configuration Options Explanation

* **Force**
  Prevents players from changing this setting.

* **OP only**
  Allows only operators to toggle the setting.
  This may not behave exactly as expected in all cases.

* **Forced value**
  Determines whether the rule is forced ON or OFF when Force is enabled.

* **Default value**
  The initial value assigned when a player joins the server for the first time.

---

### `/rtsetall`

Operator only.

Used to modify all player settings at once.

* **Set to on?**
  Applies the selected value to all currently online players.

* **Clear all saved settings**
  If enabled, resets all stored player configurations.

Screenshot example:
1767088190569.png

---

### `/rtsetting`

Player settings menu.

Allows players to change their own settings freely.
Forced rules are hidden and cannot be modified.

---

## Q&A

**Q: Why is the phar file so large?**
A: This plugin uses AwaitFormOptions, which is a powerful form API.
The current build size is about 50 KB.

**Q: Can I see the source code?**
A: [https://github.com/DaisukeDaisuke/GameRuleToggler](https://github.com/DaisukeDaisuke/GameRuleToggler)

**Q: Are unofficial forks supported?**
A: No. They are not supported and will not be supported in the future.

---

## Roadmap

### v1.0.0

* Server-wide game rule control
* Per-player settings
* In-game configuration UI
* OP-only and forced rules

### v2.0.0 (planned)

* World-based rule management
* Priority-based default settings
* Per-world and per-player rule layering