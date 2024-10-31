<?php
/*
Plugin Name: Pencil Wiki
Description: Pencil Wiki is a simple wiki solution for your Wordpress.
Version: 1.0.7
Author: G.Breant
Author URI: http://pencil2d.org
Plugin URI: http://wordpress.org/extend/plugins/pencil-wiki
License: GPL2
*/
// The register_post_type() function is not to be used before the 'init'.


/* Here's how to create your customized labels */


class Pencil_Wiki {
	/** Version ***************************************************************/

	/**
	 * @public string plugin version
	 */
	public $version = '1.0.7';

	/**
	 * @public string plugin DB version
	 */
	public $db_version = '100';
	
	/** Paths *****************************************************************/

	public $file = '';
	
	/**
	 * @public string Basename of the plugin directory
	 */
	public $basename = '';

	/**
	 * @public string Absolute path to the plugin directory
	 */
	public $plugin_dir = '';
        
	/**
	 * @public string Absolute path to the plugin theme directory
	 */
        public $templates_dir = '';
        
	/**
	 * @var Pencil Wiki The one true Pencil Wiki
	 */
	private static $instance;
        
        
        public $post_type_slug='';
        public $post_type_labels=array();
        public $locked_post_meta_key='';
        public $locked_branch_post_meta_key='';
        public $message_code='';

	/**
	 * Main bbPress Instance
	 *
	 * bbPress is fun
	 * Please load it only one time
	 * For this, we thank you
	 *
	 * Insures that only one instance of bbPress exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since bbPress (r3757)
	 * @staticvar array $instance
	 * @uses bbPress::setup_globals() Setup the globals needed
	 * @uses bbPress::includes() Include the required files
	 * @uses bbPress::setup_actions() Setup the hooks and actions
	 * @see bbpress()
	 * @return The one true bbPress
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Pencil_Wiki;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}
        
	/**
	 * A dummy constructor to prevent bbPress from being loaded more than once.
	 *
	 * @since bbPress (r2464)
	 * @see bbPress::instance()
	 * @see bbpress();
	 */
	private function __construct() { /* Do nothing here */ }
        
	function setup_globals() {

		/** Paths *************************************************************/
		$this->file       = __FILE__;
		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url ( $this->file );
                $this->templates_dir = $this->plugin_dir . '_inc/theme-default/';
                
                $this->locked_post_meta_key='pwiki_locked';
                $this->locked_branch_post_meta_key='pwiki_branch_locked';
                $this->post_type_slug='wiki_page';
                $this->post_type_labels=array(
                        'name' => _x( 'Wiki Pages', 'post type general name', 'pencil-wiki' ),
                        'singular_name' => _x( 'Wiki Page', 'post type singular name', 'pencil-wiki' ),
                        'add_new' => _x( 'Add New', 'wik page', 'pencil-wiki' ),
                        'add_new_item' => __( 'Add New Wiki Page', 'pencil-wiki' ),
                        'edit_item' => __( 'Edit Wiki Page', 'pencil-wiki' ),
                        'new_item' => __( 'New Wiki Page', 'pencil-wiki' ),
                        'view_item' => __( 'View Wiki Page', 'pencil-wiki' ),
                        'search_items' => __( 'Search Wiki Pages', 'pencil-wiki' ),
                        'not_found' =>  __( 'No wiki pages found', 'pencil-wiki' ),
                        'not_found_in_trash' => __( 'No wiki pages found in Trash', 'pencil-wiki' ),
                        'parent_item_colon' => ''
                );  
	}
        
	function includes(){
            
            require( $this->plugin_dir . 'pwiki-widgets.php'   );
            require( $this->plugin_dir . 'pwiki-template.php'   );
            

            require( $this->plugin_dir . 'pwiki-revisions.php'   );
	}
	
