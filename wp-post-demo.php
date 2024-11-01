<?php
/*
Plugin Name: WP Post Demo
Plugin URI: http://www.elegants.biz/wp-post-demo.php
Description: Plug-in to create simple to post demo page that is already published.
Version: 1.5
Author: momen2009
Author URI: http://www.elegants.biz/
License: GPLv2 or later
 */
?>
<?php
function wp_post_demo_save_demo_editor($post_id) {
    if ( isset( $_REQUEST['demo_content'] ) ) {
        update_post_meta( $post_id, '_demo_content', $_REQUEST['demo_content'] );
    }
}

function wp_post_demo_the_content($content){
    global $wpdb;
    $wp_post_demo = $wpdb->escape($_GET["wp-post-demo"]);
    $wp_post_demo_input_pass = get_post_meta(get_the_ID(),"wp_post_demo_input_pass",true);
    if(isset($wp_post_demo_input_pass) && $wp_post_demo_input_pass != ""){
        if($wp_post_demo == $wp_post_demo_input_pass){
            $wp_post_demo = "true";
        }else{
            $wp_post_demo = "false";
        }
    }

    $meta_values = get_post_meta(get_the_ID(), "_demo_content" ,true);
    if($wp_post_demo == "true" && $meta_values != ""){
        if(has_filter('the_content', 'wpautop')){
            $meta_values = wpautop($meta_values);
        }
        $meta_values = str_replace( ']]>', ']]&gt;', $meta_values );
        return $meta_values;
    }
    return $content;
}

function wp_post_demo_add_custom_box() {
    if(function_exists('add_meta_box')) {
        foreach(get_post_types(array("show_ui"=>true)) as $value){
            add_meta_box('wp_post_demo', __('WP Demo Editor', 'wp-post-demo'),'wp_post_demo_inner_custom_box', $value,'advanced');
            add_meta_box('wp_post_demo_input_pass', __('WP Post Demo Password', 'wp-post-demo'),'wp_post_demo_input_pass', $value, 'side');
        }
    }
}

function wp_post_demo_inner_custom_box($post) {
    $wp_post_demo_input_pass = get_post_meta(get_the_ID(),"wp_post_demo_input_pass",true);
    $permalink = "";
    
    if(isset($wp_post_demo_input_pass) && $wp_post_demo_input_pass != ""){
        $permalink = add_query_arg('wp-post-demo', $wp_post_demo_input_pass, get_permalink(get_the_ID()));
    }else{
        $permalink = add_query_arg('wp-post-demo', 'true', get_permalink(get_the_ID()));
    }

    if($post -> post_status != "auto-draft"){
        echo "<script>var wp_post_demo = document.getElementById(\"edit-slug-box\");var wp_post_demo_btn = document.createElement('a');wp_post_demo_btn.innerHTML = \"";
        _e('View Demo','wp-post-demo');
        echo "\";wp_post_demo_btn.setAttribute('class', 'button button-small');wp_post_demo_btn.setAttribute(\"href\",\"";
        echo $permalink;
        echo "\");wp_post_demo_btn.setAttribute('target', '_blank');wp_post_demo.appendChild(wp_post_demo_btn);</script>";
    }
    echo "<p>";
    _e("Enter the contents of the demo editor , you will see the demo page when you click the view demo button after you have saved.","wp_post_demo");
    echo "</p>";
    $_wp_editor_expand = false;
    if ( post_type_supports( $post_type, 'editor' ) && ! wp_is_mobile() &&
         ! ( $is_IE && preg_match( '/MSIE [5678]/', $_SERVER['HTTP_USER_AGENT'] ) ) &&
         apply_filters( 'wp_editor_expand', true ) ) {
        wp_enqueue_script('editor-expand');
        $_wp_editor_expand = ( get_user_setting( 'editor_expand', 'on' ) === 'on' );
    }
    wp_editor(get_post_meta(get_the_ID(),"_demo_content",true), 'demo_content', array(
	'dfw' => true,
	'drag_drop_upload' => true,
	'tabfocus_elements' => 'insert-media-button,save-post',
	'editor_height' => 300,
	'tinymce' => array(
		'resize' => false,
		'wp_autoresize_on' => $_wp_editor_expand,
		'add_unload_trigger' => false,
	),
) );
}

function wp_post_demo_admin_bar_menu($wp_admin_bar){
    if($wp_admin_bar -> get_node("view") != null || $wp_admin_bar -> get_node("edit") != null){
        $title = sprintf(
            '%s',
            __('View Demo','wp-post-demo')
        );
        
        $wp_post_demo_input_pass = get_post_meta(get_the_ID(),"wp_post_demo_input_pass",true);
        $permalink = "";
        
        if(isset($wp_post_demo_input_pass) && $wp_post_demo_input_pass != ""){
            $permalink = add_query_arg('wp-post-demo', $wp_post_demo_input_pass, get_permalink(get_the_ID()));
        }else{
            $permalink = add_query_arg('wp-post-demo', 'true', get_permalink(get_the_ID()));
        }
        $wp_admin_bar->add_menu(array(
            'id'    => 'wp-post-demo',
            'meta'  => array(),
            'title' => $title,
            'href'  => $permalink
        )); 
    }
}

function wp_post_demo_input_pass(){
     global $post;
     wp_nonce_field(wp_create_nonce(__FILE__), 'wp_post_demo_input_pass_nonce');
	 echo '<label class="hidden" for="wp_post_demo_input_pass">WP Post Demo Password</label>';
     echo '<input type="text" name="wp_post_demo_input_pass" value="' . esc_html(get_post_meta($post->ID, 'wp_post_demo_input_pass', true)) . '" style="width:100%">';
     echo '<p>Please enter the password. This password is used for browsing the demo page. If there is no input, you can browse the demo page by entering "true". e.g. http://wordpress_install_domain/page_name/?wp-post-demo=true</p>';
     echo '<style>#metakeyselect option[value="wp_post_demo_input_pass"]{display:none;}</style><script>jQuery(document).ready(function(){jQuery("table input[value=wp_post_demo_input_pass]").parent().parent().css("display","none");});</script>';
}

function wp_post_demo_save_pass($post_id){
	$wp_post_demo_input_pass = isset($_POST['wp_post_demo_input_pass_nonce']) ? $_POST['wp_post_demo_input_pass_nonce'] : null;
	if(!wp_verify_nonce($wp_post_demo_input_pass, wp_create_nonce(__FILE__))) {
		return $post_id;
	}
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return $post_id; }
	if(!current_user_can('edit_post', $post_id)) { return $post_id; }

	$data = $_POST['wp_post_demo_input_pass'];

	if(get_post_meta($post_id, 'wp_post_demo_input_pass') == ""){
		add_post_meta($post_id, 'wp_post_demo_input_pass', $data, true);
	}elseif($data != get_post_meta($post_id, 'wp_post_demo_input_pass', true)){
		update_post_meta($post_id, 'wp_post_demo_input_pass', $data);
	}elseif($data == ""){
		delete_post_meta($post_id, 'wp_post_demo_input_pass', get_post_meta($post_id, 'wp_post_demo_input_pass', true));
	}
}

add_filter("the_content","wp_post_demo_the_content", 10, 3 );
add_action("admin_menu", "wp_post_demo_add_custom_box", 10, 3 );
add_action("save_post","wp_post_demo_save_demo_editor", 10, 3 );
add_action('save_post',"wp_post_demo_save_pass", 10, 3 );
add_action('admin_bar_menu', "wp_post_demo_admin_bar_menu", 9999);
?>