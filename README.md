# Multisite Actions Manager

Manage the actions of all multisites in a single installation.

## Features

- Run cron
- Clean cache
- Enable/Disable modules
- Put/retire site maintenance mode
- Execute custom drush commands
- Add new drush commands


## How this work

Register a new domain in "Manage - Estrucure - Domain list"  (admin/structure/domain-entity).

Manager the actions in "Manage - Configuration - Multisite Actions Manager" (admin/config/multisite-manager).

All action will be executed by cron, with no performance problems.

