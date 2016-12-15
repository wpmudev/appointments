# Appointments +

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
- Release both versions.