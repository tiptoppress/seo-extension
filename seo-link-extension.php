<?php
/*
Plugin Name: SEO and Link Add-on - for the Term and Category Based Posts Widget
Plugin URI: http://tiptoppress.com/downloads/term-and-category-based-posts-widget/
Description: SEO on-page optimization and gather clicks with Google Analytic for the premium widget Term and Category Based Posts Widget.
Author: TipTopPress
Version: 0.2
Author URI: http://tiptoppress.com
*/

namespace termCategoryPostsPro\seoExtension;

// Don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

const TEXTDOMAIN     = 'seo-link-extension';
const MINBASEVERSION = '4.7.1';


function smashing_save_post_class_meta( $post_id, $post ) {

	/* Verify the nonce before proceeding. */
	if ( !isset( $_POST['smashing_post_class_nonce'] ) || !wp_verify_nonce( $_POST['smashing_post_class_nonce'], basename( __FILE__ ) ) )
	return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
	return $post_id;

	$url_options = array('class', 'target');
	foreach ($url_options as $option)
	{
		/* Get the posted data and sanitize it for use as an HTML class. */
		$new_meta_value = ( isset( $_POST['smashing-post-' . $option] ) ? sanitize_html_class( $_POST['smashing-post-' . $option] ) : '' );

		/* Get the meta keys. */
		$meta_key = 'smashing_post_' . $option;

		/* Get the meta value of the custom field key. */
		$meta_value = get_post_meta( $post_id, $meta_key, true );

		if ( $new_meta_value && '' == $meta_value )
		{
			/* If a new meta value was added and there was no previous value, add it. */
			add_post_meta( $post_id, $meta_key, $new_meta_value, true );
		}
		elseif ( $new_meta_value && $new_meta_value != $meta_value )
		{
			/* If the new meta value does not match the old value, update it. */
			update_post_meta( $post_id, $meta_key, $new_meta_value );
		}
		elseif ( '' == $new_meta_value && $meta_value )
		{
			/* If there is no new meta value but an old value exists, delete it. */
			delete_post_meta( $post_id, $meta_key, $meta_value );
		}
	}
}

function smashing_post_class_meta_box( $post ) { ?>

  <?php wp_nonce_field( basename( __FILE__ ), 'smashing_post_class_nonce' ); ?>

  <p>
    <label for="smashing-post-class"><?php _e( "Custom URL", 'seo-link-extension' ); ?></label>
    <br />
    <input class="widefat" type="text" name="smashing-post-class" id="smashing-post-class" value="<?php echo esc_attr( get_post_meta( $post->ID, 'smashing_post_class', true ) ); ?>" size="30" />
  </p>
  <p>
    <label for="smashing-post-target">
		<input class="widefat" type="checkbox" name="smashing-post-target" id="smashing-post-target" <?php checked( (bool) get_post_meta( $post->ID, 'smashing_post_target' ), 1 ); ?> />	
		<?php _e( "Open link in a new window", 'seo-link-extension' ); ?>
	</label>
  </p>
<?php }

function smashing_add_post_meta_boxes() {

	add_meta_box(
		'smashing-post-class',							// Unique ID
		esc_html__( 'Custom link', 'example' ),			// Title
		__NAMESPACE__.'\smashing_post_class_meta_box',	// Callback function
		'post',											// Admin page (or post type)
		'side',											// Context
		'default'										// Priority
	);
}

/* Meta box setup function. */
function smashing_post_meta_boxes_setup() {

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', __NAMESPACE__.'\smashing_add_post_meta_boxes' );

	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', __NAMESPACE__.'\smashing_save_post_class_meta', 10, 2 );
}

/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', __NAMESPACE__.'\smashing_post_meta_boxes_setup' );
add_action( 'load-post-new.php', __NAMESPACE__.'\smashing_post_meta_boxes_setup' );


