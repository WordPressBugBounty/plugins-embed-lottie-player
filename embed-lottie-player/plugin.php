<?php

/**
 * Plugin Name: Embed Lottie Player - Block
 * Description: Embed Lottie player for display lottie files.
 * Version: 1.2.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: lottie-player
 */
// ABS PATH
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( function_exists( 'lpb_fs' ) ) {
    lpb_fs()->set_basename( false, __FILE__ );
} else {
    define( 'LPB_VERSION', ( isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.2.0' ) );
    define( 'LPB_DIR_URL', plugin_dir_url( __FILE__ ) );
    define( 'LPB_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'LPB_HAS_PRO', file_exists( dirname( __FILE__ ) . '/freemius/start.php' ) );
    if ( !function_exists( 'lpb_fs' ) ) {
        function lpb_fs() {
            global $lpb_fs;
            if ( !isset( $lpb_fs ) ) {
                if ( LPB_HAS_PRO ) {
                    require_once dirname( __FILE__ ) . '/freemius/start.php';
                } else {
                    require_once dirname( __FILE__ ) . '/bplugins_sdk/init.php';
                }
                $lpbConfig = [
                    'id'                  => '14561',
                    'slug'                => 'embed-lottie-player',
                    'premium_slug'        => 'embed-lottie-player-pro',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_8be5ff74d8f915918e0992c8de37c',
                    'is_premium'          => true,
                    'premium_suffix'      => 'Pro',
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'trial'               => [
                        'days'               => 7,
                        'is_require_payment' => false,
                    ],
                    'menu'                => [
                        'slug'    => 'edit.php?post_type=lpb',
                        'contact' => false,
                        'support' => false,
                    ],
                ];
                $lpb_fs = ( LPB_HAS_PRO ? fs_dynamic_init( $lpbConfig ) : fs_lite_dynamic_init( $lpbConfig ) );
            }
            return $lpb_fs;
        }

        lpb_fs();
        do_action( 'lpb_fs_loaded' );
    }
    if ( LPB_HAS_PRO ) {
        require_once LPB_DIR_PATH . 'includes/mimes.php';
    }
    require_once LPB_DIR_PATH . '/includes/CustomPost.php';
    require_once LPB_DIR_PATH . '/includes/HelpPage.php';
    function lpbIsPremium() {
        return ( LPB_HAS_PRO ? lpb_fs()->can_use_premium_code() : false );
    }

    class LPBPlugin {
        function __construct() {
            add_action( 'init', [$this, 'onInit'] );
            add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
            add_action( 'wp_ajax_lpbPipeChecker', [$this, 'lpbPipeChecker'] );
            add_action( 'wp_ajax_nopriv_lpbPipeChecker', [$this, 'lpbPipeChecker'] );
            add_action( 'admin_init', [$this, 'registerSettings'] );
            add_action( 'rest_api_init', [$this, 'registerSettings'] );
            add_filter( 'block_categories_all', [$this, 'blockCategories'] );
        }

        function onInit() {
            register_block_type( __DIR__ . '/build' );
        }

        function enqueueBlockAssets() {
            wp_register_script(
                'dotLottiePlayer',
                LPB_DIR_URL . '/public/js/dotlottie-player.js',
                [],
                '1.5.7',
                true
            );
            wp_register_script(
                'lottieInteractivity',
                LPB_DIR_URL . '/public/js/lottie-interactivity.min.js',
                ['dotLottiePlayer'],
                '1.5.2',
                true
            );
        }

        function lpbPipeChecker() {
            $nonce = $_POST['_wpnonce'] ?? null;
            if ( !wp_verify_nonce( $nonce, 'wp_ajax' ) ) {
                wp_send_json_error( 'Invalid Request' );
            }
            wp_send_json_success( [
                'isPipe' => lpbIsPremium(),
            ] );
        }

        function registerSettings() {
            register_setting( 'lpbUtils', 'lpbUtils', [
                'show_in_rest'      => [
                    'name'   => 'lpbUtils',
                    'schema' => [
                        'type' => 'string',
                    ],
                ],
                'type'              => 'string',
                'default'           => wp_json_encode( [
                    'nonce' => wp_create_nonce( 'wp_ajax' ),
                ] ),
                'sanitize_callback' => 'sanitize_text_field',
            ] );
        }

        function blockCategories( $categories ) {
            return array_merge( [[
                'slug'  => 'LPBlock',
                'title' => 'Lottie Player Block',
            ]], $categories );
        }

        // Categories
    }

    new LPBPlugin();
}