<?php
namespace LPB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

class SubMenu {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
	}

	function adminMenu(){
		add_submenu_page(
			'tools.php',
			__('Lottie Player - bPlugins', 'embed-lottie-player'),
			__('Lottie Player', 'embed-lottie-player'),
			'manage_options',
			'lottie-player',
			[ \LPBPlugin::class, 'renderDashboard' ]
		);
	}
}
new SubMenu();