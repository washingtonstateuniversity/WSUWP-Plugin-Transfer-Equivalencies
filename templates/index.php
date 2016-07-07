<?php

get_header();

?>

<main class="">

<?php get_template_part( 'parts/headers' ); ?>


<section class="row single gutter pad-ends">

	<div class="column one">

		<?php
		$results_per_page = 20;

		$institutions_args = array(
			'taxonomy' => 'tce_institution',
		);

		if ( isset( $_GET['alpha'] ) ) {
			$alpha_index = get_terms( $institutions_args );
			$institutions = array();
			$alpha_total = 0;

			foreach ( $alpha_index as $institution ) {
				if ( isset( $_GET['alpha'] ) && sanitize_text_field( $_GET['alpha'] ) === strtolower( $institution->name[0] ) ) {
					$institutions[] = $institution;
					$alpha_total++;
				}
			}

			$slice_start = is_paged() ? ( get_query_var( 'paged' ) - 1 ) * $results_per_page : 0;
			$slice_end = $slice_start + $results_per_page;

			$institutions = array_slice( $institutions, $slice_start, $slice_end );
		} else {
			if ( isset( $_GET['institution'] ) ) {
				$institutions_args['name__like'] = sanitize_text_field( $_GET['institution'] );
			} else {
				$institutions_args['number'] = $results_per_page;

				if ( is_paged() ) {
					$institutions_args['offset'] = ( get_query_var( 'paged' ) - 1 ) * $results_per_page;
				}
			}

			$institutions = get_terms( $institutions_args );
		}

		if ( isset( $_GET['institution'] ) && empty( $institutions ) ) {
			?><p>Sorry, we couldn't find any institutions that match <?php echo esc_html( $_GET['institution'] ); ?>. Please try another search or browse using the A-Z index.</p><?php
		}
		?>

		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<div>
				<label class="screen-reader-text" for="instutition-search">Search for:</label>
				<input type="text" value="" name="institution" id="instutition-search">
				<input type="submit" value="Search">
			</div>
		</form>

		<ul id="institution-index">
		<?php foreach ( range( 'A', 'Z' ) as $index ) { ?>
			<?php $current = ( isset( $_GET['alpha'] ) && sanitize_text_field( $_GET['alpha'] ) === strtolower( $index ) ) ? ' class="current"' : ''; ?>
			<?php $url = home_url( 'equivalencies/' ) . '?alpha=' . strtolower( $index ); ?>
			<li<?php echo $current; ?>><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $index ); ?></a></li>
		<?php } ?>
		</ul>

		<?php
		foreach ( $institutions as $institution ) {
			$link = get_term_link( $institution );
			$name = $institution->name;
			$location = array();
			$state = ( $state_code = get_term_meta( $institution->term_id, 'state_code', true ) ) ? $location[] = $state_code : '';
			$country = ( $country_code = get_term_meta( $institution->term_id, 'country_code', true ) ) ? $location[] = $country_code : '';
			$location = implode( ', ', $location );

			?><p><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a> <?php echo esc_html( $location ); ?></p><?php
		}
		?>

	</div>

</section>

<?php
// Remove this check if we include a limit on search results.
if ( ! isset( $_GET['institution'] ) ) {

	if ( isset( $_GET['alpha'] ) ) {
		$total = $alpha_total;
	} else {
		$total = wp_count_terms( array(
			'taxonomy' => 'tce_institution',
		) );
	}

	$big = 99164;
	$args = array(
		'base'         => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
		'format'       => 'page/%#%',
		'total'        => ceil( $total / $results_per_page ), // Provide the number of pages this query expects to fill.
		'current'      => max( 1, get_query_var( 'paged' ) ), // Provide either 1 or the page number we're on.
		'prev_text'    => __( '«' ),
		'next_text'    => __( '»' ),
	);
	?>
	<footer class="main-footer archive-footer">
		<section class="row single pager prevnext">
			<div class="column one">
				<?php echo paginate_links( $args ); ?>
			</div>
		</section>
	</footer>
	<?php
}
?>

	<?php get_template_part( 'parts/footers' ); ?>

</main>
<?php

get_footer();
