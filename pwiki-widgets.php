<?php
////////WIDGET: SEARCH//////

function pwiki_register_widget_search() {
    register_widget( 'Pencil_Wiki_Widget_Search' ); 
}
  
class Pencil_Wiki_Widget_Search extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'pwiki_widget_search', 'description' => __("A search form for wiki pages",'pencil-wiki') );
		parent::__construct('pwiki_search', __('(Pencil Wiki) Search','pencil-wiki'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;

		// Use current theme search form if it exists
                
                $parent_id = $instance['pwiki_parent_id'];
                if(!$parent_id) $parent_id=0; // force search through all wikis
                
		pwiki_get_search_form(true,$parent_id);

		echo $after_widget;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
?>
		<p>
                    <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
                        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
                
                    
                        <?php 
                        
                            $dropdown_args = array(
                                                    'post_type'        => 'wiki_page',
                                                    'selected'         => $instance['pwiki_parent_id'],
                                                    'name'             => $this->get_field_name('pwiki_parent_id'),
                                                    'show_option_none' => __('(All Wikis)','pencil-wiki'),
                                                    'sort_column'      => 'menu_order, post_title',
                                                    'echo'             => 0,
                                                    'depth'             =>1
                            );

                            $dropdown_args = apply_filters( 'wiki_page_attributes_dropdown_pages_args', $dropdown_args);
                            $pages = wp_dropdown_pages( $dropdown_args );
                            if ( ! empty($pages) ) {
                                ?>
                                <p>
                                    <label for="<?php echo $this->get_field_id('pwiki_parent_id'); ?>"><?php _e('Limit to:','pencil-wiki'); ?>
                                    <?php echo $pages; ?>
                                </p>
                                <?php
                            }
                        
                        
                        ?>
<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags($new_instance['title']);

                $parent_page_obj = get_post($new_instance['pwiki_parent_id']);
                if ($parent_page_obj){
                    $instance['pwiki_parent_id'] = $parent_page_obj->ID;
                }
                

                
		return $instance;
	}

}



////////WIDGET: ADD LINK//////

function pwiki_register_widget_add_page_link() {
    register_widget( 'Pencil_Wiki_Widget_Add_Page_Link' ); 
}
  
class Pencil_Wiki_Widget_Add_Page_Link extends WP_Widget {
    
        var $default_options = array();

	function __construct() {
            
            $this->default_options = array(
                'title'             => '',
                'cap_check'     => false,
                'link_text'     => __('Add Wiki Page','pencil-wiki')
            );

            
            $widget_ops = array(
                'classname' => 'pwiki_widget_add_page_link', 'description' => __("Add page link for the wiki",'pencil-wiki') ); 
            parent::__construct('pwiki_widget_add_page_link', __('(Pencil Wiki) Add Page Link','pencil-wiki'), $widget_ops, $control_ops);
	}
        
        function widget( $args, $instance ) {
            extract($args);
            
            $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

            echo $before_widget;
            if ( $title )
                    echo $before_title . $title . $after_title;
            
            if(empty( $instance['link_text'] ))
                $instance['link_text'] = $this->default_options['link_text'];
            
            echo "<ul>";

            echo "<li>";
            pwiki_add_page_link($instance['link_text'],$instance['cap_check']);
            echo "</li>";

            echo "</ul>";
            
            echo $after_widget;
        }

	function form( $instance ) {
            
            

            $instance = wp_parse_args( (array) $instance, (array) $this->default_options );

            
?>
                
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
                
            <p>
                <label for="<?php echo $this->get_field_id('link_text'); ?>"><?php _e('Link Text:','pencil-wiki'); ?></label>
                <input id="<?php echo $this->get_field_id('link_text'); ?>" name="<?php echo $this->get_field_name('link_text'); ?>" type="text" value="<?php echo esc_attr($instance['link_text']); ?>" />
                <br/>
                <label for="<?php echo $this->get_field_id('cap_check'); ?>"><?php _e('Capability check ?','pencil-wiki'); ?> </label>
                <input class="checkbox" id="<?php echo $this->get_field_id('cap_check'); ?>" name="<?php echo $this->get_field_name('cap_check'); ?>" type="checkbox" <?php checked( $instance['cap_check'], true ); ?> />
            </p>
<?php
	}

	function update( $new_instance, $old_instance ) {

            $instance = $old_instance;

            $instance['title'] = strip_tags($new_instance['title']);

            $instance['link_text'] = strip_tags($new_instance['link_text']);
            
            $instance['cap_check'] = (bool)$new_instance['cap_check_add'];


            return $instance;
	}

}

////////WIDGET: EDIT LINK//////

function pwiki_register_widget_edit_page_link() {
    register_widget( 'Pencil_Wiki_Widget_Edit_Page_Link' ); 
}

class Pencil_Wiki_Widget_Edit_Page_Link extends WP_Widget {
    
        var $default_options = array();

	function __construct() {
            
            $this->default_options = array(
                'title'             => '',
                'cap_check'     => false,
                'link_text'     => __('Edit New Wiki Page','pencil-wiki')
            );

            
            $widget_ops = array(
                'classname' => 'pwiki_widget_edit_page_link', 'description' => __("Edit page link for the wiki",'pencil-wiki') ); 
            parent::__construct('pwiki_widget_edit_page_link', __('(Pencil Wiki) Edit Page Link','pencil-wiki'), $widget_ops, $control_ops);
	}
        