/**
 * Filter to add rel attribute to all widget links and make other website links more important
 *
 * @param  array $instance Array which contains the various settings
 * @return string with the anchor attribute
 *
 * @since 4.8
 */
function title_link_filter($html,$widget,$instance) {
	global $post;

	if (isset($instance['title_links']) && $instance['title_links'] == "no_links")
	{
		// remove href, if exist	
		if (preg_match('/href="[^"]+"/',$html))
		{
			$html = preg_replace('/href="[^"]+"/', "", $html);
		}

		// change inline anchor to inline span element (start- and end tag)
		$html = str_replace('<a ','<span ',$html);
		$html = str_replace('</a>','</span>',$html);
	}
	else if (isset($instance['title_links']) && $instance['title_links'] == "custom_links")
	{
		// set new URL link
		if (preg_match('/href="[^"]+"/',$html))
		{
			// retrieve the global notice for the current post;
			$post_class = get_post_meta( $post->ID, 'smashing_post_class', true );		
			$html = preg_replace('/href="[^"]+"/', " href='" . $post_class . "' ", $html);
		}

		$post_target = get_post_meta( $post->ID, 'smashing_post_target', true );	
		if(isset($post_target) && $post_target)
		{
			$html = str_replace('<a ','<a target="_blank" ',$html);
		}
	}
	return $html;
}

add_filter('cpwp_post_html',__NAMESPACE__.'\title_link_filter',10,3);


/**
 * Filter to add rel attribute to all widget links and make other website links more important
 *
 * @param  array $instance Array which contains the various settings
 * @return string with the anchor attribute
 *
 * @since 4.8
 */
function search_engine_attribute_filter($html,$widget,$instance) {

	if (isset($instance['search_engine_attribute']) && $instance['search_engine_attribute'] != 'none') {
		// remove old rel, if exist	
		if (preg_match('/(.*)rel=".*"(.*)/',$html))
			$html = preg_replace('/rel=".*"/', "", $html);
			
		// add attribute
		switch ($instance['search_engine_attribute']) {
			case 'canonical':
				$html = str_replace('<a ','<a rel="canonical" ',$html);
				break;
			case 'nofollow':
				$html = str_replace('<a ','<a rel="nofollow" ',$html);
				break;
		}
	}
	return $html;
}

add_filter('cpwp_post_html',__NAMESPACE__.'\search_engine_attribute_filter',10,3);


 /**
 * Check the Term and Category based Posts Widget version
 *
 *  @return Base widget supporteds this Extension version
 *
 */
function version_check( $min_base_version = MINBASEVERSION ) {	
	$min_base_version = explode('.', $min_base_version);
	
	if ( !defined( '\termcategoryPostsPro\VERSION' ) ) return false;
	$installed_base_version = explode('.', \termcategoryPostsPro\VERSION);

	$ret = ($min_base_version[0] < $installed_base_version[0]) ||
			($min_base_version[0] == $installed_base_version[0] && $min_base_version[1] <= $installed_base_version[1]);
	
	return $ret;
}

 /**
 * Write admin notice if a higher version is needed
 *
 */
function version_notice() {
	if ( ! version_check() ) {
		?>
		<div class="update-nag notice">
			<p><?php printf( __( 'The SEO-Link Extension needs the Term and Category based Posts Wiedget version %s or higher. It is possible that some features are not available. Please <a href="%s">update</a>.', 'category-posts' ), MINBASEVERSION, admin_url('plugins.php') ); ?></p>
		</div>
		<?php
	}
}

add_action( 'admin_notices', __NAMESPACE__.'\version_notice' );

/**
 * Panel "More Excerpt Options"
 *
 * @param this
 * @param instance
 * @param panel_id
 * @param panel_name
 * @param alt_prefix
 * @return true: override the widget panel
 *
 */
