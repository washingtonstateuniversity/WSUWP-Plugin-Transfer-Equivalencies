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
	 * @var int The number of results to show per page.
	 */
	public $results_per_page = 50;

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
		//add_action( 'rest_api_init', array( $this, 'register_api_fields' ) );

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
			'public' => true,
			'show_in_admin_bar' => false,
			'menu_position' => 20,
			'menu_icon' => 'dashicons-migrate',
			'rewrite' => array(
				'slug' => 'institution',
			),
			'show_in_rest' => true,
			'rest_base' => 'institution',
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
							if ( isset( $_POST['submit'] ) ) {
								/*$institutions = wp_remote_get( 'http://cstst.wsu.edu/PSIGW/RESTListeningConnector/PSFT_HR/TransferCreditEvalInst.v1/get/institution' );

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
								}*/
							} else {
								?><p>Import transfer institutions.</p><?php
							}
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
	 * Register the custom meta fields attached to a REST API response containing course data.
	 */
	public function register_api_fields() {
		$args = array(
			'get_callback' => array( $this, 'get_api_meta_data' ),
			'update_callback' => null,
			'schema' => null,
		);

		// @todo - consider using shorter keys for the REST API response.
		$fields = array(
			'_tce_country_code',
			'_tce_state_code',
			'_tce_transfer_source_id',
		);

		foreach ( $fields as $field_name ) {
			register_rest_field( $this->content_type_slug, $field_name, $args );
		}
	}

	/**
	 * Return the sanitized value of a post meta field.
	 *
	 * @since 0.2.0
	 *
	 * @param array           $object     The current post being processed.
	 * @param string          $field_name Name of the field being retrieved.
	 * @param WP_Rest_Request $request    The full current REST request.
	 *
	 * @return mixed Meta data associated with the post and field name.
	 */
	public function get_api_meta_data( $object, $field_name, $request ) {
		return esc_html( get_post_meta( $object['id'], $field_name, true ) );
	}

	/**
	 * Display a text input for searching by institution name.
	 *
	 * @param array $atts List of attributes used for the shortcode.
	 */
	public function display_tce_search( $atts ) {
		$atts = shortcode_atts( array(
			'page_url' => '',
		), $atts );

		if ( ! $atts['page_url'] ) {
			return;
		}

		ob_start();
		?>
		<form role="search" method="get" action="<?php echo esc_url( $atts['page_url'] ); ?>">
			<div>
				<label class="screen-reader-text" for="tce-institution-search">Search for:</label>
				<input type="text" value="" name="institution" id="tce-institution-search">
				<input type="submit" value="Search">
			</div>
		</form>
		<?php
		$html = ob_get_contents();

		ob_end_clean();

		return $html;
	}

	/**
	 * Display an interface for navigating transfer credit equivalencies.
	 */
	public function display_tce_interface() {
		ob_start();

		get_template_part( 'parts/headers' );
		?>
		<section class="row side-right pad-top">

			<div class="column one padded-left">

				<nav class="tce-nav-links" role="navigation" aria-label="Alphabetic Index">
					<ul class="tce-alpha-index">
					<?php foreach ( range( 'A', 'Z' ) as $index ) { ?>
						<li><a href="#" data-index="<?php echo esc_attr( strtolower( $index ) ); ?>"><?php echo esc_html( $index ); ?></a></li>
					<?php } ?>
					</ul>
				</nav>

			</div>

			<div class="column two padded-right">

				<form class="tce-search" role="search" method="get" action="">
					<label class="screen-reader-text" for="tce-institution-search">Search for an institution:</label>
					<input type="text" value="<?php if ( isset( $_GET['institution'] ) ) { echo esc_attr( $_GET['institution'] ); } ?>" id="tce-institution-search">
					<input type="submit" value="$">
				</form>

				<a class="tce-all-institutions" href="<?php echo esc_url( get_permalink() ); ?>">All Institutions &raquo;</a>

			</div>

		</section>

		<section class="row single gutter pad-top">

			<div class="column one tce-listings">

				<?php
					$institution_query_args = array(
						'post_type' => $this->content_type_slug,
						'posts_per_page' => $this->results_per_page,
						'orderby' => 'title',
						'order' => 'ASC',
					);

					if ( isset( $_GET['institution'] ) ) {
						$institution_query_args['s'] = esc_attr( $_GET['institution'] );
					}

					$institution_query = new WP_Query( $institution_query_args );

					if ( $institution_query->have_posts() ) {
						?><ul><?php
						while ( $institution_query->have_posts() ) {
							$institution_query->the_post();
							$id = ( $id = get_post_meta( get_the_ID(), '_tce_transfer_source_id', true ) ) ? $id : '';
							$location = array();
							$state = ( $state_code = get_post_meta( get_the_ID(), '_tce_state_code', true ) ) ? $location[] = $state_code : '';
							$country = ( $country_code = get_post_meta( get_the_ID(), '_tce_country_code', true ) ) ? $location[] = $country_code : '';
							$location = implode( ', ', $location );

							?><li><a href="<?php the_permalink(); ?>" data-institution-id="<?php echo esc_attr( $id ); ?>"><?php the_title(); ?></a> <?php echo esc_html( $location ); ?></li><?php
						}
						?></ul><?php
						wp_reset_postdata();
					}
				?>

			</div>

		</section>

		<?php
		$big = 99164;
		$args = array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => 'page/%#%',
			'total' => $institution_query->max_num_pages, // Provide the number of pages this query expects to fill.
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
						<?php echo paginate_links( $args ); ?>
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
			) );
		}
	}

	/**
	 * Handle the ajax callback to push content to the transfer credit equivalencies interface.
	 */
	public function ajax_callback() {
		$results = array();

		if ( isset( $_POST['page'] ) ) {
			$institution_query_args = array(
				'orderby' => 'title',
				'order' => 'ASC',
				'posts_per_page' => $this->results_per_page,
				'post_type' => $this->content_type_slug,

			);

			if ( isset( $_POST['method'] ) && 'paged' === $_POST['method'] ) {
				$institution_query_args['offset'] = ( $_POST['page'] - 1 ) * $this->results_per_page;

				set_query_var( 'paged', sanitize_text_field( $_POST['page'] ) );
			}

			if ( isset( $_POST['method'] ) && ( 'alpha' === $_POST['method'] || 'alpha-paged' === $_POST['method'] ) ) {
				$institution_query_args['meta_key'] = '_tce_alpha_key';
				if ( 'alpha-paged' === $_POST['method'] ) {
					$page = explode( ',', sanitize_text_field( $_POST['page'] ) );
					$institution_query_args['meta_value'] = $page[0];
					$institution_query_args['offset'] = ( $page[1] - 1 ) * $this->results_per_page;

					set_query_var( 'paged', $page[1] );
				} else {
					$institution_query_args['meta_value'] = sanitize_text_field( $_POST['page'] );
				}
			}

			if ( isset( $_POST['method'] ) && 'search' === $_POST['method'] ) {
				$institution_query_args['s'] = sanitize_text_field( $_POST['page'] );
			}

			if ( isset( $_POST['method'] ) && 'search-paged' === $_POST['method'] ) {
				$page = explode( ',', sanitize_text_field( $_POST['page'] ) );
				$institution_query_args['s'] = $page[0];
				$institution_query_args['offset'] = ( $page[1] - 1 ) * $this->results_per_page;

				set_query_var( 'paged', $page[1] );
			}

			$institution_query = new WP_Query( $institution_query_args );

			if ( $institution_query->have_posts() ) {
				$institution_results = array();
				$institution_results[] = '<ul>';

				while ( $institution_query->have_posts() ) {
					$institution_query->the_post();
					$id = ( $id = get_post_meta( get_the_ID(), '_tce_transfer_source_id', true ) ) ? $id : '';
					$location = array();
					$state = ( $state_code = get_post_meta( get_the_ID(), '_tce_state_code', true ) ) ? $location[] = $state_code : '';
					$country = ( $country_code = get_post_meta( get_the_ID(), '_tce_country_code', true ) ) ? $location[] = $country_code : '';
					$location = implode( ', ', $location );

					$institution_results[] = '<li><a href="' . get_the_permalink() . '" data-institution-id="' . esc_attr( $id ) . '">' . get_the_title() . '</a> ' . esc_html( $location ) . '</li>';
				}

				$institution_results[] = '</ul>';

				wp_reset_postdata();

				$results['content'] = implode( $institution_results );
			} else {
				$results['content'] = "<p>Sorry, we couldn't find any matching institutions. Please try searching or browsing using the A-Z index.</p>";
			}

			$big = 99164;
			// Updated pagination links.
			$pagination_args = array(
				'base' => esc_url( trailingslashit( $_POST['url'] . '%_%' ) ),
				'format' => 'page/%#%',
				'total' => $institution_query->max_num_pages, // Provide the number of pages this query expects to fill.
				'current' => max( 1, get_query_var( 'paged' ) ), // Provide either 1 or the page number we're on.
				'prev_text' => __( '«' ),
				'next_text' => __( '»' ),
				'type' => 'list'
			);

			$results['pagination_links'] = paginate_links( $pagination_args );
		}

		if ( $_POST['institution'] && is_numeric( $_POST['institution'] ) ) {
			// Course rules.
			if ( isset( $_POST['subject'] ) && isset( $_POST['course'] ) ) {
				$request_url = 'https://cstst.wsu.edu/PSIGW/RESTListeningConnector/PSFT_HR/TransferCreditEvalCrseRule.v1/get/inst/course/rule';
				$request_url = add_query_arg( array(
					'TransferSourceId' => sanitize_key( $_POST['institution'] ),
					'Subject' => strtoupper( sanitize_key( $_POST['subject'] ) ),
					'Course' => sanitize_key( $_POST['course'] ),
				), $request_url );
				$course = wp_remote_get( $request_url );

				if ( is_wp_error( $course ) ) {
					return;
				}

				$course = wp_remote_retrieve_body( $course );
				$course = json_decode( $course );

				if ( ! isset( $course->CourseRuleResponse->CourseRuleResponseComp ) ) {
					return;
				}

				$course = $course->CourseRuleResponse->CourseRuleResponseComp;

				$course_results = array();

				foreach ( $course as $rules ) {
					$results[] = '<h2>' . esc_html( $rules->IncomingCourse ) . '</h2>';
					$results[] = '<p><strong>TrCreditRuleCount</strong>: ' . esc_html( $rules->TrCreditRuleCount ) . '</p>';
					$results[] = '<p><strong>TrEquivalencyComp</strong>: ' . esc_html( $rules->TrEquivalencyComp ) . '</p>';
					$results[] = '<p><strong>IncomingCourseCount</strong>: ' . esc_html( $rules->IncomingCourseCount ) . '</p>';
					$results[] = '<p><strong>InternalEquivNbr</strong>: ' . esc_html( $rules->InternalEquivNbr ) . '</p>';
					$results[] = '<p><strong>InternalCourses</strong>: ' . esc_html( $rules->InternalCourses ) . '</p>';
					$results[] = '<p><strong>WildcardRuleCount</strong>: ' . esc_html( $rules->WildcardRuleCount ) . '</p>';
					$results[] = '<p><strong>Note</strong>: ' . esc_html( $rules->Note ) . '</p>';
				}
			// Institution courses.
			} else {
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
				$results[] = '<ul class="tce-courses">';

				foreach ( $courses as $course ) {
					$results[] = '<li><a href="#" data-institution-id="' . esc_attr( $_POST['institution'] ) . '" data-subject="' . esc_attr( $course->Subject ) . '" data-course="' . esc_attr( $course->CatalogNumber ) . '">' . esc_html( $course->IncomingCourse ) . '</a> <span class="tce-internal-course">' . esc_html( $course->InternalCourses ) . '</span></li>';
				}

				$results[] = '</ul>';
			}

			$results['content'] = implode( $results );

			// pagination links...
			//$results['pagination_links'] = '';
		}

		echo json_encode( $results );

		exit();
	}
}
