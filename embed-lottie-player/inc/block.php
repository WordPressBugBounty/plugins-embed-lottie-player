<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

class LPBBlock{
    function __construct(){
		add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
		add_action( 'init', [$this, 'onInit'] );
	}

	function enqueueBlockAssets(){
		wp_register_script( 'dotLottiePlayer', LPB_DIR_URL . '/assets/js/dotlottie-player.js', [], '1.5.7', true );
		wp_register_script( 'lottieInteractivity', LPB_DIR_URL . '/assets/js/lottie-interactivity.min.js', [ 'dotLottiePlayer' ], '1.5.2', true );
	}

	function onInit() {
		wp_register_style( 'lpb-lottie-player-style', LPB_DIR_URL . 'dist/style.css', [], LPB_VERSION ); // Style
		wp_register_style( 'lpb-lottie-player-editor-style', LPB_DIR_URL . 'dist/editor.css', [ 'lpb-lottie-player-style' ], LPB_VERSION ); // Backend Style

		register_block_type( __DIR__, [
			'editor_style'		=> 'lpb-lottie-player-editor-style',
			'render_callback'	=> [$this, 'render']
		] ); // Register Block

		wp_set_script_translations( 'lpb-lottie-player-editor-script', 'lottie-player', LPB_DIR_PATH . 'languages' );
	}

	function render( $attributes ){
		extract( $attributes );

		wp_enqueue_style( 'lpb-lottie-player-style' );
		wp_enqueue_script( 'lpb-lottie-player-script', LPB_DIR_URL . 'dist/script.js', [ 'wp-util', 'react', 'react-dom', 'dotLottiePlayer', 'lottieInteractivity' ], LPB_VERSION, true );
		wp_set_script_translations( 'lpb-lottie-player-script', 'lottie-player', LPB_DIR_PATH . 'languages' );

		$className = $className ?? '';
		$extraClass = lpbIsPremium() ? 'premium' : 'free';
		$blockClassName = "wp-block-lpb-lottie-player $extraClass $className align$align";

		ob_start(); ?>
		<div class='<?php echo esc_attr( $blockClassName ); ?>' id='lpbLottiePlayer-<?php echo esc_attr( $cId ); ?>' data-attributes='<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>'></div>

		<?php return ob_get_clean();
	} // Render
}
new LPBBlock;