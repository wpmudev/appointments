var gulp = require('gulp');
var git = require('gulp-git');

gulp.task('git', function(){
    git.checkout('master', function (err) {
        if (err) throw err;
    });
});