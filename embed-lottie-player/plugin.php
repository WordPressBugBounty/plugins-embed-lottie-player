<?php
/**
 * Plugin Name: Embed Lottie Player
 * Description: Embed Lottie player for display lottie files.
 * Version: 1.3.1
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * Plugin URI: https://bplugins.com/products/lottie-player/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: embed-lottie-player
 * Requires at least: 6.5
 * Tested up to: 7.1
 * Requires PHP: 7.4
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'lpb_fs' ) ) {
	lpb_fs()->set_basename( true, __FILE__ );
} else {
	define( 'LPB_VERSION', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : '1.3.1' );
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
				add_action( 'wp_ajax_lpb_dismiss_bfcm_banner', [$this, 'ajaxDismissBFCM'] );
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

			/**
			 * Checks whether the current user has dismissed the BFCM promotion in lpb_options.
			 *
			 * @param int $user_id
			 * @return bool
			 */
			public static function isBFCMPromotionDismissed( $user_id = 0 ) {
				if ( ! $user_id ) {
					$user_id = get_current_user_id();
				}
				if ( ! $user_id ) {
					return false;
				}
				$options   = get_option( 'lpb_options', [] );
				$dismissed = isset( $options['bfcm_2026_dismissed'] ) && is_array( $options['bfcm_2026_dismissed'] )
					? $options['bfcm_2026_dismissed']
					: [];
				return in_array( (int) $user_id, array_map( 'intval', $dismissed ), true );
			}

			/**
			 * Records the user dismissal inside the lpb_options array in wp_options.
			 *
			 * @param int $user_id
			 * @return bool
			 */
			public static function dismissBFCMPromotion( $user_id = 0 ) {
				if ( ! $user_id ) {
					$user_id = get_current_user_id();
				}
				if ( ! $user_id ) {
					return false;
				}
				$options = get_option( 'lpb_options', [] );
				if ( ! is_array( $options ) ) {
					$options = [];
				}
				$dismissed = isset( $options['bfcm_2026_dismissed'] ) && is_array( $options['bfcm_2026_dismissed'] )
					? array_map( 'intval', $options['bfcm_2026_dismissed'] )
					: [];
				$user_id = (int) $user_id;
				if ( ! in_array( $user_id, $dismissed, true ) ) {
					$dismissed[] = $user_id;
					$options['bfcm_2026_dismissed'] = array_values( array_unique( $dismissed ) );
					update_option( 'lpb_options', $options );
				}
				return true;
			}

			/**
			 * Handles AJAX request to dismiss the BFCM promotional banner.
			 *
			 * @return void
			 */
			public function ajaxDismissBFCM() {
				check_ajax_referer( 'lpb_dismiss_bfcm', 'nonce' );
				self::dismissBFCMPromotion();
				wp_send_json_success();
			}

			public function pluginActionLinks( $links, $file ) {
				if( plugin_basename( __FILE__ ) === $file ) {
					$helpDemosLink = admin_url( 'tools.php?page=lottie-player#/welcome' );

					$links['help-and-demos'] = sprintf( '<a href="%s" style="%s">%s</a>', $helpDemosLink, 'color:#FF7A00;font-weight:bold', __( 'Help & Demos', 'embed-lottie-player' ) );

					$isExpired = time() > strtotime( '2026-12-05 23:59:59' );
					$isPremium = function_exists( 'lpb_fs' ) && lpb_fs()->can_use_premium_code();

					if ( ! $isExpired && ! $isPremium ) {
						$bfcmUrl = 'https://bplugins.com/products/lottie-player/pricing/?utm_source=admin&utm_medium=plugins&utm_campaign=bfcm2026';
						$links['bfcm-deal'] = sprintf(
							'<a href="%s" style="%s" target="_blank" rel="noopener noreferrer">%s</a>',
							esc_url( $bfcmUrl ),
							'color: #dc2626; font-weight: 700;',
							__( 'Upgrade to Pro & Save 45% This BFCM!', 'embed-lottie-player' )
						);
					}
				}
	
				return $links;
			}

			public function onInit(){
				register_block_type( __DIR__ . '/build' );

				// Core registers block.json scripts without a path, so local JSONs need this explicitly
				wp_set_script_translations( 'lpb-lottie-player-editor-script', 'embed-lottie-player', LPB_DIR_PATH . 'languages' );
				wp_set_script_translations( 'lpb-lottie-player-view-script', 'embed-lottie-player', LPB_DIR_PATH . 'languages' );
			}

			public function blockCategories( $categories ){
				return array_merge( [ [
					'slug'	=> 'LPBlock',
					'title'	=> __( 'Lottie Player Block', 'embed-lottie-player' ),
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

			/**
			 * Renders the dashboard container for the React app.
			 *
			 * @return void
			 */
			public static function renderDashboard(){ ?>
				<div
					id='lpbDashboard'
					data-info='<?php echo esc_attr( wp_json_encode( [
						'version' => LPB_VERSION,
						'isPremium' => function_exists( 'lpb_fs' ) && lpb_fs()->can_use_premium_code(),
						'isBFCMDismissed' => self::isBFCMPromotionDismissed(),
						'dismissNonce' => wp_create_nonce( 'lpb_dismiss_bfcm' ),
						'adminUrl' => admin_url(),
						'currentUser' => [
							'name' => wp_get_current_user()->display_name,
						],
						'startUrl' => admin_url( 'post-new.php?post_type=page&title=' . rawurlencode( 'Lottie Player' ) . '&content=' . rawurlencode( '<!-- wp:lpb/lottie-player /-->' ) . '&nonce=' . wp_create_nonce( 'lpbCreatePage' ) ),
						'licenseActiveNonce' => wp_create_nonce( 'bPlLicenseActivation' )
					] ) ); ?>'
				></div>
			<?php }
		}
		new LPBPlugin;
	}
}