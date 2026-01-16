/**
 * Context AI Search - Admin Scripts
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		console.log('WP CAIS Admin JS loaded');
		console.log('wpCaisAdmin object:', wpCaisAdmin);
		
		// Handle premium feature checkboxes
		var isPremium = $('.cais-premium-section .cais-premium-notice').length === 0;

		if (!isPremium) {
			// Disable premium checkboxes
			$('.cais-premium-section input[type="checkbox"]').prop('disabled', true);

			// Show tooltip or message when clicking disabled checkboxes
			$('.cais-premium-section input[type="checkbox"]').on('click', function(e) {
				e.preventDefault();
				$(this).prop('checked', false);
				
				// Scroll to premium notice
				$('html, body').animate({
					scrollTop: $('.cais-premium-notice').offset().top - 50
				}, 500);
			});
		}

		// Form validation
		$('form').on('submit', function(e) {
			var freeCheckboxes = $('.cais-section:not(.cais-premium-section) input[type="checkbox"]');
			var hasFreeChecked = false;

			freeCheckboxes.each(function() {
				if ($(this).is(':checked')) {
					hasFreeChecked = true;
					return false;
				}
			});

			if (!hasFreeChecked) {
				e.preventDefault();
				alert('Please select at least one content type to enable search.');
				return false;
			}
		});

		// Update provider link when provider changes
		var providerLinks = {
			'openai': '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>',
			'claude': '<a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic</a>',
			'gemini': '<a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>',
			'huggingface': '<a href="https://huggingface.co/settings/tokens" target="_blank">HuggingFace</a>'
		};

		$('#ai_provider').on('change', function() {
			var provider = $(this).val();
			var link = providerLinks[provider] || providerLinks['openai'];
			$('#cais-api-key-link').html('Get your API key from ' + link);
			// Hide quota info when provider changes
			$('#cais-quota-info').hide();
		});

		// Function to display quota information
		function displayQuotaInfo(quota, provider) {
			var $quotaInfo = $('#cais-quota-info');
			var $quotaContent = $('#cais-quota-content');
			
			if (!quota || !quota.has_quota_info) {
				$quotaContent.html('<p style="margin: 0; color: #646970;">' + (quota.message || 'Quota information not available. Check your provider dashboard.') + '</p>');
				$quotaInfo.show();
				return;
			}

			var html = '';
			var showWarning = false;
			var purchaseLinks = {
				'openai': 'https://platform.openai.com/account/billing',
				'claude': 'https://console.anthropic.com/settings/billing',
				'gemini': 'https://console.cloud.google.com/billing'
			};

			if (quota.limit !== undefined && quota.used !== undefined) {
				var percentage = quota.percentage || ((quota.used / quota.limit) * 100);
				var usedFormatted = typeof quota.used === 'number' ? '$' + quota.used.toFixed(2) : quota.used;
				var limitFormatted = typeof quota.limit === 'number' ? '$' + quota.limit.toFixed(2) : quota.limit;
				
				html += '<p style="margin: 0 0 8px 0; color: #1d2327;">';
				html += '<strong>Usage:</strong> ' + usedFormatted + ' / ' + limitFormatted;
				html += ' (' + percentage.toFixed(1) + '%)';
				html += '</p>';

				// Show warning if over 80%
				if (percentage >= 80) {
					showWarning = true;
					html += '<p style="margin: 8px 0 0 0; color: #d63638;">';
					html += '⚠ ' + (percentage >= 100 ? 'Quota exceeded!' : 'Quota is running low.');
					html += '</p>';
				}
			} else {
				html += '<p style="margin: 0; color: #646970;">' + (quota.message || 'Quota information not available.') + '</p>';
			}

			// Add purchase link if quota is low or exceeded
			if (showWarning && purchaseLinks[provider]) {
				html += '<p style="margin: 8px 0 0 0;">';
				html += '<a href="' + purchaseLinks[provider] + '" target="_blank" class="button button-small" style="text-decoration: none;">';
				html += 'Purchase More Credits';
				html += '</a>';
				html += '</p>';
			}

			$quotaContent.html(html);
			$quotaInfo.css('border-left-color', showWarning ? '#d63638' : '#2271b1').show();
		}

		// Function to fetch quota information
		function fetchQuotaInfo(apiKey, provider) {
			if (!apiKey || !apiKey.trim()) {
				$('#cais-quota-info').hide();
				return;
			}

			$.ajax({
				url: wpCaisAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cais_get_quota',
					nonce: wpCaisAdmin.nonce,
					api_key: apiKey,
					provider: provider
				},
				success: function(response) {
					if (response.success && response.data.quota) {
						displayQuotaInfo(response.data.quota, provider);
					} else {
						$('#cais-quota-info').hide();
					}
				},
				error: function() {
					$('#cais-quota-info').hide();
				}
			});
		}

		// Test API key - use both class and ID selector for better compatibility
		$(document).on('click', '.cais-test-api-key, #cais-test-api-key', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var $button = $(this);
			var $status = $('#cais-api-key-status');
			var apiKey = $('#ai_api_key').val();
			var provider = $('#ai_provider').val();

			console.log('Test API Key clicked', { 
				apiKey: apiKey ? apiKey.substring(0, 10) + '...' : 'empty', 
				provider: provider,
				ajaxUrl: wpCaisAdmin.ajaxUrl,
				hasNonce: !!wpCaisAdmin.nonce
			});

			if (!apiKey || !apiKey.trim()) {
				$status.html('<span style="color: red;">✗ API key is empty</span>');
				$('#cais-quota-info').hide();
				return false;
			}

			$button.prop('disabled', true).text(wpCaisAdmin.strings.testing || 'Testing...');
			$status.html('<span style="color: #666;">Testing...</span>');
			$('#cais-quota-info').hide();

			$.ajax({
				url: wpCaisAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cais_test_api_key',
					nonce: wpCaisAdmin.nonce,
					api_key: apiKey,
					provider: provider
				},
				success: function(response) {
					console.log('API test response:', response);
					if (response.success) {
						$status.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
						// Display quota info if available
						if (response.data.quota) {
							displayQuotaInfo(response.data.quota, provider);
						} else {
							// Try to fetch quota separately
							fetchQuotaInfo(apiKey, provider);
						}
					} else {
						$status.html('<span style="color: red;">✗ ' + (response.data.message || 'Validation failed') + '</span>');
						$('#cais-quota-info').hide();
					}
				},
				error: function(xhr, status, error) {
					console.error('API test error:', { xhr: xhr, status: status, error: error, responseText: xhr.responseText });
					$status.html('<span style="color: red;">✗ Connection error. Please check your network.</span>');
					$('#cais-quota-info').hide();
				},
				complete: function() {
					$button.prop('disabled', false).text('Test API Key');
				}
			});
			
			return false;
		});

		// Auto-fetch quota when API key is entered and provider is selected
		var quotaTimeout;
		$('#ai_api_key, #ai_provider').on('change', function() {
			clearTimeout(quotaTimeout);
			var apiKey = $('#ai_api_key').val();
			var provider = $('#ai_provider').val();
			
			if (apiKey && apiKey.trim() && provider) {
				quotaTimeout = setTimeout(function() {
					fetchQuotaInfo(apiKey, provider);
				}, 1000);
			} else {
				$('#cais-quota-info').hide();
			}
		});

		// Create database table
		$(document).on('click', '#cais-create-table', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var $status = $('#cais-table-status');
			
			$button.prop('disabled', true).text('Creating...');
			$status.html('<span style="color: #666;">Creating table...</span>');
			
			$.ajax({
				url: wpCaisAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'cais_create_table',
					nonce: wpCaisAdmin.nonce
				},
				success: function(response) {
					if (response.success) {
						$status.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
						// Reload page after 1 second to show updated status
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						$status.html('<span style="color: red;">✗ ' + (response.data.message || 'Failed to create table') + '</span>');
						$button.prop('disabled', false).text('Create Cache Table');
					}
				},
				error: function() {
					$status.html('<span style="color: red;">✗ Connection error. Please try again.</span>');
					$button.prop('disabled', false).text('Create Cache Table');
				}
			});
		});
	});

})(jQuery);
