/**
 * Context AI Search - Frontend Scripts
 */
(function($) {
	'use strict';

	var $container, $messages, $form, $input, $submitBtn;

	$(document).ready(function() {
		$container = $('#cais-search-container');
		if (!$container.length) {
			return;
		}

		$messages = $('#cais-results-container');
		$form = $('#cais-search-form');
		$input = $('#cais-query-input');
		$submitBtn = $('#cais-submit-btn');

		// Handle form submission
		$form.on('submit', handleSubmit);

		// Focus input on load
		$input.focus();
	});

	function handleSubmit(e) {
		e.preventDefault();

		var query = $input.val().trim();
		if (!query) {
			return;
		}

		// Remove welcome message if exists
		$('#cais-welcome-message').fadeOut(200, function() {
			$(this).remove();
		});

		// Clear previous results
		$messages.empty();

		// Keep input value (don't clear it)
		$input.prop('disabled', true);
		$submitBtn.prop('disabled', true);

		// Show loading
		var $loading = addLoadingMessage();

		// Send AJAX request
		$.ajax({
			url: wpCais.ajaxUrl,
			type: 'POST',
			data: {
				action: 'cais_search',
				nonce: wpCais.nonce,
				query: query
			},
			success: function(response) {
				$loading.remove();

				if (response.success) {
					addResult(response.data.response, response.data.sources);
				} else {
					addError(response.data.message || wpCais.strings.error);
				}
			},
			error: function() {
				$loading.remove();
				addError(wpCais.strings.error);
			},
			complete: function() {
				$input.prop('disabled', false);
				$submitBtn.prop('disabled', false);
				$input.focus();
				scrollToBottom();
			}
		});
	}

	function addResult(content, sources) {
		var $result = $('<div>').addClass('cais-result');
		var $content = $('<div>').addClass('cais-result-content');

		// Decode HTML entities in content
		var tempDiv = document.createElement('div');
		tempDiv.innerHTML = content;
		content = tempDiv.textContent || tempDiv.innerText || content;

		// Convert newlines to paragraphs
		var paragraphs = content.split('\n\n');
		paragraphs.forEach(function(para) {
			if (para.trim()) {
				$content.append($('<p>').text(para.trim()));
			}
		});

		$result.append($content);

		// Add sources if available
		if (sources && sources.length > 0) {
			var $sources = $('<div>').addClass('cais-sources');
			$sources.append($('<div>').addClass('cais-sources-title').text('Results:'));
			
			sources.forEach(function(source) {
				var $link = $('<a>')
					.addClass('cais-source-link')
					.attr('href', source.url)
					.attr('target', '_blank')
					.text(source.title);
				$sources.append($link);
			});

			$content.append($sources);
		}

		$messages.append($result);
		scrollToBottom();
	}

	function addError(message) {
		var $error = $('<div>').addClass('cais-error');
		$error.append($('<p>').text(message));
		$messages.append($error);
		scrollToBottom();
	}

	function addLoadingMessage() {
		var $loading = $('<div>').addClass('cais-message cais-message-assistant');
		var $content = $('<div>').addClass('cais-loading');
		
		$content.append($('<span>').text(wpCais.strings.searching));
		$content.append($('<div>').addClass('cais-loading-dots')
			.append($('<span>').addClass('cais-loading-dot'))
			.append($('<span>').addClass('cais-loading-dot'))
			.append($('<span>').addClass('cais-loading-dot'))
		);

		$loading.append($content);
		$messages.append($loading);
		scrollToBottom();

		return $loading;
	}

	function scrollToBottom() {
		$messages.animate({
			scrollTop: $messages[0].scrollHeight
		}, 300);
	}

})(jQuery);
