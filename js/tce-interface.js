(function ($) {

	'use strict';

	// Set up common data for the Ajax call.
	var data = {
			action: 'tce_navigation',
			url: tce.page_url
		},
		// Make the Ajax call and update the content accordingly.
		tce_institution_browse = function (e) {
			e.preventDefault();

			$('.tce-course-filter').remove(); // Remove course search form.

			// Make the AJAX call.
			$.post(tce.ajax_url, data, function (response) {
				var response_data = $.parseJSON(response);

				$('.tce-listings').html(response_data.content); // Update the content.
				$('.pager .tce-nav-links').html(response_data.pagination_links); // Update the pagination links.
			});

			// Reset data object.
			data = {
				action: 'tce_navigation',
				url: tce.page_url
			};
		};

	// Pagination link click handling.
	$('.pager .tce-nav-links').on('click', 'a', function (e) {
		if ($('.tce-alpha-index').has('.current').length && 'All' !== $('.tce-alpha-index .current a').html()) {
			data.index = $('.tce-alpha-index .current a').html();
		} else if ($('#tce-institution-search').val().length) {
			data.search = $('#tce-institution-search').val();
		}

		var $page_number = $(this).attr('href').slice(tce.page_url.length + 5, -1); // Get page number (permalink + 'paged/' - trailing slash).

		data.page = $page_number ? $page_number : '1';
		tce_institution_browse(e);
	});

	// Alphabetic index link click handling.
	$('.tce-alpha-index li:not(.tce-all-institutions)').on('click', 'a', function (e) {
		data.index = $(this).html();

		tce_institution_browse(e);

		$('.tce-heading').html('Institutions - ' + $(this).html()); // Update the heading.
		$(this).parent('li').addClass('current').siblings('li').removeClass('current'); // Set current alpha index link.
		$('#tce-institution-search').val(''); // Remove value from search input.
	});

	// Search submit handling.
	$('.tce-search').on('submit', function (e) {
		data.search = $('#tce-institution-search').val();

		tce_institution_browse(e);

		$('.tce-heading').html('Search Results for <em>' + $('#tce-institution-search').val() + '</em>'); // Update the heading.
		$('.tce-alpha-index .current').removeClass('current'); // Remove current alpha index link.
	});

	// Institution link click handling.
	$('.tce-listings').on('click', 'a', function (e) {
		data.institution = $(this).data('institution-id');

		tce_institution_browse(e);

		$('.pager .tce-nav-links').html(''); // Remove pagination links.
		$('#tce-institution-search').val(''); // Remove value from search input.
		$('.tce-alpha-index .current').removeClass('current'); // Remove current alpha index link.
		$('.tce-heading').html($(this).html()); // Update the heading.
		$('.tce-listings').html('<div class="tce-loading"></div>'); // Loading animation.
		// Add course filter elements.
		var course_filter = '<form class="tce-course-filter" role="search">' +
			'<label for="tce-course-filter-input">Search courses:</label>' +
			'<input type="search" id="tce-course-filter-input" value="" autocomplete="off">' +
			'<label><input type="radio" name="tce-course-filter-by" value="transfer"> Transfer Course</label>' +
			'<label><input type="radio" name="tce-course-filter-by" value="internal"> WSU Course</label>' +
			'<label><input type="radio" name="tce-course-filter-by" value="both" checked="checked"> Both</label>' +
			'</form>';
		$('.tce-heading').parent('header').after(course_filter);
	});

	// Prevent course search submit.
	$('.tce-list-header').on('submit', '.tce-course-filter', function (e) {
		e.preventDefault();
	});

	// Course search handling.
	$('.tce-list-header').on('keyup', 'input[type=search]', function () {
		var	value = $(this).val(),
			search_by = $('input[name=tce-course-filter-by]:checked').val(),
			courses = $('.tce-courses tbody tr');

		if (value.length > 0) {
			courses.each(function () {
				var course = $(this),
					content_area = course;

				if ('transfer' === search_by) {
					content_area = course.find($('td:first-of-type'));
				}

				if ('internal' === search_by) {
					content_area = course.find($('td:not(:first-of-type)'));
				}

				if (content_area.text().toLowerCase().indexOf(value.toLowerCase()) === -1) {
					course.hide('fast');
				} else {
					course.show('fast');
				}
			});
		} else {
			courses.show('fast');
		}
	});

	// Trigger search input keyup when radio button is changed.
	$('.tce-list-header').on('change', 'input[type=radio]', function () {
		$('.tce-list-header input[type=search]').trigger('keyup');

	});
}(jQuery));
