/**
 * @file
 */

(function ($, Drupal, drupalSettings) {

	'use strict';
	Drupal.behaviors.owl = {
		attach: function (context, settings) {
			$('.owl-slider-wrapper', context).each(function () {
				var $this = $(this);
				var $this_settings = $.parseJSON($this.attr('data-settings'));
				console.log($this_settings);
				$this.owlCarousel($this_settings);
			});
		}
	};
})(jQuery, Drupal, drupalSettings);
