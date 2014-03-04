<?php
/*
Plugin Name: MZP TM WIDGET
*/

/* Widget class */
class WP_Widget_mzp_tm extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'widget_mzp_tm', 'description' => __( 'HTML w/visual editor', 'mzp-tm-widget' ) );
		$control_ops = array( 'width' => 800, 'height' => 800 );
		parent::__construct( 'mzp-tm', __( 'Visual Editor', 'mzp-tm-widget' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		if ( get_option( 'embed_autourls' ) ) {
			$wp_embed = $GLOBALS['wp_embed'];
			add_filter( 'widget_text', array( $wp_embed, 'run_shortcode' ), 8 );
			add_filter( 'widget_text', array( $wp_embed, 'autoembed' ), 8 );
		}
		extract( $args );
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base );
		$text = apply_filters( 'widget_text', $instance['text'], $instance );
		if ( function_exists( 'icl_t' ) ) {
			$title = icl_t( "Widgets", 'widget title - ' . md5 ( $title ), $title );
			$text = icl_t( "Widgets", 'widget body - ' . $this->id_base . '-' . $this->number, $text );
		}
		$text = do_shortcode( $text );
		echo $before_widget;
		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}
?>
			<div class="textwidget"><?php echo $text; ?></div>
<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		if ( current_user_can('unfiltered_html') ) {
			$instance['text'] =  $new_instance['text'];
		}
		else {
			$instance['text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['text']) ) ); // wp_filter_post_kses() expects slashed
		}
		$instance['type'] = strip_tags( $new_instance['type'] );
		if ( function_exists( 'icl_register_string' )) {
			//icl_register_string( "Widgets", 'widget title - ' . $this->id_base . '-' . $this->number /* md5 ( apply_filters( 'widget_title', $instance['title'] ))*/, apply_filters( 'widget_title', $instance['title'] ) ); // This is handled automatically by WPML
			icl_register_string( "Widgets", 'widget body - ' . $this->id_base . '-' . $this->number, apply_filters( 'widget_text', $instance['text'] ) );
		}
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '', 'type' => 'visual' ) );
		$title = strip_tags( $instance['title'] );
		if ( function_exists( 'esc_textarea' ) ) {
			$text = esc_textarea( $instance['text'] );
		}
		else {
			$text = stripslashes( wp_filter_post_kses( addslashes( $instance['text'] ) ) );
		}
		$type = esc_attr( $instance['type'] );
		if ( get_bloginfo( 'version' ) < "3.5" ) {
			$toggle_buttons_extra_class = "editor_toggle_buttons_legacy";
			$media_buttons_extra_class = "editor_media_buttons_legacy";
		}
		else {
			$toggle_buttons_extra_class = "wp-toggle-buttons";
			$media_buttons_extra_class = "wp-media-buttons";
		}
?>
		<input id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="hidden" value="<?php echo esc_attr( $type ); ?>" />
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
		<div class="editor_toggle_buttons hide-if-no-js <?php echo $toggle_buttons_extra_class; ?>">
			<a id="widget-<?php echo $this->id_base; ?>-<?php echo $this->number; ?>-html"<?php if ( $type == 'html' ) {?> class="active"<?php }?>><?php _e( 'HTML' ); ?></a>
			<a id="widget-<?php echo $this->id_base; ?>-<?php echo $this->number; ?>-visual"<?php if ( $type == 'visual' ) {?> class="active"<?php }?>><?php _e(' Visual' ); ?></a>
		</div>
		<div class="editor_media_buttons hide-if-no-js <?php echo $media_buttons_extra_class; ?>">
			<?php do_action( 'media_buttons' ); ?>
		</div>
		<div class="editor_container">
			<textarea class="widefat" rows="20" cols="40" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>
		</div>		
<?php
	}
}

/* Load localization */
load_plugin_textdomain( 'mzp-tm-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 

/* Widget initialization */
add_action( 'widgets_init', 'mzp_tm_widgets_init' );
function mzp_tm_widgets_init() {
	if ( ! is_blog_installed() )
		return;
	register_widget( 'WP_Widget_mzp_tm' );
}

/* Add actions and filters (only in widgets admin page) */
add_action( 'admin_init', 'mzp_tm_admin_init' );
function mzp_tm_admin_init() {
	global $pagenow;
	$load_editor = false;
	if ( $pagenow == "widgets.php" ) {
		$load_editor = true;
	}
	// Compatibility for WP Page Widget plugin
	if ( is_plugin_active('wp-page-widget/wp-page-widgets.php' ) && (
			( in_array( $pagenow, array( 'post-new.php', 'post.php') ) ) ||
			( in_array( $pagenow, array( 'edit-tags.php' ) ) && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) || 
			( in_array( $pagenow, array( 'admin.php' ) ) && isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'pw-front-page', 'pw-search-page' ) ) )
	) ) {
		$load_editor = true;
	}
	if ( $load_editor ) {
		add_action( 'admin_head', 'mzp_tm_load_tiny_mce' );
		add_filter( 'tiny_mce_before_init', 'mzp_tm_init_editor', 20 );
		add_action( 'admin_print_scripts', 'mzp_tm_scripts' );
		add_action( 'admin_print_styles', 'mzp_tm_styles' );
		add_action( 'admin_print_footer_scripts', 'mzp_tm_footer_scripts' );
		add_filter( 'atd_load_scripts', '__return_true'); // Compatibility with Jetpack After the deadline
	}
}

