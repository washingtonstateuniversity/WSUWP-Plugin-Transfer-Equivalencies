(function ($) {

	'use strict';

    var tce_institution_browse = function (event) {

		event.preventDefault();

		var tce_method = '';

		if ($(this).closest('ul').hasClass('tce-alpha-index') || $('.tce-alpha-index').has('.current').length) {
			tce_method = 'alpha';
		}
		if ($(this).closest('ul').hasClass('page-numbers')) {
			tce_method += 'paged';
		}

		var page = $(this).attr('href').substr($(this).attr('href').lastIndexOf('/') - 1, 1), // not good enough
			link = $(this).parent('li'),
			data = {
				action: 'tce_navigation',
				url: tce.page_url,
				method: tce_method,
				page: page
			};

		// Make the ajax call.
		$.post(tce.ajax_url, data, function (response) {
			var response_data = $.parseJSON(response);

			tce_method = '';

			// Update the content
			$('.tce-listings').html(response_data.content);

			// Update the pagination links.
			$('.pager .tce-nav-links').html(response_data.pagination_links);

			link.addClass('current').siblings('li').removeClass('current');
		});

	};

	$('.tce-nav-links').on('click', 'a', tce_institution_browse);
	$('.tce-search').on('submit', tce_institution_browse);

}(jQuery));
