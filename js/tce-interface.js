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
		if ($('.tce-alpha-index').has('.current').length) {
			data.index = $('.tce-alpha-index .current a').html();
		} else if ($('#tce-institution-search').val().length) {
			data.search = $('#tce-institution-search').val();
		}

		data.page = $(this).attr('href').slice(tce.page_url.length + 5, -1); // Get page number (permalink + 'paged/' - trailing slash).

		tce_institution_browse(e);
	});

	// Alphabetic index link click handling.
	$('.tce-alpha-index').on('click', 'a', function (e) {
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
		$('.tce-heading').html($(this).html()); // Update the heading.
		$('.tce-listings').html('<div class="tce-loading"></div>'); // Loading animation.
	});
}(jQuery));