/* Instantiate tm editor */
function mzp_tm_load_tiny_mce() {
	// Remove filters added from "After the deadline" plugin, to avoid conflicts
	// Add support for thickbox media dialog
	add_thickbox();
	// New media modal dialog (WP 3.5+)
	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media(); 
	}
}

/* tm setup customization */
function mzp_tm_init_editor( $initArray ) {
	global $pagenow;
	// Remove WP fullscreen mode and set the native tm fullscreen mode
	if ( get_bloginfo( 'version' ) < "3.3" ) {
		$plugins = explode(',', $initArray['plugins']);
		if ( isset( $plugins['wpfullscreen'] ) ) {
			unset( $plugins['wpfullscreen'] );
		}
		if ( ! isset( $plugins['fullscreen'] ) ) {
			$plugins[] = 'fullscreen';
		}
		$initArray['plugins'] = implode( ',', $plugins );
	}
	// Remove the "More" toolbar button (only in widget screen)
	if ( $pagenow == "widgets.php" ) {
		$initArray['theme_advanced_buttons1'] = str_replace( ',wp_more', '', $initArray['theme_advanced_buttons1'] );
	}
	// Do not remove linebreaks
	$initArray['remove_linebreaks'] = false;
	// Convert newline characters to BR tags
	$initArray['convert_newlines_to_brs'] = false; 
	// Force P newlines
	$initArray['force_p_newlines'] = true; 
	// Force P newlines
	$initArray['force_br_newlines'] = false; 
	// Do not remove redundant BR tags
	$initArray['remove_redundant_brs'] = false;
	// Force p block
	$initArray['forced_root_block'] = 'p';
	// Apply source formatting
	$initArray['apply_source_formatting '] = true;
	// Return modified settings
	return $initArray;
}

/* Widget js loading */
function mzp_tm_scripts() {
	global $mzp_tm_widget_version, $mzp_tm_widget_dev_mode;
	wp_enqueue_script('media-upload');
	if ( get_bloginfo( 'version' ) >= "3.3" ) {
		wp_enqueue_script( 'wplink' );
		wp_enqueue_script( 'wpdialogs-popup' );
		wp_enqueue_script( 'mzp-tm-widget', plugins_url('mzp-tm-widget' . ($mzp_tm_widget_dev_mode ? '.dev' : '' ) . '.js', __FILE__ ), array( 'jquery' ), $mzp_tm_widget_version );
	}
	else {
		wp_enqueue_script( 'mzp-tm-widget-legacy', plugins_url('mzp-tm-widget-legacy' . ($mzp_tm_widget_dev_mode? '.dev' : '' ) . '.js', __FILE__ ), array( 'jquery' ), $mzp_tm_widget_version );
	}
}

/* Widget css loading */
function mzp_tm_styles() {
	global $mzp_tm_widget_version;
	if ( get_bloginfo( 'version' ) < "3.3" ) {
		wp_enqueue_style( 'thickbox' );
	}
	else {
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}
	wp_print_styles( 'editor-buttons' );
    wp_enqueue_style( 'mzp-tm-widget', plugins_url( 'mzp-tm-widget.css', __FILE__ ), array(), $mzp_tm_widget_version );
}


/* Footer script */
function mzp_tm_footer_scripts() {
	// Setup for WP 3.1 and previous versions
	if ( get_bloginfo( 'version' ) < "3.2" ) {
		if ( function_exists( 'wp_tiny_mce' ) ) {
			wp_tiny_mce( false, array() );
		}
		if ( function_exists( 'wp_tiny_mce_preload_dialogs' ) ) {
			wp_tiny_mce_preload_dialogs();
		}
	}
	// Setup for WP 3.2.x
	else if ( get_bloginfo( 'version' ) < "3.3" ) {
		if ( function_exists( 'wp_tiny_mce' ) ) {
			wp_tiny_mce( false, array() );
		}
		if ( function_exists( 'wp_preload_dialogs') ) {
			wp_preload_dialogs( array( 'plugins' => 'wpdialogs,wplink,wpfullscreen' ) );
		}
	}
	// Setup for WP 3.3 - New Editor API
	else {
		wp_editor( '', 'mzp-tm-widget' );
	}
}