function form_seo_panel_filter($widget,$instance) {

	if ( ! version_check( "4.7.1" ) ) {
		return;
	}

	$instance = wp_parse_args( ( array ) $instance, array(	
		// extension options
		'search_engine_attribute'         => 'none',
		'title_links'                     => 'default_links',
	) );
	
	// extension options
	$search_engine_attribute         = $instance['search_engine_attribute'];
	$title_links                     = $instance['title_links'];

	?>
	<h4 data-panel="seo"><?php _e('SEO','categorypostspro')?></h4>
	<div>
		<?php if ( version_check( "4.7.1" ) ) : ?>
		<p>
			<label for="<?php echo $widget->get_field_id("title_links_default_links"); ?>">
				<input type="radio" value="default_links" class="checkbox" id="<?php echo $widget->get_field_id("title_links_default_links"); ?>" name="<?php echo $widget->get_field_name("title_links"); ?>"<?php if($instance["title_links"] === 'default_links'){ echo 'checked="checked"'; }; ?> />
				<?php _e( 'Normal WordPress URL','seo-link-extension' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id("title_links_no_links"); ?>">
				<input type="radio" value="no_links" class="checkbox" id="<?php echo $widget->get_field_id("title_links_no_links"); ?>" name="<?php echo $widget->get_field_name("title_links"); ?>"<?php if($instance["title_links"] === 'no_links'){ echo 'checked="checked"'; }; ?> />
				<?php _e( 'No links','seo-link-extension' ); ?>
			</label>
		</p>
		<p>
			<label for="<?php echo $widget->get_field_id("title_links_custom_links"); ?>">
				<input type="radio" value="custom_links" class="checkbox" id="<?php echo $widget->get_field_id("title_links_custom_links"); ?>" name="<?php echo $widget->get_field_name("title_links"); ?>"<?php if($instance["title_links"] === 'custom_links'){ echo 'checked="checked"'; }; ?> />
				<?php _e( 'Custom links (set in post admin)','seo-link-extension' ); ?>
			</label>
		</p>
		<?php endif; ?>
		<p>
			<label for="<?php echo $widget->get_field_id("search_engine_attribute"); ?>">
				<?php _e( 'SEO friendly URLs:','seo-link-extension' ); ?>
				<select id="<?php echo $widget->get_field_id("search_engine_attribute"); ?>" name="<?php echo $widget->get_field_name("search_engine_attribute"); ?>">
					<option value="none" <?php selected($search_engine_attribute, 'none')?>><?php _e( 'None', 'category-posts' ); ?></option>
					<option value="canonical" <?php selected($search_engine_attribute, 'canonical')?>><?php _e( 'canonical', 'category-posts' ); ?></option>
					<option value="nofollow" <?php selected($search_engine_attribute, 'nofollow')?>><?php _e( 'nofollow', 'category-posts' ); ?></option>
				</select>
			</label>
		</p>
	</div>
	<?php
}

add_filter('cpwp_after_general_panel',__NAMESPACE__.'\form_seo_panel_filter',10,5);

/**
 * Filter for the shortcode settings
 *
 * @param shortcode settings
 *
 */
function cpwp_default_settings($setting) {

	return wp_parse_args( ( array ) $setting, array(
		'search_engine_attribute'         => 'none',
		'title_links'                     => 'default_links',
	) );
}

add_filter('cpwp_default_settings',__NAMESPACE__.'\cpwp_default_settings');

// Plugin action links section

/**
 *  Applied to the list of links to display on the plugins page (beside the activate/deactivate links).
 *  
 *  @return array of the widget links
 *  
 *  @since 0.1
 */
function add_action_links ( $links ) {
    $pro_link = array(
        '<a target="_blank" href="http://tiptoppress.com/term-and-category-based-posts-widget/?utm_source=widget_seoext&utm_campaign=get_pro_seoext&utm_medium=action_link">'.__('Get the pro widget needed for this extension','category-posts').'</a>',
    );
	
	$links = array_merge($pro_link, $links);
    
    return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), __NAMESPACE__.'\add_action_links' );
