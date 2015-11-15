<?php
/*
Plugin Name: GWP Custom Product Tabs
Plugin URI:
Description: A plugin to add Custom product tabs for WooCommerce
Version: 1.2
Author: Ohad Raz
Author URI: http://generatewp.com
*/
/**
* GWP_Custom_Product_Tabs
*/
class GWP_Custom_Product_Tabs{
    /**
     * $post_type
     * holds custo post type name
     * @var string
     */
    public $post_type = 'c_p_tab';
    /**
     * $id
     * holds settings tab id
     * @var string
     */
    public $id = 'gwp_custom_tabs';
 
    /**
    * __construct
    * class constructor will set the needed filter and action hooks
    */
    function __construct(){
        if (is_admin()){
            //add settings tab
            add_filter( 'woocommerce_settings_tabs_array', array($this,'woocommerce_settings_tabs_array'), 50 );
            //show settings tab
            add_action( 'woocommerce_settings_tabs_'.$this->id, array($this,'show_settings_tab' ));
            //save settings tab
            add_action( 'woocommerce_update_options_'.$this->id, array($this,'update_settings_tab' ));
 
            //add tabs select field
            add_action('woocommerce_admin_field_'.$this->post_type,array($this,'show_'.$this->post_type.'_field' ),10);
            //save tabs select field
            add_action( 'woocommerce_update_option_'.$this->post_type,array($this,'save_'.$this->post_type.'_field' ),10);
 
            //add product tab link in admin
            add_action( 'woocommerce_product_write_panel_tabs', array($this,'woocommerce_product_write_panel_tabs' ));
            //add product tab content in admin
            add_action('woocommerce_product_write_panels', array($this,'woocommerce_product_write_panels'));
            //save product selected tabs
            add_action('woocommerce_process_product_meta', array($this,'woocommerce_process_product_meta'), 10, 2);
        }else{
            //add tabs to product page
            add_filter( 'woocommerce_product_tabs', array($this,'woocommerce_product_tabs') );
        }
        //ajax search handler
        add_action('wp_ajax_woocommerce_json_custom_tabs', array($this,'woocommerce_json_custom_tabs'));
        //register_post_type
        add_action( 'init', array($this,'custom_product_tabs_post_type'), 0 );
    }
 
    /**
     * woocommerce_settings_tabs_array
     * Used to add a WooCommerce settings tab
     * @param  array $settings_tabs
     * @return array
     */
    function woocommerce_settings_tabs_array( $settings_tabs ) {
        $settings_tabs[$this->id] = __('GWP Custom Tabs','GWP');
        return $settings_tabs;
    }
 
    /**
     * show_settings_tab
     * Used to display the WooCommerce settings tab content
     * @return void
     */
    function show_settings_tab(){
        woocommerce_admin_fields($this->get_settings());
    }
 
    /**
     * update_settings_tab
     * Used to save the WooCommerce settings tab values
     * @return void
     */
    function update_settings_tab(){
        woocommerce_update_options($this->get_settings());
    }
 
    /**
     * get_settings
     * Used to define the WooCommerce settings tab fields
     * @return void
     */
    function get_settings(){
        $settings = array(
            'section_title' => array(
                'name'     => __('GWP Custom Tabs','GWP'),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_'.$this->id.'_section_title'
            ),
            'title' => array(
                'name'     => __( 'Global Custom Tabs', 'GWP' ),
                'type'     => $this->post_type,
                'desc'     => __( 'Start typing the Custom Tab name, Used for including custom tabs on all products.', 'GWP' ),
                'desc_tip' => true,
                'default'  => '',
                'id'       => 'wc_'.$this->id.'_globals'
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id'   => 'wc_'.$this->id.'_section_end'
            )
        );
        return apply_filters( 'wc_'.$this->id.'_settings', $settings );
    }
 
    /**
     * show_c_p_tab_field
     * Used to print the settings field of the custom type c_p_tab
     * @param  array $field
     * @return void
     */
    function show_c_p_tab_field($field){
        global $woocommerce;
        ?><tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
                <?php echo '<img class="help_tip" data-tip="' . esc_attr( $field['desc'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />'; ?>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title( $field['type'] ) ?>">
                <p class="form-field custom_product_tabs">
                    <select id="custom_product_tabs" style="width: 50%;" name="<?php echo $field['id'];?>[]" class="ajax_chosen_select_tabs" multiple="multiple" data-placeholder="<?php _e( 'Search for a custom tab&hellip;', 'GWP' ); ?>">
                        <?php   
                            $tabs_ids = get_option($field['id']);
                            $_ids = ! empty( $tabs_ids ) ? array_map( 'absint',  $tabs_ids ) : array();
                            foreach ( $this->get_custom_tabs_list() as $id => $label ) {
                                $selected = in_array($id, $_ids)?  'selected="selected"' : '';
                                echo '<option value="' . esc_attr( $id ) . '"'.$selected.'>' . esc_html( $label ) . '</option>';
                            }
                        ?>
                    </select>
                </p>
            </td>
        </tr><?php
        add_action('admin_footer',array($this,'ajax_footer_js'));
    }
 
    /**
     * save_c_p_tab_field
     * Used to save the settings field of the custom type c_p_tab
     * @param  array $field
     * @return void
     */
    function save_c_p_tab_field($field){
        if (isset($_POST[$field['id']])){
            $option_value =   $_POST[$field['id']];
            update_option($field['id'],$option_value);
        }else{
            delete_option($field['id']);
        }
    }
 
    /**
     * ajax_footer_js
     * Used to add needed javascript to product edit screen and custom settings tab
     * @return void
     */
    function ajax_footer_js(){
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            // Ajax Chosen Product Selectors
            jQuery("select.ajax_chosen_select_tabs").select2({});
        });
        </script>
        <?php
    }
 
