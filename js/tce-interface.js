(function ($) {

	'use strict';

	// Browsing institutions via page, alphabetic index, or search.
    var tce_institution_browse = function (e, method, page) {
		e.preventDefault();

		var data = {
				action: 'tce_navigation',
				url: tce.page_url,
				method: method,
				page: page
			};

		// Make the ajax call.
		$.post(tce.ajax_url, data, function (response) {
			var response_data = $.parseJSON(response);

			// Update the content
			$('.tce-listings').html(response_data.content);

			// Update the pagination links.
			$('.pager .tce-nav-links').html(response_data.pagination_links);
		});
	};

	// Pagination link click handling.
	$('.pager .tce-nav-links').on('click', 'a', function (e) {
		if ( $('.tce-alpha-index').has('.current').length ) {
			var method = 'alpha-paged',
				page = $('.tce-alpha-index .current a').data('index') + ',' + $(this).attr('href').slice(tce.page_url.length + 5, -1);
		} else if ( $('#tce-institution-search').val().length ) {
			var method = 'search-paged',
				page = $('#tce-institution-search').val() + ',' + $(this).attr('href').slice(tce.page_url.length + 5, -1);
		} else {
			var method = 'paged',
				page = $(this).attr('href').slice(tce.page_url.length + 5, -1);
		}

		tce_institution_browse(e, method, page);
	});

	// Alphabetic index link click handling.
	$('.tce-alpha-index').on('click', 'a', function (e) {
		var page = $(this).data('index').toUpperCase(),
			link = $(this).parent('li');

		 $('#tce-institution-search').val('');

		tce_institution_browse(e, 'alpha', page);

		link.addClass('current').siblings('li').removeClass('current');
	});

	// Search handling.
	$('.tce-search').on('submit', function (e) {
		var page = $('#tce-institution-search').val();

		$('.tce-alpha-index .current').removeClass('current');

		tce_institution_browse(e, 'search', page);
	});

	// Institution link click handling.
	$('.tce-listings').on('click', 'a', function (e) {
		e.preventDefault();

		var data = {
				action: 'tce_navigation',
				institution: $(this).data('institution-id')
			};

		// Loading animation.
		$('.tce-listings').html('<div class="tce-loading"></div>');

		// Remove the pagination links.
		$('.pager .tce-nav-links').html('');

		// Make the ajax call.
		$.post(tce.ajax_url, data, function (response) {
			var response_data = $.parseJSON(response);

			// Update the content
			$('.tce-listings').html(response_data.content);
		});
	});

}(jQuery));
