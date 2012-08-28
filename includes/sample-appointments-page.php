<?php
/*
Template Name: Sample Appointments+ Page
*/

/*
Also add these inside function.php of your theme to force css and js codes of the plugin:
function add_appointments_css_style() {
	if ( !class_exists( 'Appointments' ) )
		return;
	// You may add additional conditions here, e.g. load styles only for a certain page
	global $appointments;
	$appointments->load_scripts_styles( );
}
add_action( 'template_redirect', 'add_appointments_css_style' );

*/

get_header(); ?>

		<div id="primary">
			<div id="content" role="main">

				<?php while ( have_posts() ) : the_post(); ?>

					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<header class="entry-header">
							<h1 class="entry-title"><?php the_title(); ?></h1>
						</header><!-- .entry-header -->

						<div class="entry-content">
						<!-- You can add other shortcodes like the below sample -->
							<?php do_shortcode('[app_schedule]'); ?>
							<?php wp_link_pages( array( 'before' => '<div class="page-link"><span>' . __( 'Pages:' ) . '</span>', 'after' => '</div>' ) ); ?>
						</div><!-- .entry-content -->
						
						<footer class="entry-meta">
							<?php edit_post_link( __( 'Edit' ), '<span class="edit-link">', '</span>' ); ?>
						</footer><!-- .entry-meta -->
					</article><!-- #post-<?php the_ID(); ?> -->

					<?php comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_footer(); ?>