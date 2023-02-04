TheGamesDB.net
==============

https://thegamesdb.net

This site serves as a frontend to a complete database of video games.

The site includes artwork and metadata that can be incorporated into various HTPC software and plug-ins via our API.



Getting started
---------------

To get the Website and the API up and running you need a Webserver with a MySQL-Database.
You need to copy and rename two files in the include folder which contains debug and database settings.

- config.class.example.php -> config.class.php
- db.config.template.php -> db.config.php

Development Environment
---------------

There is a docker compose file which provides a full development environment with xdebug capabilities.

**Do not use this development environment for production purposes!**

To start this environment you simply run `docker compose up`.
You can navigate to http://localhost:8080/ to browse the website or http://localhost:8088/ to access the api.

This will also start a mariadb sql server which is configured to be accessible on hostname `localhost` with port `13306`.
Username is `root` and passwort is `abc123`.

A simple database export will be inserted (see file docker/sql/init.sql) to get you started if you run the up command the first time.

If you just cloned this repository you'll need to install all required composer dependencies. Run this command and you are ready to go `docker compose exec tgdb_api composer install`.
Or just run `docker compose exec tgdb_api bash` to access the root shell of the docker server.
