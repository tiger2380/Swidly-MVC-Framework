# Welcome to the smallest and simplest PHP MVC Framework

This is a small, simple yet powerful PHP MVC Framework built with PHP. No installation/compower require. Oh yeah, it's free!! 😊

1. First, download the framework by downloading the zip or cloning the repo.
1. Run composer... oh right, no composer requires!
1. That's it. To configure, Open [App/Core/Settings.php](App/Core/Settings.php) and fill out your server information
1. Create routes, add controllers, views, models.
1. Also supported, templating and multi-language. You can edit the file at: [App/Core/lang/en.json](App/lang/en.json)

## Configuration
The configuration settings are stored under [App/Core/Settings.php](App/Core/Settings.php)
You can access the settings in your code by: `App::getConfig('db::host')`. You can also set a default value by: `App::getConfig('db::host', 'localhost')`.

## Routing

