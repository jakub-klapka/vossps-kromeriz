/**
 * gulp -> production one-time compile
 * gulp dev --dev -> watcher with disabled minification
 */

var gulp = require( 'gulp' ),
	sass = require( 'gulp-ruby-sass' ),
	autoprefixer = require( 'gulp-autoprefixer' ),
	plumber = require( 'gulp-plumber' ),
	imagemin = require( 'gulp-imagemin' ),
	svg_store = require( 'gulp-svgstore' ),
	filter = require( 'gulp-filter' ),
	uglify = require( 'gulp-uglify' ),
	livereload = require( 'gulp-livereload' ),
	browserSync = require('browser-sync').create(),
	gulpif = require( 'gulp-if' ),
	args = require( 'yargs' ).argv;

var plumber_config = {
	errorHandler: function (err) {
		console.log(err.toString());
		this.emit('end');
	}
};

var is_dev = ( args.dev !== undefined ) ? true : false;

/*
CSS
 */
gulp.task( 'sass', function(){
	var maps_filter = filter( [ '*', '!*.map' ] );
	return gulp.src( 'src/sass/**/*.scss', { base: 'src/sass' } )
		.pipe( plumber( plumber_config ) )
		.pipe( gulpif( !is_dev, sass( {
			style: 'compressed'
		} ) ) )
		.pipe( gulpif( is_dev, sass( {
			style: 'expanded'
		} ) ) )
		.pipe( maps_filter )
		.pipe( autoprefixer() )
		.pipe( gulp.dest( 'build/css' ) )
		.pipe( gulp.dest( '../wp/wp-content/themes/vossps_km/assets/css' ) )
		.pipe(browserSync.stream());
} );
gulp.task( 'sass_watch', function(){
	gulp.watch( 'src/sass/**/*.scss', [ 'sass' ] );
} );


/*
Images
 */
gulp.task( 'images', function(){
	return gulp.src( 'src/images/**/*', { base: 'src/images' } )
		.pipe( plumber( plumber_config ) )
		.pipe( imagemin( { progressive: true } ) )
		.pipe( gulp.dest( 'build/images' ) )
		.pipe( gulp.dest( '../wp/wp-content/themes/vossps_km/assets/images' ) );
} );
gulp.task( 'images_watch', function(){
	gulp.watch( 'src/images/**/*', [ 'images' ] );
} );

/*
SVG
 */
gulp.task( 'svg', function(){
	return gulp.src( 'src/svg_sprite/*.svg' )
		.pipe( plumber( plumber_config ) )
		.pipe( imagemin() )
		.pipe( svg_store( {
			fileName: 'svg_sprite.svg',
			prefix: 'icon-'
		} ) )
		.pipe( gulp.dest( 'build/images' ) )
		.pipe( gulp.dest( '../wp/wp-content/themes/vossps_km/assets/images' ) );
} );
gulp.task( 'svg_watch', function(){
	gulp.watch( 'src/svg_sprite/**/*', [ 'svg' ] );
} );


/*
Fonts
 */
gulp.task( 'fonts', function() {
	return gulp.src( 'src/fonts/**/*', { base: 'src/fonts' } )
		.pipe( gulp.dest( 'build/fonts' ) )
		.pipe( gulp.dest( '../wp/wp-content/themes/vossps_km/assets/fonts' ) );
} );
gulp.task( 'fonts_watch', function(){
	gulp.watch( 'src/fonts/**/*', [ 'fonts' ] );
} );


/*
JS
 */
gulp.task( 'js', function() {
	return gulp.src( 'src/js/**/*.js', { base: 'src/js' } )
		.pipe( plumber( plumber_config ) )
		.pipe( gulpif( !is_dev, uglify() ) )
		.pipe( gulp.dest( 'build/js' ) )
		.pipe( gulp.dest( '../wp/wp-content/themes/vossps_km/assets/js' ) );
} );
gulp.task( 'js-watch', function() {
	gulp.watch( 'src/js/**/*', [ 'js-watch-reloader' ] );
} );
gulp.task('js-watch-reloader', ['js'], function (done) {
	browserSync.reload();
	done();
});

/*
Browsersync
 */
gulp.task('browser-sync', [ 'sass', 'js' ], function() {
	browserSync.init({
		server: {
			baseDir: "./"
		},
		startPath: 'src/layout.html'
	});

	gulp.watch( [ "src/*.html", 'build/images/**/*', 'build/fonts/**/*' ]).on('change', browserSync.reload);
});

/*
Tasks
 */
gulp.task( 'default', [ 'sass', 'images', 'svg', 'fonts', 'js' ] );
gulp.task( 'dev', [ 'sass_watch', 'images_watch', 'svg_watch', 'fonts_watch', 'browser-sync', 'js-watch' ] );