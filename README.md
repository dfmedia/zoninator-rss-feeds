# Zoninator RSS Feeds
This plugin provides RSS Feeds for each Zoninator Zone.

## Installation and Usage
Install and activate the plugin like any other WordPress plugin, then flush the permalinks.

#### Usage
Each Zone will have an RSS 2.0 feed at `site.com/feed/zoninator_zones?zone={slug}`

So, if there's a "zone" with the slug of "sports", it can be accessed at `site.com/feed/zoninator_zones?zone=sports`

The feed is RSS 2.0, using the same RSS template as the default feeds WordPress outputs.

## Unit Tests
You must have PHPUnit installed locally, and have this plugin in the same directory as "Zoninator" for tests to run properly.

Once you've met these pre-reqs, you can install the test suite like so:

`bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]`


Then run `phpunit` and the tests will run.
