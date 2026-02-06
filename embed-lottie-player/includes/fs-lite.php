<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'lpb_fs' ) ) {
	function lpb_fs() {
		global $lpb_fs;

		if ( !isset( $lpb_fs ) ) {
			require_once LPB_DIR_PATH . '/vendor/freemius-lite/start.php';

			$lpb_fs = fs_lite_dynamic_init( [
				'id'					=> '14561',
				'slug'					=> 'embed-lottie-player',
				'__FILE__'				=> LPB_DIR_PATH . 'plugin.php',
				'premium_slug'			=> 'embed-lottie-player-pro',
				'type'					=> 'plugin',
				'public_key'			=> 'pk_8be5ff74d8f915918e0992c8de37c',
				'is_premium'			=> false,
				'premium_suffix'		=> 'Pro',
				'has_premium_version'	=> true,
				'has_addons'			=> false,
				'has_paid_plans'		=> true,
				'menu'					=> [
					'slug'			=> 'lottie-player',
					'first-path'	=> 'tools.php?page=lottie-player',
					'parent'		=> [
						'slug'	=> 'tools.php'
					],
					'contact'		=> false,
					'support'		=> false,
				]
			] );
		}

		return $lpb_fs;
	}

	lpb_fs();
	do_action( 'lpb_fs_loaded' );
}
