# Appointments +

## Install
You need nodejs + npm installed
- `npm install`

## What does do this install?
- Install npm dependencies
- Install Bower dependencies
- Executes Webpack for the first time

## npm tasks
Everything (except unit tests) should be handled by npm. Note that you don't need to interact with Grunt in a direct way.

* **`npm run watch`**: Start watching JS files.
* **`npm run release`**: Package everything to be released (DEV and wp.org version, both, yeah!). **note**: You must be on `master` branch to use this command.
* **`npm run deploy-svn`**: Once build is done, this task will download Appointments SVN repository from wp.org and generate a new tag. Then follow instructions on screen.

There are some other commands that allows you to manually generate wp.org or DEV packages. These are: 
* **`npm run build:main`**: Generates the pro version.
* **`npm run build:wporg`**: Generates the wp.org version.

You don't need to be in any specific branch to execute these. Useful if you need to generate betas.

## Versioning

Follow semantic versioning [http://semver.org/](http://semver.org/) as `package.json` won't work otherwise. That's it:

- `X.X` for mayor versions
- `X.X.X` for minor versions
- `X.X[.X]-rc.1` for release candidates
- `X.X[.X]-beta.1` for betas
- `X.X[.X]-alpha.1` for alphas

## JavaScript and Stylesheets
All JS files that are processed by Webpack are located at `_src` folder.

`webpack.config.js` includes the setup to watch and process JS files.

Run `npm run watch` to start watching files. You only need to modify `.dev.js` files and Webpack will do the rest by processing them in `.js`.

For instance: `_src/shortcodes/js/app-confirmation.dev.js` will be processed to `includes/shortcodes/js/app-confirmation.js` automatically when Webpack is watching.

Stylesheets are slightly different. At the moment only `_src/admin/css/unslider` is processed by Webpack. But Webpack only understands JS so `_src/admin/css/unslider.js` will load css files, then Webpack processes that JS and converti it to CSS. Please, take a look at `webpack.config.js` file comments. 

### JavaScript Source Maps
Webpack always compress files so it's impossible to debug. Fortunately Webpack will generate source maps for you as `.js.map` files.

If you need to set a breakpoint by using browser developer tools do not search the loaded file but instead search for `webpack://` source. You should see the development version of the script listed there. Now you can set breakpoints in there.

## Branches
- `development`: Use this branch to develop things
- `master`: Merge `development` into this one when a new version is ready

## How to release pro and free versions then?
The release process always must start on `master` branch. Once latest changes are merged into it, with just a command you'll be able to grab all the files and make the releases.

wp.org and DEV versions are exactly the same but wp.org does not include `includes/pro` or `includes/external/wpmudev-dash` folder.

Follow these steps to make the release:

* Edit `changelog.txt` file. **Trick**: use `npm run log` to generate the log since a given date and save it into `log.log` file.
* Once you have your `development` branch ready, merge into `master`. Do not forget to update the version number. Always with format X.X.X. You'll need to update in `appointments.php` and also `package.json`
* Execute `npm run release`. zips and files will be generated in `build` folder.
* At the end of the process some instructions will be displayed. Follow them to get the version and upload to DEV.
* Time to release wp.org version. Execute `npm run deploy-svn`. the script located at `tools/deploy-to-svn.sh` will download the SVN repository to `./build/appointments` and sync the new trunk, generate a new tag and display some instructions to finish the process by yourself. Piece of cake!

The release process always must start on `master` branch. Once latest changes are merged into it, with just a command you'll be able to grab all the files and make the releases.

## Unit Tests
All tests are under `tests` folder. It's a good idea to add a test for any refactoring task or for any bug found that you fix. Unit Testing is not always possible but try it.
In order to execute tests you'll need to install PHP Unit and MySQL + PHP installed and running. A Vagrant like Varying Vagrant Vagrant box would be the best way to execute the tests
 
* Execute the script that will download the latest WordPress version and the WordPress Unit Tests Bootstrap `./bin/install-wp-tests.sh [database_name] [database_user] [database_password] localhost latest` 
 
* Execute all tests by using `phpunit` or execute a group tests by using `phpunit --group [group-name]` for example `phpunit --group timetables` 