    /**
     * woocommerce_product_write_panel_tabs
     * Used to add a product custom tab to product edit screen
     * @return void
     */
    function woocommerce_product_write_panel_tabs(){
        ?>
        <li class="custom_tab">
            <a href="#custom_tab_data_ctabs">
                <?php _e('Custom Tabs', 'GWP'); ?>
            </a>
        </li>
        <?php
    }
 
    /**
     * woocommerce_product_write_panels
     * Used to display a product custom tab content (fields) to product edit screen
     * @return void
     */
    function woocommerce_product_write_panels() {
        global $post,$woocommerce;
        $fields = array(
            array(
                'key'   => 'custom_tabs_ids',
                'label' => __( 'Select Custom Tabs', 'GWP' ),
                'desc'  => __( 'Start typing the Custom Tab name, Used for including custom tabs.', 'GWP' )
            ),
            array(
                'key'   => 'exclude_custom_tabs_ids',
                'label' => __( 'Select Global Tabs to exclude', 'GWP' ),
                'desc'  => __( 'Start typing the Custom Tab name. used for excluding global tabs.', 'GWP' )
            )
        );
        ?>
        <div id="custom_tab_data_ctabs" class="panel woocommerce_options_panel">
            <?php
            foreach ($fields as $f) {
                $tabs_ids = get_post_meta( $post->ID, $f['key'], true );
                $_ids = ! empty( $tabs_ids ) ? array_map( 'absint',  $tabs_ids ) : array();
                ?>
                <div class="options_group">
                    <p class="form-field custom_product_tabs">
                        <label for="custom_product_tabs"><?php echo $f['label']; ?></label>
                        <select style="width: 50%;" id="<?php echo $f['key']; ?>" name="<?php echo $f['key']; ?>[]" class="ajax_chosen_select_tabs" multiple="multiple" data-placeholder="<?php _e( 'Search for a custom tab&hellip;', 'GWP' ); ?>">
                            <?php                           
                                foreach ( $this->get_custom_tabs_list() as $id => $label ) {
                                    $selected = in_array($id, $_ids)?  'selected="selected"' : '';
                                    echo '<option value="' . esc_attr( $id ) . '"'.$selected.'>' . esc_html( $label ) . '</option>';
                                }
                            ?>
                        </select> <img class="help_tip" data-tip="<?php echo esc_attr($f['desc']); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </p>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        add_action('admin_footer',array($this,'ajax_footer_js'));
    }
 
    /**
     * woocommerce_process_product_meta
     * used to save product custom tabs meta
     * @param  int $post_id
     * @return void
     */
    function woocommerce_process_product_meta( $post_id ) {
        foreach (array('exclude_custom_tabs_ids','custom_tabs_ids') as $key) {
            if (isset($_POST[$key]))
                update_post_meta( $post_id, $key, $_POST[$key]);
            else
                delete_post_meta( $post_id, $key);
        }  
    }
     
    /**
     * woocommerce_json_custom_tabs
     * An AJAX handler to list tabs for tabs field
     * prints out json of {tab_id: tab_name}
     * @return void
     */
    function woocommerce_json_custom_tabs(){
        check_ajax_referer( 'search-products-tabs', 'security' );
        header( 'Content-Type: application/json; charset=utf-8' );
        $term = (string) urldecode(stripslashes(strip_tags($_GET['term'])));
        if (empty($term)) die();
        $post_types = array($this->post_type);
        if ( is_numeric( $term ) ) {
            //by tab id
            $args = array(
                'post_type'      => $post_types,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'post__in'       => array(0, $term),
                'fields'         => 'ids'
            );
 
            $args2 = array(
                'post_type'      => $post_types,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'post_parent'    => $term,
                'fields'         => 'ids'
            );
 
            $posts = array_unique(array_merge( get_posts( $args ), get_posts( $args2 )));
 
        } else {
            //by name
            $args = array(
                'post_type'      => $post_types,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                's'              => $term,
                'fields'         => 'ids'
            );
            $posts = array_unique( get_posts( $args ) );
        }
 
        $found_tabs = array();
 
        if ( $posts ) foreach ( $posts as $post_id ) {
 
            $found_tabs[ $post_id ] = get_the_title($post_id);
        }
         
        $found_tabs = apply_filters( 'woocommerce_json_search_found_tabs', $found_tabs );
        echo json_encode( $found_tabs );
 
        die();
    }
 
    /**
     * woocommerce_product_tabs
     * Used to add tabs to product view page
     * @param  array $tabs
     * @return array
     */
    function woocommerce_product_tabs($tabs){
        global $post;
        //get global tabs
        $global_tabs = get_option('wc_'.$this->id.'_globals');
        $global_tabs_ids = ! empty( $global_tabs ) ? array_map( 'absint',  $global_tabs ) : array();
 
        //get tabs to exclude from this product
        $exclude_tabs = get_post_meta( $post->ID, 'exclude_custom_tabs_ids', true );
        $exclude_tabs_ids = ! empty($exclude_tabs  ) ? array_map( 'absint',  $exclude_tabs ) : array();
 
        //get tabs to include with current product
        $product_tabs = get_post_meta( $post->ID, 'custom_tabs_ids', true );
        $_ids = ! empty($product_tabs  ) ? array_map( 'absint',  $product_tabs ) : null;
 
        //combine global and product specific tabs and remove excluded tabs
        $_ids = array_merge((array)$_ids,(array)array_diff((array)$global_tabs_ids, (array)$exclude_tabs_ids));
 
        if ($_ids){
            //fix order
            $_ids = array_reverse($_ids);
            //loop over tabs and add them
            foreach ($_ids as $id) {
            	if ($this->post_exists($id)){
					$display_title = get_post_meta($id,'tab_display_title',true);
					$priority      = get_post_meta($id,'tab_priority',true);
	                $tabs['customtab_'.$id] = array(
	                    'title'    => ( !empty($display_title)? $display_title : get_the_title($id) ),
	                    'priority' => ( !empty($priority)? $priority : 50 ),
	                    'callback' => array($this,'render_tab'),
	                    'content'  => apply_filters('the_content',get_post_field( 'post_content', $id)) //this allows shortcodes in custom tabs
	                );
            	}
            }
        }
        return $tabs;
    }
 
    /**
     * render_tab
     * Used to render tabs on product view page
     * @param  string $key
     * @param  array  $tab
     * @return void
     */
    function render_tab($key,$tab){
        global $post;
        echo '<h2>'.apply_filters('GWP_custom_tab_title',$tab['title'],$tab,$key).'</h2>';
        echo apply_filters('GWP_custom_tab_content',$tab['content'],$tab,$key);
    }
 
    /**
     * custom_product_tabs_post_type
     * Register custom tabs Post Type
     * @return void
     */
    function custom_product_tabs_post_type() {
        $labels = array(
            'name'                => _x( 'Product Tabs', 'Post Type General Name', 'GWP' ),
            'singular_name'       => _x( 'Product Tab', 'Post Type Singular Name', 'GWP' ),
            'menu_name'           => __( 'product Tabs', 'GWP' ),
            'parent_item_colon'   => __( '', 'GWP' ),
            'all_items'           => __( 'Product Tabs', 'GWP' ),
            'view_item'           => __( '', 'GWP' ),
            'add_new_item'        => __( 'Add Product Tab', 'GWP' ),
            'add_new'             => __( 'Add New', 'GWP' ),
            'edit_item'           => __( 'Edit Product Tab', 'GWP' ),
            'update_item'         => __( 'Update Product Tab', 'GWP' ),
            'search_items'        => __( 'Search Product Tab', 'GWP' ),
            'not_found'           => __( 'Not found', 'GWP' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'GWP' ),
        );
        $args = array(
            'label'               => __( 'Product Tabs', 'GWP' ),
            'description'         => __( 'Custom Product Tabs', 'GWP' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor', 'custom-fields' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=product',
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-feedback',
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
        );
        register_post_type( 'c_p_tab', $args );
    }

    function post_exists($post_id){
    	return is_string(get_post_status( $post_id ) );
    }

    /**
     * get_custom_tabs_list
     * @since 1.2
     * @return array
     */
    function get_custom_tabs_list(){
        $args = array(
            'post_type'      => array($this->post_type),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids'
        );
        $found_tabs = array();
        $posts = get_posts($args);
        if ( $posts ) foreach ( $posts as $post_id ) {
 
            $found_tabs[ $post_id ] = get_the_title($post_id);
        }
        return $found_tabs;
    }
}//end GWP_Custom_Product_Tabs class.
new GWP_Custom_Product_Tabs();