	function setup_actions(){
            
            //PAGES LOCK (adapted from the Lock Pages plugin from Steve Taylor)
            add_filter( 'user_has_cap', array( &$this, 'prevent_post_edition' ), 0, 3 );
            add_filter( 'manage_wiki_page_posts_columns', array( &$this, 'pages_list_col' ) );
            add_action( 'manage_wiki_page_posts_custom_column', array( &$this, 'pages_list_col_value' ), 10, 2 );

            add_action ('admin_init', array( &$this, 'admin_init' ));
            

            
            //BOTH
            add_action( 'init', array( $this, 'register_post_type' ) );
            add_action('init', array( $this, 'set_roles' ) );//roles @ capabilities

            //in pwiki-widgets.php
            add_action( 'widgets_init', 'pwiki_register_widget_add_page_link' );
            add_action( 'widgets_init', 'pwiki_register_widget_edit_page_link' );
            add_action( 'widgets_init', 'pwiki_register_widget_search' );
            add_action( 'widgets_init', 'pwiki_register_widget_tree' );
            
            //add_action( 'save_post', 'pencil_wiki_force_parent_page' );
            //add_action( 'save_post', 'pencil_wiki_force_revision_comment' );
            
            //FRONTEND
            
            add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );//scripts + styles
            
            add_filter('template_include', array( $this, 'search_results_template' ) );//scripts + styles
            
