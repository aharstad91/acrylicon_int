jQuery(document).ready(function($) {
	$('#acrylicon-sync-button').on('click', function() {
		var button = $(this);
		var postId = button.data('post-id');
		var targetBlogId = $('#target_blog_id').val();
		var spinner = $('#sync-spinner');
		var resultDiv = $('#sync-result');

		// Validation
		if (!targetBlogId) {
			alert('Vennligst velg en target site');
			return;
		}

		// Disable button and show spinner
		button.prop('disabled', true);
		spinner.show();
		resultDiv.html('').removeClass('success error');

		// AJAX request
		$.ajax({
			url: acrylicon_sync.ajax_url,
			type: 'POST',
			data: {
				action: 'acrylicon_sync_post',
				nonce: acrylicon_sync.nonce,
				post_id: postId,
				target_blog_id: targetBlogId
			},
			success: function(response) {
				spinner.hide();
				button.prop('disabled', false);

				if (response.success) {
					resultDiv.addClass('success').html(
						'<strong>✓ ' + response.data.message + '</strong><br>' +
						'Ny post ID: ' + response.data.post_id
					);

					if (response.data.errors && response.data.errors.length > 0) {
						resultDiv.append('<br><br><strong>Advarsler:</strong><ul>');
						response.data.errors.forEach(function(error) {
							resultDiv.append('<li>' + error + '</li>');
						});
						resultDiv.append('</ul>');
					}

					// Reload page after 2 seconds to show updated status
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					resultDiv.addClass('error').html(
						'<strong>✗ ' + response.data.message + '</strong>'
					);
				}
			},
			error: function(xhr, status, error) {
				spinner.hide();
				button.prop('disabled', false);
				resultDiv.addClass('error').html(
					'<strong>✗ AJAX feil:</strong> ' + error
				);
			}
		});
	});
});
