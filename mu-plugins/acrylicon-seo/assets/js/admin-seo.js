/**
 * AcryliCon SEO — Admin Metabox JS
 *
 * Live SERP preview, character counters, regenerate + clear buttons.
 */
(function ($) {
	'use strict';

	var $titleInput, $descInput, $previewTitle, $previewDesc;
	var $titleCounter, $descCounter;
	var debounceTimer;

	function init() {
		$titleInput   = $('#acrylicon_seo_title');
		$descInput    = $('#acrylicon_seo_description');
		$previewTitle = $('#acrylicon-seo-preview-title');
		$previewDesc  = $('#acrylicon-seo-preview-desc');
		$titleCounter = $('#acrylicon-seo-title-counter');
		$descCounter  = $('#acrylicon-seo-desc-counter');

		if (!$titleInput.length) return;

		// Live preview with 50ms debounce
		$titleInput.on('input', function () {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(updatePreview, 50);
		});

		$descInput.on('input', function () {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(updatePreview, 50);
		});

		// Regenerate button
		$('#acrylicon-seo-regenerate').on('click', regenerate);

		// Clear button
		$('#acrylicon-seo-clear').on('click', clearOverrides);

		// Initial counter update
		updateCounters();
	}

	function updatePreview() {
		var title = $titleInput.val() || $titleInput.attr('placeholder');
		var desc  = $descInput.val() || $descInput.attr('placeholder');

		$previewTitle.text(title + ' | AcryliCon');
		$previewDesc.text(desc);

		updateCounters();
	}

	function updateCounters() {
		var titleVal = $titleInput.val() || $titleInput.attr('placeholder') || '';
		var descVal  = $descInput.val() || $descInput.attr('placeholder') || '';

		updateCounter($titleCounter, titleVal.length, 60);
		updateCounter($descCounter, descVal.length, 155);
	}

	function updateCounter($el, len, max) {
		$el.text(len + '/' + max);
		$el.removeClass('warning over');

		if (len > max) {
			$el.addClass('over');
		} else if (len > max * 0.9) {
			$el.addClass('warning');
		}
	}

	function regenerate() {
		var $btn = $(this);
		$btn.prop('disabled', true).text('Regenererer...');

		$.post(acryliconSeo.ajaxUrl, {
			action: 'acrylicon_seo_regenerate',
			nonce: acryliconSeo.nonce,
			post_id: acryliconSeo.postId
		}, function (response) {
			if (response.success) {
				$titleInput.val('').attr('placeholder', response.data.title);
				$descInput.val('').attr('placeholder', response.data.description);
				updatePreview();
			}
			$btn.prop('disabled', false).text('Regenerer');
		}).fail(function () {
			$btn.prop('disabled', false).text('Regenerer');
		});
	}

	function clearOverrides() {
		$titleInput.val('');
		$descInput.val('');
		$('input[name="acrylicon_seo_robots"]').prop('checked', false);
		updatePreview();
	}

	$(document).ready(init);
})(jQuery);
