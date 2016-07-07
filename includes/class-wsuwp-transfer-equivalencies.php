<?php

class WSUWP_Transfer_Equivalencies {
	/**
	 * @var WSUWP_Transfer_Equivalencies
	 */
	private static $instance;

	/**
	 * @var string Slug for tracking the content type of a transfer credit equivalency.
	 */
	public $content_type_slug = 'tce_course';

	/**
	 * @var string Slug for tracking the institution taxonomy.
	 */
	public $institution_taxonomy_slug = 'tce_institution';

	/**
	 * @var string Slug for tracking the institution taxonomy.
	 */
	public $subject_taxonomy_slug = 'tce_subject';

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
		add_action( 'init', array( $this, 'register_institution_taxonomy' ), 10 );
		add_action( 'init', array( $this, 'register_subject_taxonomy' ), 10 );
		add_action( 'add_meta_boxes_' . $this->content_type_slug, array( $this, 'add_meta_boxes' ), 10 );
		add_filter( 'manage_edit-' . $this->institution_taxonomy_slug . '_columns', array( $this, 'institution_columns' ) );
		add_filter( 'manage_' . $this->institution_taxonomy_slug . '_custom_column', array( $this, 'institution_column_content' ), 10, 3 );
		add_action( $this->institution_taxonomy_slug . '_edit_form_fields', array( $this, 'institution_term_meta' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'api_testing_notices' ) );

