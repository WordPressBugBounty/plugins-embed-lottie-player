<?php
/**
 * Plugin Name: Lottie Player - Block
 * Description: Lottie player for display lottie files.
 * Version: 1.1.7
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: lottie-player
   */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'lpb_fs' ) || function_exists( 'lpb_init' ) ) {
	register_activation_hook( __FILE__, function () {
		if ( is_plugin_active( 'embed-lottie-player/plugin.php' ) ){
			deactivate_plugins( 'embed-lottie-player/plugin.php' );
		}
		if ( is_plugin_active( 'embed-lottie-player-pro/plugin.php' ) ){
			deactivate_plugins( 'embed-lottie-player-pro/plugin.php' );
		}
	} );
}else{
	define( 'LPB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.1.7' );
	define( 'LPB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'LPB_DIR_PATH', plugin_dir_path( __FILE__ ) );
	define( 'LPB_HAS_FREE', 'embed-lottie-player/plugin.php' === plugin_basename( __FILE__ ) );
	define( 'LPB_HAS_PRO', 'embed-lottie-player-pro/plugin.php' === plugin_basename( __FILE__ ) );

	if( LPB_HAS_FREE ){
		if( !function_exists( 'lpb_init' ) ) {
			function lpb_init() {
				global $lpb_bs;
				require_once( LPB_DIR_PATH . 'bplugins_sdk/init.php' );
				$lpb_bs = new BPlugins_SDK( __FILE__ );
			}
			lpb_init();
		}else {
			$lpb_bs->uninstall_plugin( __FILE__ );
		}
	}

	if ( LPB_HAS_PRO ) {
		require_once LPB_DIR_PATH . 'includes/fs-init.php';

		if( function_exists( 'lpb_fs' ) ){
			lpb_fs()->set_basename( false, __FILE__ );
		}
	}

	function lpbIsPremium(){
		if( LPB_HAS_FREE ){
			global $lpb_bs;
			return $lpb_bs->can_use_premium_feature();
		}

		if ( LPB_HAS_PRO ) {
			return lpb_fs()->can_use_premium_code();
		}
	}

	require_once LPB_DIR_PATH . '/includes/CustomPost.php';
	require_once LPB_DIR_PATH . '/includes/HelpPage.php';

	// if( LPB_HAS_FREE ){
	// 	require_once LPB_DIR_PATH . '/includes/UpgradePage.php';
	// }

	class LPBPlugin{
		function __construct(){
			add_action( 'init', [$this, 'onInit'] );
			add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
			add_action( 'wp_ajax_lpbPipeChecker', [$this, 'lpbPipeChecker'] );
			add_action( 'wp_ajax_nopriv_lpbPipeChecker', [$this, 'lpbPipeChecker'] );
			add_action( 'admin_init', [$this, 'registerSettings'] );
			add_action( 'rest_api_init', [$this, 'registerSettings']);

			add_filter( 'block_categories_all', [$this, 'blockCategories'] );

			add_filter( 'upload_mimes', [$this, 'uploadMimes'] );
			add_filter( 'wp_check_filetype_and_ext', [$this, 'wpCheckFileTypeAndExt'], 10, 5 );
		}

		function onInit(){
			register_block_type( __DIR__ . '/build' );
		}

		function enqueueBlockAssets(){
			wp_register_script( 'dotLottiePlayer', LPB_DIR_URL . '/public/js/dotlottie-player.js', [], '1.5.7', true );
			wp_register_script( 'lottieInteractivity', LPB_DIR_URL . '/public/js/lottie-interactivity.min.js', [ 'dotLottiePlayer' ], '1.5.2', true );
		}

		function lpbPipeChecker(){
			$nonce = $_POST['_wpnonce'] ?? null;

			if( !wp_verify_nonce( $nonce, 'wp_ajax' )){
				wp_send_json_error( 'Invalid Request' );
			}

			wp_send_json_success( [
				'isPipe' => lpbIsPremium()
			] );
		}

		function registerSettings(){
			register_setting( 'lpbUtils', 'lpbUtils', [
				'show_in_rest'		=> [
					'name'			=> 'lpbUtils',
					'schema'		=> [ 'type' => 'string' ]
				],
				'type'				=> 'string',
				'default'			=> wp_json_encode( [ 'nonce' => wp_create_nonce( 'wp_ajax' ) ] ),
				'sanitize_callback'	=> 'sanitize_text_field'
			] );
		}

		function blockCategories( $categories ){
			return array_merge( [[
				'slug'	=> 'LPBlock',
				'title'	=> 'Lottie Player Block',
			] ], $categories );
		} // Categories

		//Allow some additional file types for upload
		function uploadMimes( $mimes ) {
			// New allowed mime types.
			$mimes['json'] = 'application/json';
			$mimes['lottie'] = 'application/json';
			return $mimes;
		}
		function wpCheckFileTypeAndExt( $data, $file, $filename, $mimes, $real_mime = null ){
			// If file extension is 2 or more 
			$f_sp = explode( '.', $filename );
			$f_exp_count = count( $f_sp );

			if( $f_exp_count <= 1 ){
				return $data;
			}else{
				$f_name = $f_sp[0];
				$ext = $f_sp[$f_exp_count - 1];
			}

			if( $ext == 'json' || $ext === 'lottie' ){
				$type = 'application/json';
				$proper_filename = '';
				return compact('ext', 'type', 'proper_filename');
			}else {
				return $data;
			}
		}
	}
	new LPBPlugin;
}