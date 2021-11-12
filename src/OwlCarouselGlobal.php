<?php


namespace Drupal\owlcarousel;


class OwlCarouselGlobal {

	/**
 	* Default settings for owl.
 	*/
	public static function defaultSettings($key = NULL) {
		$settings = [
			'image_style' => '',
			'image_link' => '',
			'items' => 3,
			'margin' => '0',
			'nav' => FALSE,
			'autoplay' => FALSE,
			'autoplayHoverPause' => FALSE,
			'dots' => FALSE,
		];

		return isset($settings[$key]) ? $settings[$key] : $settings;
	}

	/**
 	* Return formatted js array of settings.
 	*/
	public static function formatSettings($settings) {
		$settings['items'] = (int) $settings['items'];

		$settings['margin'] = (int) $settings['margin'];
		$settings['nav'] = (bool) $settings['nav'];
		$settings['autoplay'] = (bool) $settings['autoplay'];
		$settings['autoplayHoverPause'] = (bool) $settings['autoplayHoverPause'];
		$settings['dots'] = (bool) $settings['dots'];


		if (isset($settings['image_style'])) {
			unset($settings['image_style']);
		}
		if (isset($settings['image_link'])) {
			unset($settings['image_link']);
		}

		return $settings;
	}


}
