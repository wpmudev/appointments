var gulp = require('gulp');
var git = require('gulp-git');

gulp.task('git', function(){
    // git.checkout('master', function (err) {
    //     if (err) throw err;
    // });

    git.checkout( 'wporg-master', function(err) {
        if ( err ) {
            throw err;
        }

        console.log( 'Switched to wporg-master branch' );
        
    });
});