            //check for empty content
            add_filter( 'the_content', array( $this, 'empty_post_message' ) ); 

            
            //localization
            add_action('init', array($this, 'load_plugin_textdomain'));
            
	}
        
        public function load_plugin_textdomain(){
            load_plugin_textdomain('pencil-wiki', FALSE, $this->plugin_dir.'/languages/');
        }
        

        
        function admin_init(){
            //meta boxes
            remove_meta_box( 'pageparentdiv' , 'wiki_page' , 'normal' );//we will move the page parent box into the pencil wiki box
            add_meta_box( 'pwiki_meta-box',__('Pencil Wiki','pencil-wiki'), array( &$this, 'main_meta_box' ), 'wiki_page', 'side', 'default' );

            add_action( 'save_post', array( &$this, 'meta_box_save' ), 1, 2 );

            add_action( 'edit_form_advanced', array( &$this, 'old_value_fields' ) );
            add_filter('wp_insert_post_data', array( &$this, 'validate_wiki_post' ),99,2);

            add_action('admin_notices', array( $this, 'no_templates_warning' ) );
            add_action('admin_notices', array( $this, 'post_updated_messages' ) );
        }
 


        
        function register_post_type() {

                // Create an array for the $args
                $args = array(
                        'labels' => $this->post_type_labels, /* NOTICE: the $labels variable is used here... */
                        'description'=>'',
                        'public' => true,
                        'publicly_queryable' => true,
                        'show_ui' => true, 
                        'query_var' => true,
                        'rewrite' => array('slug' => 'wiki'),
                        'capability_type' => 'wiki_page',
                        'map_meta_cap' => true,
                        'hierarchical' => true,
                        'menu_position' => 20,
                        'supports' => array( 'title', 'editor', 'author', 'excerpt', 'revisions', 'page-attributes')
                ); 
                register_post_type( $this->post_type_slug, $args ); /* Register it and move on */
        }

        
        function set_roles(){
            global $wp_roles;
            if ( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();

            //CREATE NEW ROLE
            $subscriber = $wp_roles->get_role('subscriber');//get subscriber

            $wp_roles->add_role('wiki_author',__('Wiki Author','pencil-wiki'), $subscriber->capabilities);//clone contributor


            $wiki_caps=array(
                'read_private_wiki_pages'=>array('administrator','editor'),
                'edit_private_wiki_pages'=>array('administrator','editor'),
                'delete_published_wiki_pages'=>array('administrator','editor'),
                'delete_others_wiki_pages'=>array('administrator','editor'),
                'delete_private_wiki_pages'=>array('administrator','editor'),

                'edit_wiki_pages'=>array('administrator','editor','author','wiki_author'),
                'edit_others_wiki_pages'=>array('administrator','editor','author','wiki_author'),
                'edit_published_wiki_pages'=>array('administrator','editor','author','wiki_author'),
                'delete_wiki_pages'=>array('administrator','editor','author','wiki_author'),//(not published)
                'publish_wiki_pages'=>array('administrator','editor','author','wiki_author'),
                
                'edit_root_wiki_pages'=>array('administrator','editor'),
                'lock_wiki_pages'=>array('administrator','editor')
            );

            foreach ($wiki_caps as $wiki_cap=>$roles){
                foreach ($roles as $role){
                    $wp_roles->add_cap( $role, $wiki_cap );
                }
            }

        }

        
        function scripts_styles() {
            wp_register_style( 'pwiki-style', $this->plugin_url . '_inc/pencil-wiki.css' );
            wp_enqueue_style( 'pwiki-style' );
        }
        
        
        
        function has_missing_templates(){
            //get theme filenames
            
            $copy_dir = $this->plugin_dir . 'theme';

            if(is_dir($copy_dir)){
                $dir = opendir($copy_dir); 

                while ($file = readdir($dir)) { 
                    if (eregi("\.php",$file)) { /* Look for files with .png extension */
                        $filenames[]=$file;
                    }
                }

                closedir($dir);
            }

            if (!$filenames)return false;
            
            
            
            //check each file exists
            foreach($filenames as $filename){
                if (!locate_template((array)$filename ) ) return true;
            }
            return false;
        }
        
        function no_templates_warning() {
            
            if (!$this->has_missing_templates()) return;

            $plugin_theme_path_suffix_split = explode(ABSPATH,$this->plugin_dir);
            $plugin_theme_path_suffix = $plugin_theme_path_suffix_split[1];
            
            $template_path_suffix_split = explode(ABSPATH,get_stylesheet_directory());
            $template_path_suffix = $template_path_suffix_split[1];
            
            

            echo "
            <div id='pwiki-warning' class='updated fade'><p><strong>".__('Pencil Wiki is almost ready.','pencil-wiki')."</strong> ".sprintf(__('You need to copy the files from <em>%1s</em> to your current theme directory (<em>%2s</em>).'),$plugin_theme_path_suffix,$template_path_suffix)."</p></div>
            ";
        }
        
        /**
         * Add a pwiki_message code in the url when post update has a problem.
         * @param type $location
         * @return type 
         */
        
        function validation_message_redirect($location) {
            remove_filter('redirect_post_location', array(&$this,'validation_message_redirect'));
            
            if(!$this->message_code) return $location;
            
            $location = add_query_arg('pwiki_message',$this->message_code, $location);
            return $location;
        }
        
        /**
         * Display post update error
         * @return boolean 
         */
        
        function post_updated_messages(){
            $screen = get_current_screen();
            // Check screen base and current post type
            if ( 'post' != $screen->base || $this->post_type_slug != $screen->post_type ) return false;

            $message_code = $_REQUEST['pwiki_message'];

            if (!$message_code) return false;
            
                
            $messages=array(
                1=>array('error',__('You cannot change the post parent of a top-level wiki page','pencil-wiki')),
                2=>array('error',__('You have not the capability to set top-level wiki page.','pencil-wiki')),
                3=>array('error',__('Please set a page parent.  Page has not been published.','pencil-wiki'))
                
            );
            
            $message = $messages[$message_code];
            
            $classes=array();
            $classes[]='updated';
            $classes[]='fade';
            $classes[]=$message[0];
            if($classes) $classes_str=' class="'.implode(" ",$classes).'"';
            

            echo '<div id="pwiki-warning"'.$classes_str.'"><p>';
            echo $message[1];
            echo '</p></div>';
            
        }
        
       ///LOCKED PAGES
        
        /**
        * Check if post is locked (with or without children)
        *
        * @global type $post
        * @param type $post_id
        * @return type 
        */
        
        function has_meta_lock( $post_id ) {
                global $post;
                if ($this->has_meta_postlock( $post_id )) return true;
                if ($this->has_meta_branchlock( $post_id )) return true;
        }
        
        /**
        * Check if post is locked (without children)
        *
        * @global type $post
        * @param type $post_id
        * @return type 
        */
        
        function has_meta_postlock( $post_id ) {
                global $post;
                if ( !$post_id ) $post_id=$post->ID;
                $meta = get_post_meta($post_id,$this->locked_post_meta_key,true);
                return (bool)$meta;
        }
        
        /**
        * Check if post is locked (including children)
        *
        * @global type $post
        * @param type $post_id
        * @return type 
        */
        
        function has_meta_branchlock( $post_id ) {
                global $post;
                if ( !$post_id ) $post_id=$post->ID;
                $meta = get_post_meta($post_id,$this->locked_branch_post_meta_key,true);
                return (bool)$meta;
        }
        
        /**
        * Check if ancestor has children locked
         *
         * @global type $post
         * @param type $post_id 
         */
        
        function is_post_parent_branchlocked( $post_id ) {
                global $post;
                if ( !$post_id ) $post_id=$post->ID;
                $parents_ids = get_post_ancestors( $post );
                
                foreach((array)$parents_ids as $parent_id){

                    if($this->has_meta_branchlock($parent_id)) return true;
                }
        }
        
        /**
        * Check if post is locked or ancestor has children locked
        *
        * @param type $post_id
        * @return boolean 
        */
        function is_post_locked( $post_id ) {
            if ($this->has_meta_lock( $post_id )) return true;
            if ($this->is_post_parent_branchlocked( $post_id )) return true;

            return false;
        }
        
        /**
        * Add lock column to admin pages list.
        *
        * @param	array		$cols		The columns
        * @return	array
        */
        function pages_list_col( $cols ) {
                $cols["page-locked"] = "Lock";
                return $cols;
        }

        /**
        * Add lock indicator to admin pages list.
        *
        * @param		string		$column_name		The column name
        * @param		int			$id					Page ID
        */
        function pages_list_col_value( $column_name, $id ) {
            if ( !$column_name == "page-locked" ) return false;
            
            if($this->is_post_parent_branchlocked( $id )){
                echo '<img src="' . $this->plugin_url . '_inc/images/lock-grey.png' . '" width="16" height="16" alt="'.__('Parent Locked','pencil-wiki').'" />';
            }elseif($this->has_meta_lock( $id )){
                echo '<img src="' . $this->plugin_url . '_inc/images/lock.png' . '" width="16" height="16" alt="'.__('Post Locked','pencil-wiki').'" />';
            }else{
                echo '&nbsp;';
            }
        }
        
        
        /**
         *Prevents unauthorized users deleting a locked page.
         *
         * @global type $post
         * @param type $allcaps
         * @param type $caps
         * @param type $args
         * @return type 
         */
        function prevent_post_edition( $allcaps, $caps, $args ) {
            global $post;
            $cap_check = count( $args ) ? $args[0] : '';
            $user_id = count( $args ) > 1 ? $args[1] : 0;
            $post_id = count( $args ) > 2 ? $args[2] : 0;
            // Is the check for deleting a page?
            if ( ( $cap_check == "delete_wiki_page" || $cap_check == "edit_wiki_page" ) && $post_id && is_object( $post ) && property_exists( $post, 'post_type' ) && $post->post_type == $this->post_type_slug ) {
                    // Basic check for "edit locked wiki page" capability
                    $user_can = array_key_exists('lock_wiki_pages', $allcaps ) && $allcaps[ 'lock_wiki_pages' ];
                    // Override it if page isn't locked and scope isn't all pages
                    if ( !$this->is_post_locked( $post_id ) )
                            $user_can = true;
                    // If user isn't able to touch this page, remove delete capabilities
                    if ( ! $user_can ) {
                            unset($allcaps[$cap]);
                            
                            foreach( $allcaps as $cap => $value ) {
                                    if ( strpos( $cap, "wiki_pages" ) !== false && ( strpos( $cap, "edit_" ) !== false || strpos( $cap, "delete_" ) !== false ) )
                                            unset( $allcaps[$cap] );
                            }
                    }
            }
            return $allcaps;  
        }


        /**
        * Controls the display of the page locking meta box.
        *
        * @since	0.1
        * @global	$post
        */
        function main_meta_box() {
            global $post;

            
            //WIKI LOCKING
            if (get_post_status()!='auto-draft'){
                if ( current_user_can( 'lock_wiki_pages' ) ) {
                        global $post; 

                        $parent_locked = $this->is_post_parent_branchlocked( $post->ID );

                        ?>

                        <p>
                            <strong><?php _e( 'Locking', 'pwiki' ); ?></strong><br/>
                            <label for="pwiki_locked">
                                    <input type="checkbox" name="pwiki_locked" id="pwiki_locked"<?php if (( $this->has_meta_postlock( $post->ID ) ) || ( $parent_locked )) echo ' checked="checked"'; ?> value="true"<?php if ( $parent_locked ) echo ' disabled="disabled"'; ?> />
                                    <?php _e( 'Lock this page', 'pwiki' ); ?>
                            </label><br/>

                            <label for="pwiki_branch_locked">
                                    <input type="checkbox" name="pwiki_branch_locked" id="pwiki_locked_branch"<?php if (( $this->has_meta_branchlock( $post->ID ) ) || ( $parent_locked )) echo ' checked="checked"'; ?> value="true"<?php if ( $parent_locked ) echo ' disabled="disabled"'; ?> />
                                    <?php _e( 'Lock page & children', 'pwiki' ); ?>
                            </label>
                        </p>

                        <?php 
                        //box are disabled when parent is branchloked, so force to send the values with hidden fields.
                        if ($parent_locked){
                            if (( $this->has_meta_branchlock( $post->ID ) )){
                                ?>
                                <input type="hidden" name="pwiki_locked" value="true" />
                                <?php
                            }elseif (( $this->has_meta_postlock( $post->ID ) )){
                                ?>
                                <input type="hidden" name="pwiki_branch_locked" value="true" />
                                <?php
                            }

                        }


                }
            }

            //REVISION REASON
            pwiki_revisions_metabox_block();
            
            //PAGE PARENT (core function)
            page_attributes_meta_box($post);

            //NONCE
            wp_nonce_field( wp_create_nonce($this->basename), 'pwiki_meta_nonce' );               
        }
        

        
        function meta_box_save( $post_id, $post ) {
            

            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;


            // verify this came from the our screen and with proper authorization,
            // because save_post can be triggered at other times
            //TO FIX NOT WORKING
            //if ( !wp_verify_nonce( $_POST['pwiki_meta_nonce'], $this->basename ) ) return;

            // Check permissions
            if ( $this->post_type_slug == $_POST['post_type'] ){
                if ( !current_user_can( 'edit_wiki_page', $post_id ) )
                    return;
            }
            
            //save revision log
            pwiki_revisions_save( $post_id, $post );

            
            //save locking
            $this->lock_meta_save( $post_id, $post );
            

        }

        
	/**
        * Saves the page locking metabox data to a custom field.
        *
        * @since	0.1
        * @uses		current_user_can()
        */
        function lock_meta_save( $post_id, $post ) {

                /* Block:
                - Users who can't change locked pages
                - Users who can't edit pages
                - Revisions, autoupdates, quick edits, posts etc.
                - Simple Page Ordering plugin
                */
                if (
                        ( ! current_user_can( 'lock_wiki_pages' ) ) ||
                        ( ! current_user_can( 'edit_wiki_pages', $post_id ) ) ||
                        ( $post->post_type != $this->post_type_slug ) ||
                        isset( $_POST["_inline_edit"] ) ||
                        ( isset( $_REQUEST["action"] ) && $_REQUEST["action"] == 'simple_page_ordering'  ) //TO FIX TO CHECK simple_page_ordering
                )
                        return;
                
                //PAGE
                if ( $_POST['pwiki_locked'] ) { //box checked
                    add_post_meta($post_id, $this->locked_post_meta_key,true,true);
                } else {
                    delete_post_meta($post_id, $this->locked_post_meta_key);
                }
                
                //PAGE
                $has_meta_postlock = $this->has_meta_postlock( $post_id );
                $has_meta_branchlock = $this->has_meta_branchlock( $post_id );

                if ( $_POST['pwiki_branch_locked'] ){
                    add_post_meta($post_id, $this->locked_branch_post_meta_key,true,true);
                } else {
                    delete_post_meta($post_id, $this->locked_branch_post_meta_key);
                }
        }

        
        /**
        * Stores old values for locked fields in hidden fields on the wiki page edit form (creation/edition of a post).
         *
         * @global type $post
         * @return boolean 
         */
        function old_value_fields() {
                global $post;
                if ($post->post_type!=$this->post_type_slug) return false;

                echo '<input type="hidden" name="pwiki_old_parent" value="' . esc_attr( $post->post_parent ) . '" />';
        }

        
        function validate_wiki_post($data, $postarr) {
            
            //TO FIX
            //SHOULD NOT FIRE WHEN CLICKING ON "ADD POST" page
            //SHOULD differentiate new post / updating post

            $old_parent_value = $_POST['pwiki_old_parent'];
            
            // verify this is not an auto save routine. 
            if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $data;
            
            //is a draft (do not use $post)
            if (($_POST['post_status']=='draft') || ($_POST['post_status']=='auto-draft')) return $data;
            
            //not a wiki post
            if ($data['post_type']!=$this->post_type_slug) return $data;

            //is root but current user has capability
            if (current_user_can('edit_root_wiki_pages')) return $data;
            
            if ($data['post_parent']==0){
                
                    $data['post_parent'] = $old_parent_value;
                    $this->message_code=2;
                    
                    if (!$data['post_parent']){
                        
                        if (!user_can($_POST['post_author'],'edit_root_wiki_pages')){
                            $data['post_status'] ='draft';
                            $this->message_code=3;
                        }
                        
                    }


            }

            if($this->message_code){
                //http://stackoverflow.com/questions/6152315/wordpress-how-displaying-custom-error-message-in-wp-insert-post-data
                add_filter('redirect_post_location', array(&$this,'validation_message_redirect'));
            }
            
            return $data;
        }
        
        function search_results_template($template){
            global $wp_query;

            if( !$wp_query->is_search) return $template;
            if (get_query_var('post_type') != $this->post_type_slug ) return $template;

            return locate_template('archive-wiki_page.php');
        }
        
        /**
         * Displays a message if a post is empty.
         * @param type $content
         * @return type 
         */
        
        function empty_post_message($content) {
            global $post;
            
            if ($post->post_type != $this->post_type_slug ) return $content;
            
            if($content) return $content;
            
            $subpages_args = array('child_of'=>$post->ID,'post_type'=>$post->post_type,'parent'=>$post->ID);
            $children = get_pages($subpages_args);
            $children_count = count($children);

            if($children_count){
                 $rootpage = pwiki_get_root_page();
                 $index_link_txt = sprintf(__('%s Index','pencil-wiki'),$rootpage->post_title);
                 $message = sprintf(__('This page is empty but has %1d subpages.  Check the %2s !','pencil-wiki'),$children_count,'<em>'.$index_link_txt.'</em>');
            }else{
                $message = __('This page is empty.','pencil-wiki');
            }
            
                
            $messageblock = '<p class="pwiki_message">'.$message.'</p>';
            return $messageblock.$content;
        }
        
        function has_children($post=false) {
            if(!$post){
                global $post;
            }

            $children = get_pages("child_of=$post->ID&post_type=".$this->post_type_slug);
            if( count( $children ) != 0 ) { return true; } // Has Children
            else { return false; } // No children
            }

       
}

