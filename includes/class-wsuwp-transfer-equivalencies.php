<?php

class WSUWP_Transfer_Equivalencies {
	/**
	 * @var WSUWP_Transfer_Equivalencies
	 */
	private static $instance;

	/**
	 * @var string Slug for tracking the content type of a transfer credit equivalency.
	 */
	public $content_type_slug = 'tce_institution';

	/**
	 * @var int Maximum number of pages for a given institution query.
	 */
	public $max_num_pages;

	/**
	 * Maintain and return the one instance. Initiate hooks when called the first time.
	 *
	 * @since 0.0.1
	 *
	 * @return \WSUWP_Transfer_Equivalencies
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWP_Transfer_Equivalencies();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks to include.
	 *
	 * @since 0.0.1
	 */
	public function setup_hooks() {
		add_action( 'init', array( $this, 'register_content_type' ), 10 );
		add_action( 'add_meta_boxes_' . $this->content_type_slug, array( $this, 'add_meta_boxes' ), 10 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_shortcode( 'tce_search', array( $this, 'display_tce_search' ) );
		add_shortcode( 'tce_interface', array( $this, 'display_tce_interface' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_action( 'wp_ajax_nopriv_tce_navigation', array( $this, 'ajax_callback' ) );
		add_action( 'wp_ajax_tce_navigation', array( $this, 'ajax_callback' ) );
	}

	/**
	 * Register a content type to track information about transfer credit equivalencies.
	 */
	public function register_content_type() {
		$labels = array(
			'name' => 'Institutions',
			'singular_name' => 'Institution',
			'all_items' => 'Institutions',
			'view_item' => 'View Institution',
			'add_new_item' => 'Add New Institution',
			'edit_item' => 'Edit Institution',
			'update_item' => 'Update Institution',
			'search_items' => 'Search Institutions',
			'not_found' => 'No Institutions found',
			'not_found_in_trash' => 'No Institutions found in Trash',
		);

		$args = array(
			'labels' => $labels,
			'description' => 'Transfer institutions',
			'public' => false,
			'public_queryable' => true,
			'exclude_from_search' => true,
			'show_ui' => true,
			'show_in_admin_bar' => false,
			'menu_position' => 20,
			'menu_icon' => 'dashicons-migrate',
		);

		register_post_type( $this->content_type_slug, $args );
	}

	/**
	 * Add the metabox used to show and capture transfer credit equivalency information.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'tce-transfer-course-meta',
			'Institution Information',
			array( $this, 'display_transfer_course_meta_box' ),
			$this->content_type_slug,
			'normal',
			'high'
		);
	}

	/**
	 * Display the metabox used to show and capture transfer institution information.
	 *
	 * @param WP_Post $post Object for the post currently being edited.
	 */
	public function display_transfer_course_meta_box( $post ) {
		if ( $country_code = get_post_meta( $post->ID, '_tce_country_code', true ) ) {
			?><p><strong>Country Code:</strong> <?php echo esc_html( $country_code ); ?></p><?php
		}

		if ( $state_code = get_post_meta( $post->ID, '_tce_state_code', true ) ) {
			?><p><strong>State Code:</strong> <?php echo esc_html( $state_code ); ?></p><?php
		}

		if ( $transfer_id = get_post_meta( $post->ID, '_tce_transfer_source_id', true ) ) {
			?><p><strong>Transfer Source Id:</strong> <?php echo esc_html( $transfer_id ); ?></p><?php
		}
	}

	/**
	 * Add a submenu page for testing consumption of the myWSU API.
	 */
	public function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=' . $this->content_type_slug,
			'API Settings',
			'API Settings',
			'manage_options',
			'tce-api-settings',
			array( $this, 'tce_api_settings' )
		);
	}

	/**
	 * A temporary utility to test consuming data from the myWSU API.
	 */
	public function tce_api_settings() {
		?>
		<div class="wrap">
			<h2>API Settings</h2>
			<form method="post" action="">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Import</th>
						<td>
							<?php
							// @codingStandardsIgnoreStart
							if ( isset( $_POST['submit'] ) ) {
								$institutions = wp_remote_get( 'http://cstst.wsu.edu/PSIGW/RESTListeningConnector/PSFT_HR/TransferCreditEvalInst.v1/get/institution' );

								if ( is_wp_error( $institutions ) ) {
									return;
								}

								$institutions = wp_remote_retrieve_body( $institutions );
								$institutions = json_decode( $institutions );

								if ( ! isset( $institutions->InstitutionResponse->InstResponseComp ) ) {
									return;
								}

								$institutions = $institutions->InstitutionResponse->InstResponseComp;

								foreach ( $institutions as $institution ) {

									// Build the array of meta data.
									$institution_meta = array();

									// Any value in capturing the first two as taxonomic data?
									if ( $institution->CountryCode ) {
										$institution_meta['_tce_country_code'] = sanitize_text_field( $institution->CountryCode );
									}

									if ( $institution->StateCode ) {
										$institution_meta['_tce_state_code'] = sanitize_text_field( $institution->StateCode );
									}

									if ( $institution->TransferSourceId ) {
										$institution_meta['_tce_transfer_source_id'] = sanitize_text_field( $institution->TransferSourceId );
									}

									$institution_meta['_tce_alpha_key'] = sanitize_text_field( substr( $institution->TransferSourceDescr, 0, 1 ) );

									// Build the post to insert.
									$transfer_institution = array(
										'post_title' => sanitize_text_field( $institution->TransferSourceDescr ),
										'post_status' => 'publish',
										'post_type' => $this->content_type_slug,
										'meta_input' => $institution_meta,
									);

									// Insert the post.
									wp_insert_post( $transfer_institution );
								}
							} else {
								?><p>Import transfer institutions.</p><?php
							}
							// @codingStandardsIgnoreEnd
							?>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Go for it!' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display a text input for searching by institution name.
	 *
	 * @param array $atts List of attributes used for the shortcode.
	 *
	 * @return string Content to output.
	 */
	public function display_tce_search( $atts ) {
		$atts = shortcode_atts( array(
			'page_url' => '',
		), $atts );

		if ( ! $atts['page_url'] ) {
			return '';
		}

		ob_start();
		?>
		<form role="search" method="get" action="<?php echo esc_url( $atts['page_url'] ); ?>" class="tce-institution-search">
			<div>
				<label class="screen-reader-text" for="tce-institution-search">Search for institution by name:</label>
				<input type="search" value="" name="institution" id="tce-institution-search">
				<input type="submit" value="$">
			</div>
		</form>
		<?php
		$html = ob_get_contents();

		ob_end_clean();

		return $html;
	}

	/**
	 * Build the query for institutions.
	 *
	 * @param string	$search		Value from search form input.
	 * @param string	$index		Alphabetic index.
	 * @param int		$page		Pagination page number.
	 *
	 * @return WP_Query results.
	 */
	public function institution_query( $search = null, $index = null, $page = null ) {
		$results_per_page = 50;
		$args = array(
			'post_type' => $this->content_type_slug,
			'posts_per_page' => $results_per_page,
			'orderby' => 'title',
			'order' => 'ASC',
		);

		if ( isset( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		if ( isset( $index ) ) {
			$args['meta_key'] = '_tce_alpha_key';
			$args['meta_value'] = sanitize_text_field( $index );
		}

		if ( isset( $page ) ) {
			$args['offset'] = ( (int) sanitize_text_field( $page ) - 1 ) * $results_per_page;
		}

		$institution_query = new WP_Query( $args );

		if ( $institution_query->have_posts() ) {
			$institution_results = array();
			$institution_results[] = '<ul>';

			while ( $institution_query->have_posts() ) {
				$institution_query->the_post();
				$id = ( $id = get_post_meta( get_the_ID(), '_tce_transfer_source_id', true ) ) ? $id : '';
				$location = array();

				if ( $state_code = get_post_meta( get_the_ID(), '_tce_state_code', true ) ) {
					$location[] = $state_code;
				}

				if ( $country_code = get_post_meta( get_the_ID(), '_tce_country_code', true ) ) {
					$location[] = $country_code;
				}

				$location = implode( ', ', $location );
				$institution_results[] = '<li><a href="' . get_the_permalink() . '" data-institution-id="' . esc_attr( $id ) . '">' . get_the_title() . '</a> ' . esc_html( $location ) . '</li>';
			}

			$institution_results[] = '</ul>';

			wp_reset_postdata();

			$this->max_num_pages = $institution_query->max_num_pages;

			return implode( $institution_results );
		} else {
			return "<p>Sorry, we couldn't find any matching institutions. Please try searching or browsing using the A-Z index.</p>";
		}
	}

	/**
	 * Display an interface for navigating transfer credit equivalencies.
	 *
	 * This shortcode should be used on a page with the "Blank" template set.
	 *
	 * @param array  $atts    Arguments passed with the shortcode.
	 * @param string $content Content passed with the shortcode.
	 *
	 * @return string
	 */
	public function display_tce_interface( $atts, $content = null ) {
		ob_start();

		get_template_part( 'parts/headers' );

		if ( $content ) : ?>

		<section class="row single gutter pad-top">

			<div class="column one">

				<?php
				// @codingStandardsIgnoreStart
				echo wpautop( $content );
				// @codingStandardsIgnoreEnd
				?>

			</div>

		</section>

		<?php endif; ?>

		<section class="row side-right gutter pad-top">

			<div class="column one">

				<nav class="tce-nav-links" role="navigation" aria-label="Alphabetic Index">
					<ul class="tce-alpha-index">
						<li class="tce-all-institutions <?php if ( ! isset( $_GET['institution'] ) ) { echo 'current'; } ?>"><a href="<?php echo esc_url( get_permalink() ); ?>">All</a></li>
						<?php foreach ( range( 'A', 'Z' ) as $index ) { ?>
						<li><a href="#"><?php echo esc_html( $index ); ?></a></li>
						<?php } ?>
					</ul>
				</nav>

			</div>

			<div class="column two">

				<form class="tce-search" role="search" method="get" action="">
					<label for="tce-institution-search">Search by institution name:</label>
					<div>
						<input type="search" value="<?php if ( isset( $_GET['institution'] ) ) { echo esc_attr( $_GET['institution'] ); } ?>" id="tce-institution-search">
						<input type="submit" value="$">
					</div>
				</form>

			</div>

		</section>

		<section class="row single gutter pad-top">

			<div class="column one tce-list-header">
				<header>
					<h2 class="tce-heading"><?php
					if ( isset( $_GET['institution'] ) ) {
						echo 'Search Results for <em>' . esc_html( $_GET['institution'] ) . '</em>';
					} else {
						echo 'All Institutions';
					}
					?></h2>
				</header>
			</div>

		</section>

		<section class="row single gutter pad-top">

			<div class="column one tce-listings">

				<?php
				$search_input = null;
				if ( isset( $_GET['institution'] ) ) {
					$search_input = sanitize_text_field( $_GET['institution'] );
				}

				// @codingStandardsIgnoreStart
				echo $this->institution_query( $search_input );
				// @codingStandardsIgnoreEnd
				?>

			</div>

		</section>

		<?php
		$big = 99164;
		$args = array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => 'page/%#%',
			'total' => $this->max_num_pages, // Provide the number of pages this query expects to fill.
			'current' => max( 1, get_query_var( 'paged' ) ), // Provide either 1 or the page number we're on.
			'prev_text' => __( '«' ),
			'next_text' => __( '»' ),
			'type' => 'list',
			'mid_size' => 3,
		);
		?>
		<footer class="main-footer archive-footer">
			<section class="row single pager prevnext pad-ends">
				<div class="column one">
					<nav class="tce-nav-links" role="navigation" aria-label="Page Nagivation">
						<?php
						// @codingStandardsIgnoreStart
						echo paginate_links( $args );
						// @codingStandardsIgnoreEnd
						?>
					</nav>
				</div>
			</section>
		</footer>

		<?php
		get_template_part( 'parts/footers' );

		$html = ob_get_contents();

		ob_end_clean();

		return $html;
	}

	/**
	 * Enqueue the scripts and styles used on the front end.
	 */
	public function wp_enqueue_scripts() {
		$post = get_post();
		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'tce_interface' ) ) {
			wp_enqueue_style( 'tce-interface', plugins_url( 'css/tce-interface.css', dirname( __FILE__ ) ), array( 'spine-theme' ) );
			wp_enqueue_script( 'tce-interface', plugins_url( 'js/tce-interface.js', dirname( __FILE__ ) ), array( 'jquery' ), '', true );
			wp_localize_script( 'tce-interface', 'tce', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'page_url' => esc_url( get_permalink() ),
				'nonce' => wp_create_nonce( 'transfer-credit-equivalenices' ),
			) );
		}
		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'tce_search' ) ) {
			wp_enqueue_style( 'tce-interface', plugins_url( 'css/tce-search.css', dirname( __FILE__ ) ), array( 'spine-theme' ) );
		}
	}

	/**
	 * Handle the ajax callback to push content to the transfer credit equivalencies interface.
	 */
	public function ajax_callback() {
		check_ajax_referer( 'transfer-credit-equivalenices', 'nonce' );

		$results = array();

		// Build institution courses results.
		if ( $_POST['institution'] && is_numeric( $_POST['institution'] ) ) {
			$request_url = 'http://cstst.wsu.edu/PSIGW/RESTListeningConnector/PSFT_HR/TransferCreditEvalInstCrse.v1/get/inst/course';
			$request_url = add_query_arg( array( 'TransferSourceId' => sanitize_key( $_POST['institution'] ) ), $request_url );
			$courses = wp_remote_get( $request_url );

			if ( is_wp_error( $courses ) ) {
				return;
			}

			$courses = wp_remote_retrieve_body( $courses );
			$courses = json_decode( $courses );

			if ( ! isset( $courses->InstCourseResponse->InstCourseResponseComp ) ) {
				return;
			}

			$courses = $courses->InstCourseResponse->InstCourseResponseComp;
			$results = array();
			$results[] = '<table class="tce-courses">
				<thead>
					<tr>
						<th>Transfer</th>
						<th colspan="3">WSU Equivalent</th>
					</tr>
					<tr>
						<th>Course(s)</th>
						<th>Course(s)</th>
						<th>Course Title</th>
						<th>UCORE Requirement</th>
					</tr>
				</thead>
				<tbody>';

			foreach ( $courses as $course ) {
				if ( '' !== $course->InternalCourses ) {
					// Parse the value of the 'InternalCourses' key into three different chunks...
					// Start with blanks and fill them in as we're able.
					$wsu_course = '';
					$wsu_title = '';
					$wsu_ucore = '';

					if ( 'NON_T NON-T Non-Transfer' === $course->InternalCourses ) {
						$wsu_course = 'NON_T';
						$wsu_title = 'Non-Transfer';
					} else {
						$wsu_info = $course->InternalCourses;
						$title_end = strlen( $wsu_info );

						// Try to find the course subject and prefix first.
						$info_exploded = explode( ' ', $wsu_info );
						if ( is_array( $info_exploded ) ) {
							$wsu_course = trim( $info_exploded[0] . ' ' . $info_exploded[1] );
						}

						// Try to find if this course fulfills a UCORE requirement.
						$ucore_start = strpos( $wsu_info, 'UCORE - ' );
						if ( $ucore_start ) {
							$wsu_ucore = trim( substr( $wsu_info, $ucore_start + 8, -1 ) );
							$title_end = -( strlen( $wsu_ucore ) + 21 );
						}

						// Remove what we've already found and call what remains the title.
						$wsu_title = trim( substr( $wsu_info, strlen( $wsu_course ), $title_end ) );
					}

					$results[] = '<tr>
						<td>' . esc_html( $course->IncomingCourse ) . '</td>
						<td>' . esc_html( $wsu_course ) . '</td>
						<td>' . esc_html( $wsu_title ) . '</td>
						<td>' . esc_html( $wsu_ucore ) . '</td>
					</tr>';
				}
			}

			$results[] = '</tbody></table>';
			$results['content'] = implode( $results );
			$results['pagination_links'] = '';
		} else {
			// Build institution browsing results.
			$search_input = null;
			$index = null;
			$page = null;

			if ( isset( $_POST['search'] ) ) {
				$search_input = sanitize_text_field( $_POST['search'] );
			}

			if ( isset( $_POST['index'] ) ) {
				$index = sanitize_text_field( $_POST['index'] );
			}

			if ( isset( $_POST['page'] ) && is_numeric( $_POST['page'] ) ) {
				$page = sanitize_text_field( $_POST['page'] );

				set_query_var( 'paged', sanitize_text_field( $_POST['page'] ) );
			}

			$results['content'] = $this->institution_query( $search_input, $index, $page );

			// Update the pagination links.
			$pagination_args = array(
				'base' => esc_url( trailingslashit( $_POST['url'] . '%_%' ) ),
				'format' => 'page/%#%',
				'total' => $this->max_num_pages, // Provide the number of pages this query expects to fill.
				'current' => max( 1, get_query_var( 'paged' ) ), // Provide either 1 or the page number we're on.
				'prev_text' => __( '«' ),
				'next_text' => __( '»' ),
				'type' => 'list',
			);
			$results['pagination_links'] = paginate_links( $pagination_args );
		}

		echo wp_json_encode( $results );

		exit();
	}
}