		add_shortcode( 'tce_institution_search', array( $this, 'display_tce_institution_search' ) );
		add_filter( 'template_include', array( $this, 'template_include' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
	}

	/**
	 * Register a content type to track information about transfer credit equivalencies.
	 */
	public function register_content_type() {
		$labels = array(
			'name' => 'Courses',
			'singular_name' => 'Course',
			'all_items' => 'Courses',
			'view_item' => 'View Course',
			'add_new_item' => 'Add New Course',
			'edit_item' => 'Edit Course',
			'update_item' => 'Update Course',
			'search_items' => 'Search Courses',
			'not_found' => 'No Courses found',
			'not_found_in_trash' => 'No Courses found in Trash',
		);

		$args = array(
			'labels' => $labels,
			'description' => 'Courses at the transfer institution.',
			'public' => true,
			'show_in_admin_bar' => false,
			'menu_position' => 20,
			'menu_icon' => 'dashicons-migrate',
			'rewrite' => array(
				'slug' => 'course',
			),
			'show_in_rest' => true,
			'rest_base' => 'course',
		);

		register_post_type( $this->content_type_slug, $args );
	}

	/**
	 * Register an Institution taxonomy that will be attached to the transfer credit equivalencies content type.
	 */
	public function register_institution_taxonomy() {
		$labels = array(
			'name' => 'Institutions',
			'singular_name' => 'Institution',
			'all_items' => 'All Institutions',
			'edit_item' => 'Edit Institution',
			'view_item' => 'View Institution',
			'update_item' => 'Update Institution',
			'add_new_item' => 'Add New Institution',
			'new_item_name' => 'New Institution Name',
			'search_items' => 'Search Institutions',
			'popular_items' => 'Popular Institutions',
			'separate_items_with_commas' => 'Separate institutions with commas',
			'add_or_remove_items' => 'Add or remove institutions',
			'choose_from_most_used' => 'Choose from the most used institutions',
			'not_found' => 'No institutions found',
		);
		$args = array(
			'labels' => $labels,
			'description' => 'Transfer institutions.',
			'public' => true,
			'hierarchical' => false,
			'show_tagcloud' => false,
			'show_in_quick_edit' => false,
			//'meta_box_cb' => '', // Hmm
			'show_admin_column' => true,
			'show_in_rest' => true,
			'rest_base' => 'institution',
			'rewrite' => array(
				'slug' => 'institution',
			),
		);
		register_taxonomy( $this->institution_taxonomy_slug, $this->content_type_slug, $args );
	}

	/**
	 * Register a Subject taxonomy that will be attached to the transfer credit equivalencies content type.
	 */
	public function register_subject_taxonomy() {
		$labels = array(
			'name' => 'Subjects',
			'singular_name' => 'Subject',
			'all_items' => 'All Subjects',
			'edit_item' => 'Edit Subject',
			'view_item' => 'View Subject',
			'update_item' => 'Update Subject',
			'add_new_item' => 'Add New Subject',
			'new_item_name' => 'New Subject Name',
			'search_items' => 'Search Subject',
			'popular_items' => 'Popular Subjects',
			'separate_items_with_commas' => 'Separate subjects with commas',
			'add_or_remove_items' => 'Add or remove subjects',
			'choose_from_most_used' => 'Choose from the most used subjects',
			'not_found' => 'No subjects found',
		);
		$args = array(
			'labels' => $labels,
			'description' => 'Transfer course subject.',
			'public' => true,
			'hierarchical' => false,
			'show_tagcloud' => false,
			'show_in_quick_edit' => false,
			//'meta_box_cb' => '', // Hmm
			'show_admin_column' => true,
			'show_in_rest' => true,
			'rest_base' => 'subject',
			'rewrite' => array(
				'slug' => 'subject',
			),
		);
		register_taxonomy( $this->subject_taxonomy_slug, $this->content_type_slug, $args );
	}

	/**
	 * Add the metabox used to show and capture transfer credit equivalency information.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'wsuwp-transfer-equivalency-meta',
			'Transfer Credit Equivalency Information',
			array( $this, 'display_transfer_credit_equivalency_meta_box' ),
			$this->content_type_slug,
			'normal',
			'high'
		);
	}

	/**
	 * Display the metabox used to show and capture transfer credit equivalency information.
	 *
	 * @param WP_Post $post Object for the post currently being edited.
	 */
	public function display_transfer_credit_equivalency_meta_box( $post ) {
		// The following is an example of information returned by the "Courses by Institution" service.
		// "?"'s denote that I'm not sure what the key means or what its value is used for.

		// Subject				@string	Transfer course subject - possibly store as taxonomy term.
		// CatalogNumber		@string	Transfer course catalog number - store as meta.
		// TrCreditRuleCount	@int	? (probably store as meta)
		// CompSubjectArea 		@string Is this alwasys the same value as Subject? If so, is there any need to store both?
		// TrEquivalencyComp	@string	? (probably store as meta)
		// IncomingCourseCount	@int	? (probably store as meta)
		// IncomingCourse 		@string Transfer course subject and number - store as meta, or posssibly don't and just parse Subject (or CompSubjectArea) and Catalog number.
		// InternalEquivNbr		@int	? (probably store as meta)
		// InternalCourses		@string Equivalent course(s) - store as meta.
		// WildcardRuleCount	@int	? (probably store as meta)
		// Note					@string	Equivalent course - store as meta.

		// The "Course Rules" service returns the above - often as multiple, similar records with differing
		// values for some keys ("TrEquivalencyComp", "InternalCourses", Note", possibly others) - but:
		// without "Subject" and "CatalogNumber" keys;
		// with a different "WildcardRuleCount" value; and
		// includes a "TransferPriority" key.

		// TransferPriority		@int	? (probably store as meta)

		// @todo - rename all these to something more intuitive (it will help to know what they are/do), shorten meta keys.
		$catalog_number = get_post_meta( $post->ID, '_tce_catalog_number', true );
		$tr_credit_rule_count = get_post_meta( $post->ID, '_tce_tr_credit_rule_count', true );
		$tr_equivalency_comp = get_post_meta( $post->ID, '_tce_tr_equivalency_comp', true );
		$incoming_course_count = get_post_meta( $post->ID, '_tce_incoming_course_count', true );
		$internal_equiv_nbr = get_post_meta( $post->ID, '_tce_internal_equiv_nbr', true );
		$internal_courses = get_post_meta( $post->ID, '_tce_internal_courses', true );
		$wildcard_rule_count = get_post_meta( $post->ID, '_tce_wildcard_rule_count', true );
		$note = get_post_meta( $post->ID, '_tce_note', true );
		$transfer_priority = get_post_meta( $post->ID, '_tce_transfer_priority', true );

		// We'll eventually leverage the nonce for additional data to be saved along with the record.
		wp_nonce_field( 'save-wsu-transfer-meta', '_wsu_transfer_meta_nonce' );

		// I'm not certain there's any reason to show this data on the back end.
		?>
		<p><strong>Catalog Number:</strong> <?php if ( $catalog_number ) { echo esc_html( $catalog_number ); } ?></p>

		<p><strong>Credit Rule Count:</strong> <?php if ( $tr_credit_rule_count ) { echo esc_html( $tr_credit_rule_count ); } ?></p>

		<p><strong>Equivalency Comp:</strong> <?php if ( $tr_equivalency_comp ) { echo esc_html( $tr_equivalency_comp ); } ?></p>

		<p><strong>Incoming Course Count:</strong> <?php if ( $incoming_course_count ) { echo esc_html( $incoming_course_count ); } ?></p>

		<p><strong>Internal Equivalency Number:</strong> <?php if ( $internal_equiv_nbr ) { echo esc_html( $internal_equiv_nbr ); } ?></p>

		<p><strong>Internal Courses:</strong> <?php if ( $internal_courses ) { echo esc_html( $internal_courses ); } ?></p>

		<p><strong>Wildecard Rule Count:</strong> <?php if ( $wildcard_rule_count ) { echo esc_html( $wildcard_rule_count ); } ?></p>

		<p><strong>Note:</strong> <?php if ( $note ) { echo esc_html( $note ); } ?></p>

		<p><strong>Transfer Priority:</strong> <?php if ( $transfer_priority ) { echo esc_html( $transfer_priority ); } ?></p>
		<?php
	}

	/**
	 * Add Country and State code columns to the All Institutions view.
	 * Probably not necessary.
	 */
	public function institution_columns( $columns ) {
		$columns['country_code'] = 'Country';
		$columns['state_code'] = 'State';

		unset( $columns['description'] );

		return $columns;
	}

	/**
	 * Add Country and State code meta to their respective columns in the All Institutions view.
	 * Probably not necessary.
	 */
	public function institution_column_content( $content, $column_name, $term_id ) {
		if ( 'country_code' === $column_name ) {
			$country_code = get_term_meta( absint( $term_id ), 'country_code', true );

			if ( $country_code ) {
				$content .= esc_attr( $country_code );
			}
		}

		if ( 'state_code' === $column_name ) {
			$state_code = get_term_meta( absint( $term_id ), 'state_code', true );

			if ( $state_code ) {
				$content .= esc_attr( $state_code );
			}
		}

		return $content;
	}

	/**
	 * Display taxonomy meta data.
	 *
	 * @param
	 * @param
	 */
	public function institution_term_meta( $term, $taxonomy ) {
		?>
		<tr class="form-field term-group-wrap">
			<th scope="row"><label for="feature-group">Institution Data</label></th>
			<td>
			<?php if ( $country_code = get_term_meta( $term->term_id, 'country_code', true ) ) { ?>
				<p>Country Code: <strong><?php echo esc_html( $country_code ); ?></strong></p>
			<?php } ?>
			<?php if ( $state_code = get_term_meta( $term->term_id, 'state_code', true ) ) { ?>
				<p>State Code: <strong><?php echo esc_html( $state_code ); ?></strong></p>
			<?php } ?>
			<?php if ( $transfer_id = get_term_meta( $term->term_id, 'transfer_source_id', true ) ) { ?>
				<p>Transfer Source Id: <strong><?php echo esc_html( $transfer_id ); ?></strong></p>
			<?php } ?>

			</td>
		</tr>
		<?php
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
	 * A temporary utility to testing recieving data from the myWSU API.
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
							<label>Import institution and its courses by TransferSourceId <em>(note that it will take a moment)</em></label><br />
							<input type="number" name="transfer_source_id" placeholder="TransferSourceId" value="" />

							<?php
							if ( isset( $_POST['submit'] ) && isset( $_POST['transfer_source_id'] ) ) {
								$institutions_url = 'http://cstst.wsu.edu/PSIGW/RESTListeningConnector/PSFT_HR/TransferCreditEvalInst.v1/get/institution';
								$institutions = wp_remote_get( $institutions_url );

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
									if ( $_POST['transfer_source_id'] === $institution->TransferSourceId ) {
										$institution_name = str_replace( ',', ' ', $institution->TransferSourceDescr );

										// Insert a term and meta data for tracking an institution.
										$term = wp_insert_term( $institution_name, $this->institution_taxonomy_slug );

										if ( is_array( $term ) ) {

											if ( $institution->CountryCode ) {
												add_term_meta( $term['term_id'], 'country_code', $institution->CountryCode, true );
											}

											if ( $institution->StateCode ) {
												add_term_meta( $term['term_id'], 'state_code', $institution->StateCode, true );
											}

											if ( $institution->TransferSourceId ) {
												add_term_meta( $term['term_id'], 'transfer_source_id', $institution->TransferSourceId, true );
											}

											// Retrieve courses from this institition and create Course posts for them.
											$this->retrieve_courses( $institution->TransferSourceId, $institution_name );

										}

										// Stop.
										break;
									}
								}
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
	 * Retrieval the course records for a given institution.
	 */
	public function retrieve_courses( $institution_id, $institution_name ) {
		// Retrieve all courses from an institute.
		$institution_courses_url = 'https://cstst.wsu.edu/PSIGW/RESTListeningConnector/PSFT_HR/TransferCreditEvalInstCrse.v1/get/inst/course';
		$institution_courses_url = add_query_arg( 'TransferSourceId', urlencode( $institution_id ), $institution_courses_url );
		$institution_courses = wp_remote_get( $institution_courses_url );

		if ( is_wp_error( $institution_courses ) ) {
			return;
		}

		$courses = wp_remote_retrieve_body( $institution_courses );
		$courses = json_decode( $courses );

		if ( ! $courses->InstCourseResponse->InstCourseResponseComp ) {
			return;
		}

		$courses = $courses->InstCourseResponse->InstCourseResponseComp;

		foreach ( $courses as $course ) {

			if ( 'NON_T NON-T Non-Transfer' === $course->InternalCourses || '' === $course->InternalCourses ) {
				continue;
			}

			// Build the array of meta data.
			$transfer_course_meta = array();
			if ( $course->CatalogNumber ) {
				$transfer_course_meta['_tce_catalog_number'] = $course->CatalogNumber;
			}

			if ( $course->TrCreditRuleCount ) {
				$transfer_course_meta['_tce_tr_credit_rule_count'] = $course->TrCreditRuleCount;
			}

			if ( $course->TrEquivalencyComp ) {
				$transfer_course_meta['_tce_tr_equivalency_comp'] = $course->TrEquivalencyComp;
			}

			if ( $course->IncomingCourseCount ) {
				$transfer_course_meta['_tce_incoming_course_count'] = $course->IncomingCourseCount;
			}

			if ( $course->InternalEquivNbr ) {
				$transfer_course_meta['_tce_internal_equiv_nbr'] = $course->InternalEquivNbr;
			}

			if ( $course->InternalCourses ) {
				$transfer_course_meta['_tce_internal_courses'] = $course->InternalCourses;
			}

			if ( $course->WildcardRuleCount ) {
				$transfer_course_meta['_tce_wildcard_rule_count'] = $course->WildcardRuleCount;
			}

			if ( $course->Note ) {
				$transfer_course_meta['_tce_note'] = $course->Note;
			}

			// Build the post to insert.
			$transfer_course = array(
				'post_title' => $course->IncomingCourse,
				'post_name' => sanitize_title_with_dashes( $institution_name . '-' . $course->IncomingCourse, '', 'save' ),
				'post_status' => 'publish',
				'post_type' => $this->content_type_slug,
				'tax_input' => array(
					$this->institution_taxonomy_slug => $institution_name,
					$this->subject_taxonomy_slug => $course->Subject,
				),
				'meta_input' => $transfer_course_meta,
			);

			// Insert the post.
			$post_id = wp_insert_post( $transfer_course );

			// Retrieve course rules.
			$course_rules_url = 'http://cstst.wsu.edu/PSIGW/RESTListeningConnector/PSFT_HR/TransferCreditEvalCrseRule.v1/get/inst/course/rule';
			$course_rules_url = add_query_arg( array(
				'TransferSourceId' => urlencode( $institution_id ),
				'Subject'          => urlencode( $course->Subject ),
				'Course'           => urlencode( $course->CatalogNumber ),
			), $course_rules_url );

			$course_rules = wp_remote_get( $course_rules_url );

			if ( is_wp_error( $course_rules ) ) {
				continue;
			}

			$rules = wp_remote_retrieve_body( $course_rules );
			$rules = json_decode( $rules );

			if ( isset( $rules->CourseRuleResponse->CourseRuleResponseComp ) ) {
				$rules = $rules->CourseRuleResponse->CourseRuleResponseComp;

				foreach ( $rules as $index => $rule ) {
					// Add rule meta data if it exists for this course.
					if ( $rule->TrEquivalencyComp ) {
						update_post_meta( $post_id, '_tce_tr_equivalency_comp', $rule->TrEquivalencyComp );
					}

					if ( $rule->TransferPriority ) {
						update_post_meta( $post_id, '_tce_transfer_priority', $rule->TransferPriority );
					}

					if ( $rule->InternalCourses ) {
						update_post_meta( $post_id, '_tce_internal_courses', $rule->InternalCourses );
					}

					if ( $rule->Note ) {
						update_post_meta( $post_id, '_tce_note', $rule->Note );
					}
				}
			}
		}
	}

	/**
	 * Not very well done admin notices for the API Settings page.
	 */
	public function api_testing_notices() {
		$screen = get_current_screen();

		if ( 'tce_course_page_tce-api-settings' !== $screen->id ) {
			return;
		}

		if ( isset( $_POST['submit'] ) ) {
			if ( isset( $_POST['transfer_source_id'] ) ) {
				?><div class="notice notice-success is-dismissible">
					<p>Successfully imported!</p>
				</div><?php
			} else {
				?><div class="notice notice-error is-dismissible">
					<p>Import unsuccessful.</p>
				</div><?php
			}
		}
	}

	/**
	 * Display a text input for searching by institution name.
	 */
	public function display_tce_institution_search() {
		ob_start();
		?>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<div>
				<label class="screen-reader-text" for="instutition-search">Search for:</label>
				<input type="text" value="" name="institution" id="instutition-search">
				<input type="submit" value="Search">
			</div>
		</form>
		<?php
		$html = ob_get_contents();

		ob_end_clean();

		return $html;
	}

	/**
	 * Add a template for the transfer equivalencies search page.
	 *
	 * @param string $template
	 *
	 * @return string template path
	 */
	public function template_include( $template ) {
		if ( is_page( 'equivalencies' ) || isset( $_GET['institution'] ) ) {
			$template = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/index.php';
		}

		if ( is_tax( $this->institution_taxonomy_slug ) ) {
			$template = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/institution.php';
		}

		if ( is_single() && get_post_type() === $this->content_type_slug ) {
			$template = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/course.php';
		}

		return $template;
	}

	/**
	 * Enqueue the scripts and styles used on the front end.
	 */
	public function wp_enqueue_scripts() {
		if ( is_page( 'equivalencies' ) || isset( $_GET['institution'] ) || is_tax( $this->institution_taxonomy_slug ) ) {
			wp_enqueue_style( 'tce-institutions', plugins_url( 'css/institutions.css', dirname( __FILE__ ) ), array( 'spine-theme' ) );
		}
	}
}
