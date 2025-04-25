<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

class LPBUpgradePage{
	public function __construct(){
		add_action( 'admin_menu', [$this, 'adminMenu'] );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
	}

	function adminMenu(){
		add_submenu_page(
			'edit.php?post_type=lpb',
			__( 'Lottie Player - Upgrade', 'lottie-player' ),
			__( 'Upgrade', 'lottie-player' ),
			'manage_options',
			'lpb-upgrade',
			[$this, 'upgradePage']
		);
	}

	function upgradePage(){ ?>
		<div id='bplUpgradePage'></div>
	<?php }

	function adminEnqueueScripts( $hook ) {
		if( strpos( $hook, 'lpb-upgrade' ) ){
			wp_enqueue_script( 'lpb-admin-upgrade', LPB_DIR_URL . 'build/admin-upgrade.js', [ 'react', 'react-dom' ], LPB_VERSION, true );
			wp_set_script_translations( 'lpb-admin-upgrade', 'lottie-player', LPB_DIR_PATH . 'languages' );
		}
	}
}
new LPBUpgradePage;