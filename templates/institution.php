<?php

get_header();

?>

<main class="">

<?php get_template_part( 'parts/headers' ); ?>


<section class="row single gutter pad-top">

	<div class="column one">

		<h1>Credits from <?php single_term_title(); ?></h1>

		<?php
			// Quick and dirty modification of the loop.
			global $query_string;
			query_posts( $query_string . '&posts_per_page=25&orderby=title&order=ASC' );
		?>

		<?php while ( have_posts() ) : the_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<header class="article-header">
				<h2 class="article-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
			</header>

			<?php $course = explode( '(', get_post_meta( $post->ID, '_tce_internal_courses', true ) ); ?>

			<div class="tce-internal-course">
				<p><?php echo $course[0]; ?></p>
			</div>

			<div class="tce-ucore">
				<p><?php
					if ( array_key_exists( 1, $course ) ) echo rtrim( $course[1], ')' );
				?></p>
			</div>

		</article>

		<?php endwhile; // end of the loop. ?>

	</div>

</section>

<?php
/* @type WP_Query $wp_query */
global $wp_query;

$big = 99164;
$args = array(
	'base'         => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
	'format'       => 'page/%#%',
	'total'        => $wp_query->max_num_pages, // Provide the number of pages this query expects to fill.
	'current'      => max( 1, get_query_var( 'paged' ) ), // Provide either 1 or the page number we're on.
	'prev_text'    => __('«'),
	'next_text'    => __('»'),
);
?>
	<footer class="main-footer archive-footer">
		<section class="row single pager prevnextr pad-ends"">
			<div class="column one">
				<?php echo paginate_links( $args ); ?>
			</div>
		</section>
	</footer>

	<?php get_template_part( 'parts/footers' ); ?>

</main>
<?php

get_footer();