        function widget( $args, $instance ) {
            extract($args);
            
            $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

            echo $before_widget;
            if ( $title )
                    echo $before_title . $title . $after_title;
            
            if(empty( $instance['link_text'] ))
                $instance['link_text'] = $this->default_options['link_text'];
            

            echo "<ul>";

            echo "<li>";
            pwiki_edit_page_link($instance['link_text'],$instance['cap_check']);
            echo "</li>";

            echo "</ul>";
            
            echo $after_widget;
        }

	function form( $instance ) {
            
            

            $instance = wp_parse_args( (array) $instance, (array) $this->default_options );

            
?>
                
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
                
            <p>
                <label for="<?php echo $this->get_field_id('link_text'); ?>"><?php _e('Link Text:','pencil-wiki'); ?></label>
                <input id="<?php echo $this->get_field_id('link_text'); ?>" name="<?php echo $this->get_field_name('link_text'); ?>" type="text" value="<?php echo esc_attr($instance['link_text']); ?>" />
                <br/>
                <label for="<?php echo $this->get_field_id('cap_check'); ?>"><?php _e('Capability check ?','pencil-wiki'); ?> </label>
                <input class="checkbox" id="<?php echo $this->get_field_id('cap_check'); ?>" name="<?php echo $this->get_field_name('cap_check'); ?>" type="checkbox" <?php checked( $instance['cap_check'], true ); ?> />
            </p>
<?php
	}

	function update( $new_instance, $old_instance ) {

            $instance = $old_instance;

            $instance['title'] = strip_tags($new_instance['title']);

            $instance['link_text'] = strip_tags($new_instance['link_text']);
            
            $instance['cap_check'] = (bool)$new_instance['cap_check_add'];


            return $instance;
	}
}

////////WIDGET: TREE//////

function pwiki_register_widget_tree() {
    register_widget( 'Pencil_Wiki_Widget_Tree' ); 
}

class Pencil_Wiki_Widget_Tree extends WP_Widget {
    
        var $default_options = array();
		var $tree_walker;

	function __construct() {
	
			$this->tree_walker = new Pencil_Wiki_Walker_Page();
            
            $this->default_options = array(
                'title'             => '',
                'tree_args'         => array(
                    'title_li'      => ''
                ),
                'tree_args_hidden'         => array(
                    'post_type'     => pencil_wiki()->post_type_slug,
					'walker'		=> $this->tree_walker
                )
            );

            
            $widget_ops = array(
                'classname' => 'pwiki_widget_tree', 'description' => __("List the pages of your wiki",'pencil-wiki') ); 
            parent::__construct('pwiki_widget_tree', __('(Pencil Wiki) List pages','pencil-wiki'), $widget_ops, $control_ops);
	}
        
        function widget( $args, $instance ) {
            extract($args);
            
            $title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

            echo $before_widget;
            if ( $title )
                    echo $before_title . $title . $after_title;
            
            if(empty( $instance['link_text'] ))
                $instance['link_text'] = $this->default_options['link_text'];
            

            echo "<ul>";
            $children_args = wp_parse_args( $instance['tree_args'], $this->default_options['tree_args'] );
			$children_args = wp_parse_args( $this->default_options['tree_args_hidden'], $children_args );

            $children_args = apply_filters('pwiki_widget_tree_args', $children_args, $instance);
            $wiki_children = wp_list_pages( $children_args );

            echo "</ul>";
            
            echo $after_widget;
        }

	function form( $instance ) {
            
            

            $instance = wp_parse_args( (array) $instance, (array) $this->default_options );
            
            $string_args = http_build_query($instance['tree_args']);
			$string_args_hidden_arr = $this->default_options['tree_args_hidden'];
			unset($string_args_hidden_arr['walker']);
			$string_args_hidden = http_build_query($string_args_hidden_arr);
            
?>
                
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
                
            <p>
                <label for="<?php echo $this->get_field_id('tree_args'); ?>">
					<?php _e('Arguments:','pencil-wiki'); ?> <small><a href="http://codex.wordpress.org/Function_Reference/wp_list_pages" target="_blank">?</a></small>
				</label><br/>
				<span class="pwiki_tree_hidden_args" style="color:#606060;font-style:italic"><?php echo $string_args_hidden.'&';?></span>
                <input id="<?php echo $this->get_field_id('tree_args'); ?>" name="<?php echo $this->get_field_name('tree_args'); ?>" type="text" value="<?php echo $string_args; ?>" />

            </p>
<?php
	}

	function update( $new_instance, $old_instance ) {


            $instance = $old_instance;

            $instance['title'] = strip_tags($new_instance['title']);
            
            $tree_args = strip_tags($new_instance['tree_args']);
            parse_str($tree_args, $instance['tree_args']);
			$instance['tree_args'] = array_diff_key($instance['tree_args'],$this->default_options['tree_args_hidden']);

            return $instance;
	}
}

?>
