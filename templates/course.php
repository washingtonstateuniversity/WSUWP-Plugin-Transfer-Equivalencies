<?php

get_header();

?>

<main class="">

<?php get_template_part( 'parts/headers' ); ?>


<section class="row single gutter pad-ends">

	<div class="column one">

		<?php while ( have_posts() ) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<header class="article-header">
					<h1 class="article-title"><?php the_title(); ?></h1>
				</header>

				<div class="article-body">
					<?php
						if ( $note = get_post_meta( $post->ID, '_tce_note', true ) ) {
							echo esc_html( $note );
						}
					?>
				</div>

			</article>

		<?php endwhile; ?>

	</div>

</section>

	<?php get_template_part( 'parts/footers' ); ?>

</main>
<?php

get_footer();
