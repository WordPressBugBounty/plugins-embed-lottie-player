<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

class LPBCustomPost{
	public $post_type = 'lpb';

	public function __construct(){
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
		add_action( 'init', [$this, 'onInit'] );
		add_shortcode( 'lpb', [$this, 'onAddShortcode'] );
		add_filter( 'manage_lpb_posts_columns', [$this, 'manageLPBPostsColumns'], 10 );
		add_action( 'manage_lpb_posts_custom_column', [$this, 'manageLPBPostsCustomColumns'], 10, 2 );
		add_action( 'use_block_editor_for_post', [$this, 'useBlockEditorForPost'], 999, 2 );
		add_filter( 'custom_menu_order', [$this, 'orderSubMenu'] );
	}

	function adminEnqueueScripts( $hook ){
		if( 'edit.php' === $hook || 'post.php' === $hook ){
			wp_enqueue_style( 'lpb-admin-post', LPB_DIR_URL . 'build/admin-post.css', [], LPB_VERSION );
			wp_enqueue_script( 'lpb-admin-post', LPB_DIR_URL . 'build/admin-post.js', [], LPB_VERSION, true );
			wp_set_script_translations( 'lpb-admin-post', 'lottie-player', LPB_DIR_PATH . 'languages' );
		}
	}

	function onInit(){
		$menuIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 44 44'><path fill-rule='evenodd' fill='url(#lottieLogoGradient)' d='M3.252 0A3.252 3.252 0 000 3.252v37.496A3.252 3.252 0 003.252 44h36.887a3.252 3.252 0 003.252-3.252V3.252A3.252 3.252 0 0040.139 0H3.252zM34.9 11.165c.176-1.208-.646-2.33-1.837-2.509-4.495-.674-8.754 3.376-13.297 12.148-3.567 6.888-6.775 10.35-8.96 10.186-1.2-.09-2.245.824-2.334 2.04-.088 1.217.813 2.277 2.013 2.367 4.423.331 8.625-3.822 13.14-12.539 3.535-6.827 6.693-10.147 8.801-9.83 1.19.177 2.298-.656 2.474-1.864z'></path><linearGradient id='lottieLogoGradient' x1='-13.485' y1='25.573' x2='28.352' y2='52.389' gradientUnits='userSpaceOnUse'><stop stop-color='#fff8'></stop><stop offset='1' stop-color='#fff'></stop></linearGradient></svg>";

		register_post_type( 'lpb', [
			'labels'				=> [
				'name'			=> __( 'Lottie Player', 'lottie-player'),
				'singular_name'	=> __( 'Lottie Player', 'lottie-player' ),
				'add_new'		=> __( 'Add New', 'lottie-player' ),
				'add_new_item'	=> __( 'Add New', 'lottie-player' ),
				'edit_item'		=> __( 'Edit', 'lottie-player' ),
				'new_item'		=> __( 'New', 'lottie-player' ),
				'view_item'		=> __( 'View', 'lottie-player' ),
				'search_items'	=> __( 'Search', 'lottie-player'),
				'not_found'		=> __( 'Sorry, we couldn\'t find the that you are looking for.', 'lottie-player' )
			],
			'public'				=> false,
			'show_ui'				=> true, 		
			'show_in_rest'			=> true,							
			'publicly_queryable'	=> false,
			'exclude_from_search'	=> true,
			'menu_position'			=> 14,
			'menu_icon'				=> 'data:image/svg+xml;base64,' . base64_encode( $menuIcon ),		
			'has_archive'			=> false,
			'hierarchical'			=> false,
			'capability_type'		=> 'page',
			'rewrite'				=> [ 'slug' => 'lpb' ],
			'supports'				=> [ 'title', 'editor' ],
			'template'				=> [ ['lpb/lottie-player'] ],
			'template_lock'			=> 'all'
		]); // Register Post Type
	}

	function onAddShortcode( $atts ) {
		$post_id = $atts['id'];
		$post = get_post( $post_id );

		if ( !$post ) {
			return '';
		}

		if ( post_password_required( $post ) ) {
			return get_the_password_form( $post );
		}

		switch ( $post->post_status ) {
			case 'publish':
				return $this->displayContent( $post );

			case 'private':
				if (current_user_can('read_private_posts')) {
					return $this->displayContent( $post );
				}
				return '';

			case 'draft':
			case 'pending':
			case 'future':
				if ( current_user_can( 'edit_post', $post_id ) ) {
					return $this->displayContent( $post );
				}
				return '';

			default:
				return '';
		}
	}

	function displayContent( $post ){
		$blocks = parse_blocks( $post->post_content );
		return render_block( $blocks[0] );
	}

	function manageLPBPostsColumns( $defaults ) {
		unset( $defaults['date'] );
		$defaults['shortcode'] = 'ShortCode';
		$defaults['date'] = 'Date';
		return $defaults;
	}

	function manageLPBPostsCustomColumns( $column_name, $post_ID ) {
		if ( $column_name == 'shortcode' ) {
			echo '<div class="bPlAdminShortcode" id="bPlAdminShortcode-' . esc_attr( $post_ID ) . '">
				<input value="[lpb id=' . esc_attr( $post_ID ) . ']" onclick="copyBPlAdminShortcode(\'' . esc_attr( $post_ID ) . '\')">
				<span class="tooltip">' . esc_html__( 'Copy To Clipboard', 'lottie-player' ) . '</span>
			</div>';
		}
	}

	function useBlockEditorForPost($use, $post){
		if ($this->post_type === $post->post_type) {
			return true;
		}
		return $use;
	}

	function orderSubMenu( $menu_ord ){
		global $submenu;

		$sMenu = $submenu['edit.php?post_type=lpb'] ?? [];
		$arr = [];
		if( lpbIsPremium() ){
			if( isset( $sMenu[5] ) ){
				$arr[] = $sMenu[5]; // Lottie Player
			}
			if( isset( $sMenu[10] ) ){
				$arr[] = $sMenu[10]; // Add New
			}
		}
		if( isset( $sMenu[11] ) ){
			$arr[] = $sMenu[11]; // Help
		}
		if( ( !lpbIsPremium() || LPB_HAS_PRO ) && isset( $sMenu[12] ) ){
			$arr[] = $sMenu[12]; // Upgrade || Pricing || Account
		}
		$submenu['edit.php?post_type=lpb'] = $arr;
	
		return $menu_ord;
	}
}
new LPBCustomPost();