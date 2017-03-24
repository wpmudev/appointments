# Appointments +

## Install
You need Composer and nodejs + npm installed
- `npm install`

## Branches
- `development`: Use this branch to develop things
- `master`: Merge `development` into this one when a new version is ready
- `wporg-master`: Use this one to release Appointments Lite but do not change things here, just merge into this branch.

**IMPORTANT**: Never, ever, ever, merge `wporg-master` into `development` or `master`.
Always use `development` to develop things and then merge this one into `wporg-master`.

The difference between `wporg-master` and the rest is the folder `includes/pro`. Due to Git limitations,
whenever `development` or `master` are merged into `wporg-master`, this folder could appear again in `wporg-master`
which is bad because is important that this folder is not released along with Appointments Lite.

## How to release pro and free versions then?
- Once you have your `development` branch ready, merge into `master` and also in `wporg-master`
- Now execute `grunt build` in `master` and `wporg-master`. zip files will be generated in `build` folder
- Notice that `wporg-master` won't include `pro` folder in the build. You just need these files for SVN WordPress repository.

## What's the deal with pro folder?
The `pro` folder is code that is exclusive for WPMU DEV version. The `pro` folder must always extend the Lite version with WordPress hooks.
This way, you won't need to check differences between both versions and workflow is easier:
- Work on `development`
- Merge into `master` and build
- Merge master into `wporg-master` and remove `pro` folder if it appears again. Then build
- Copy what's inside the build folder to wp.org SVN repo.
- Make sure that free version plugin name is Appointments Lite
- Release both versions.

## Unit Tests
All tests are under `tests` folder. It's a good idea to add a test for any refactoring task or for any bug found that you fix. Unit Testing is not always possible but try it.
In order to execute tests you'll need to install PHP Unit and MySQL + PHP installed and running. A Vagrant like Varying Vagrant Vagrant box would be the best way to execute the tests 
1. Execute the script that will download the latest WordPress version and the WordPress Unit Tests Bootstrap `./bin/install-wp-tests.sh [database_name] [database_user] [database_password] localhost latest` 
 
2. Execute all tests by using `phpunit` or execute a group tests by using `phpunit --group [group-name]` for example `phpunit --group timetables` 