/**
 * The main function responsible for returning the one true bbPress Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $pencil_wiki = pencil_wiki(); ?>
 *
 * @return The one true Pencil Wiki Instance
 */

function pencil_wiki() {
	return Pencil_Wiki::instance();
}

pencil_wiki();


class Pencil_Wiki_Walker_Page extends Walker_Page {
    function start_lvl(&$output, $depth) {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul>\n";
    }
    function start_el(&$output, $page, $depth, $args, $current_page) {
        global $post;
        
        $classes=array();
        $class_attr = '';
        
        if ( $depth )
            $indent = str_repeat("\t", $depth);
        else
            $indent = '';
        extract($args, EXTR_SKIP);

        if(in_array($page->ID,(array)$post->ancestors)){
            $classes[] = 'pwiki-menu-ancestor';
        }

        if($page->ID==$post->ID){
            $classes[] = 'pwiki-menu-current';
        }
        
        
        if ($classes) {
            $class_attr = ' class="' . implode(" ",$classes) . '"';
        }
        $output .= $indent . '<li><a' . $class_attr . ' href="' . get_permalink($page->ID) . '"' . $class_attr . '>' . $link_before . apply_filters( 'the_title', $page->post_title, $page->ID ) . $link_after . '</a>';

        if ( !empty($show_date) ) {
            if ( 'modified' == $show_date )
                $time = $page->post_modified;
            else
                $time = $page->post_date;
            $output .= " " . mysql2date($date_format, $time);
        }
    }
}

?>