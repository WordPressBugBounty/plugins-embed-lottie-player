<?php
namespace LPB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

/**
 * SubMenu class
 * Registers the Help & Demos dashboard page under the Tools menu.
 *
 * @package LPB\Admin
 */
class SubMenu {
	/**
	 * Constructor.
	 * Registers the admin menu hook.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
	}

	/**
	 * Adds the dashboard submenu page under Tools.
	 *
	 * @return void
	 */
	public function adminMenu(){
		add_submenu_page(
			'tools.php',
			__( 'Lottie Player - bPlugins', 'embed-lottie-player' ),
			__( 'Lottie Player', 'embed-lottie-player' ),
			'manage_options',
			'lottie-player',
			[ \LPBPlugin::class, 'renderDashboard' ]
		);
	}
}
new SubMenu();
