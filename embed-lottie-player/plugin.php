<?php
/**
 * Plugin Name: Embed Lottie Player - Block
 * Description: Embed Lottie player for display lottie files.
 * Version: 1.2.3
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: lottie-player
   */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'lpb_fs' ) ) {
	lpb_fs()->set_basename( false, __FILE__ );
}else{
	define( 'LPB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && ( 'localhost' === $_SERVER['HTTP_HOST'] || 'plugins.local' === $_SERVER['HTTP_HOST'] ) ? time() : '1.2.3' );
	define( 'LPB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'LPB_DIR_PATH', plugin_dir_path( __FILE__ ) );
	define( 'LPB_HAS_PRO', file_exists( LPB_DIR_PATH . 'vendor/freemius/start.php' ) );

	if ( LPB_HAS_PRO ) {
		require_once LPB_DIR_PATH . 'includes/fs.php';
		require_once LPB_DIR_PATH . 'includes/admin/CPT.php';
		require_once LPB_DIR_PATH . 'includes/LicenseActivation.php';
		require_once LPB_DIR_PATH . 'includes/mimes.php';
	}else{
		require_once LPB_DIR_PATH . 'includes/fs-lite.php';
		require_once LPB_DIR_PATH . 'includes/admin/SubMenu.php';
	}

	function lpbIsPremium(){
		return LPB_HAS_PRO ? lpb_fs()->can_use_premium_code() : false;
	}

	class LPBPlugin{
		function __construct(){
			add_filter( 'plugin_row_meta', [$this, 'pluginRowMeta'], 10, 2 );
			add_action( 'init', [$this, 'onInit'] );
			add_filter( 'block_categories_all', [$this, 'blockCategories'] );
			add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueScripts' ] );
			add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
			add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );

			add_filter( 'plugin_action_links', [$this, 'pluginActionLinks'], 10, 2 );
			add_filter( 'default_title', [$this, 'defaultTitle'], 10, 2 );
			add_filter( 'default_content', [$this, 'defaultContent'], 10, 2 );
		}
		
		function defaultTitle( $title, $post ) {
			if ( 'page' === $post->post_type && isset( $_GET['title'] ) ) {
				$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

				if ( wp_verify_nonce( $nonce, 'lpbCreatePage' ) ) {
					return sanitize_text_field( wp_unslash( $_GET['title'] ) );
				}
			}
			return $title;
		}

		function defaultContent( $content, $post ) {
			if ( 'page' === $post->post_type && isset( $_GET['content'] ) ) {
				$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

				if ( wp_verify_nonce( $nonce, 'lpbCreatePage' ) ) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Content is secured by nonce verification and unslashed to preserve Gutenberg block markup.
					return wp_unslash( $_GET['content'] );
				}
			}
			return $content;
		}

		function pluginActionLinks( $links, $file ) {
			if( plugin_basename( __FILE__ ) === $file ) {
				$helpDemosLink = admin_url( 'edit.php?post_type=lpb&page=lottie-player' );

				$links['help-and-demos'] = sprintf( '<a href="%s" style="%s">%s</a>', $helpDemosLink, 'color:#FF7A00;font-weight:bold', __( 'Help & Demos', 'lottie-player' ) );
			}
 
			return $links;
		}

		function pluginRowMeta( $plugin_meta, $plugin_file ) {
			if ( strpos( $plugin_file, 'embed-lottie-player' ) !== false && time() < strtotime( '2025-12-06' ) ) {
				$new_links = array(
					'deal' => "<a href='https://bplugins.com/coupons/?from=plugins.php&plugin=embed-lottie-player' target='_blank' style='font-weight: 600; color: #146ef5;'>🎉 Black Friday Sale - Get up to 80% OFF Now!</a>"
				);

				$plugin_meta = array_merge( $plugin_meta, $new_links );
			}

			return $plugin_meta;
		}

		function onInit(){
			register_block_type( __DIR__ . '/build' );
		}

		function blockCategories( $categories ){
			return array_merge( [ [
				'slug'	=> 'LPBlock',
				'title'	=> 'Lottie Player Block',
			] ], $categories );
		}

		function adminEnqueueScripts( $hook ) {
			if( strpos( $hook, 'lottie-player' ) ){
				wp_enqueue_style( 'lpb-admin-dashboard', LPB_DIR_URL . 'build/admin/dashboard.css', [], LPB_VERSION );

				$asset_file = include LPB_DIR_PATH . 'build/admin/dashboard.asset.php';
				wp_enqueue_script( 'lpb-admin-dashboard', LPB_DIR_URL . 'build/admin/dashboard.js', array_merge( $asset_file['dependencies'], [ 'wp-util' ] ), LPB_VERSION, true );
				wp_set_script_translations( 'lpb-admin-dashboard', 'lottie-player', LPB_DIR_PATH . 'languages' );
			}
		}

		function enqueueBlockEditorAssets(){
			wp_add_inline_script( 'lpb-lottie-player-editor-script', 'const lpbpipecheck = ' . wp_json_encode( lpbIsPremium() ) .'; const lpbpricingurl = "'. admin_url( LPB_HAS_PRO ? 'edit.php?post_type=lpb&page=lottie-player#/pricing' : 'tools.php?page=lottie-player#/pricing' ) .'";', 'before' );
		}

		function enqueueBlockAssets(){
			wp_register_script( 'dotLottiePlayer', LPB_DIR_URL . '/public/js/dotlottie-player.js', [], '1.5.7', true );
			wp_register_script( 'lottieInteractivity', LPB_DIR_URL . '/public/js/lottie-interactivity.min.js', [ 'dotLottiePlayer' ], '1.5.2', true );
		}

		static function renderDashboard(){ ?>
			<div
				id='lpbDashboard'
				data-info='<?php echo esc_attr( wp_json_encode( [
					'version'	=> LPB_VERSION,
					'isPremium'	=> lpbIsPremium(),
					'hasPro'	=> LPB_HAS_PRO,
					'nonce' => wp_create_nonce( 'lpbCreatePage' ),
					'licenseActiveNonce' => wp_create_nonce( 'bPlLicenseActivation' )
				] ) ); ?>'
			></div>
		<?php }
	}
	new LPBPlugin;
}