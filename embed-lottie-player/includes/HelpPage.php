<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

class LPBHelpPage{
	public function __construct(){
		add_action( 'admin_menu', [$this, 'adminMenu'] );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
	}

	function adminMenu(){
		add_submenu_page(
			'edit.php?post_type=lpb',
			__( 'Lottie Player - Help', 'lottie-player' ),
			__( 'Help', 'lottie-player' ),
			'manage_options',
			'lpb-help',
			[$this, 'helpPage']
		);
	}

	function helpPage(){ ?>
		<div id='bplAdminHelpPage'></div>
	<?php }

	function adminEnqueueScripts( $hook ) {
		if( strpos( $hook, 'lpb-help' ) ){
			wp_enqueue_style( 'lpb-admin-help', LPB_DIR_URL . 'build/admin-help.css', [], LPB_VERSION );
			wp_enqueue_script( 'lpb-admin-help', LPB_DIR_URL . 'build/admin-help.js', [ 'react', 'react-dom' ], LPB_VERSION, true );
			wp_set_script_translations( 'lpb-admin-help', 'lottie-player', LPB_DIR_PATH . 'languages' );
		}
	}
}
new LPBHelpPage;