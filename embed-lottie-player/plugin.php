<?php
/**
 * Plugin Name: Embed Lottie Player
 * Description: Embed Lottie player for display lottie files.
 * Version: 1.3.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: embed-lottie-player
 * Requires at least: 6.5
 * Tested up to: 6.9
 * Requires PHP: 7.1
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'lpb_fs' ) ) {
	lpb_fs()->set_basename( true, __FILE__ );
} else {
	define( 'LPB_VERSION', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : '1.3.0' );
	define( 'LPB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'LPB_DIR_PATH', plugin_dir_path( __FILE__ ) );

	require_once LPB_DIR_PATH . 'includes/fs-lite.php';
	require_once LPB_DIR_PATH . 'includes/admin/SubMenu.php';

	if( !class_exists( 'LPBPlugin' ) ){
		class LPBPlugin{
			public function __construct(){
				add_action( 'init', [$this, 'onInit'] );
				add_filter( 'block_categories_all', [$this, 'blockCategories'] );
				add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueScripts' ] );
				add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
				add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );

				add_filter( 'plugin_action_links', [$this, 'pluginActionLinks'], 10, 2 );
				add_filter( 'default_title', [$this, 'defaultTitle'], 10, 2 );
				add_filter( 'default_content', [$this, 'defaultContent'], 10, 2 );
			}
			
			public function defaultTitle( $title, $post ) {
				if ( 'page' === $post->post_type && isset( $_GET['title'] ) ) {
					$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

					if ( wp_verify_nonce( $nonce, 'lpbCreatePage' ) ) {
						return sanitize_text_field( wp_unslash( $_GET['title'] ) );
					}
				}
				return $title;
			}

			public function defaultContent( $content, $post ) {
				if ( 'page' === $post->post_type && isset( $_GET['content'] ) ) {
					$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

					if ( wp_verify_nonce( $nonce, 'lpbCreatePage' ) ) {
						return wp_kses_post( wp_unslash( $_GET['content'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					}
				}
				return $content;
			}

			public function pluginActionLinks( $links, $file ) {
				if( plugin_basename( __FILE__ ) === $file ) {
					$helpDemosLink = admin_url( 'tools.php?page=lottie-player#/welcome' );

					$links['help-and-demos'] = sprintf( '<a href="%s" style="%s">%s</a>', $helpDemosLink, 'color:#FF7A00;font-weight:bold', __( 'Help & Demos', 'embed-lottie-player' ) );
				}
	
				return $links;
			}

			public function onInit(){
				register_block_type( __DIR__ . '/build' );
			}

			public function blockCategories( $categories ){
				return array_merge( [ [
					'slug'	=> 'LPBlock',
					'title'	=> 'Lottie Player Block',
				] ], $categories );
			}

			public function adminEnqueueScripts( $hook ) {
				if( strpos( $hook, 'lottie-player' ) ){
					wp_enqueue_style( 'lpb-admin-dashboard', LPB_DIR_URL . 'build/admin/dashboard.css', [], LPB_VERSION );

					$asset_file = include LPB_DIR_PATH . 'build/admin/dashboard.asset.php';
					wp_enqueue_script( 'lpb-admin-dashboard', LPB_DIR_URL . 'build/admin/dashboard.js', array_merge( $asset_file['dependencies'], [ 'wp-util' ] ), LPB_VERSION, true );
					wp_set_script_translations( 'lpb-admin-dashboard', 'embed-lottie-player', LPB_DIR_PATH . 'languages' );
				}
			}

			public function enqueueBlockEditorAssets(){
				wp_add_inline_script( 'lpb-lottie-player-editor-script', 'const lpbpricingurl = "'. admin_url( 'tools.php?page=lottie-player#/pricing' ) .'";', 'before' );
			}

			public function enqueueBlockAssets(){
				wp_register_script( 'dotLottiePlayer', LPB_DIR_URL . '/public/js/dotlottie-player.js', [], '1.5.7', true );
			}

			static function renderDashboard(){ ?>
				<div
					id='lpbDashboard'
					data-info='<?php echo esc_attr( wp_json_encode( [
						'version' => LPB_VERSION,
						'nonce' => wp_create_nonce( 'lpbCreatePage' ),
						'licenseActiveNonce' => wp_create_nonce( 'bPlLicenseActivation' )
					] ) ); ?>'
				></div>
			<?php }
		}
		new LPBPlugin;
	}
}