<?php

function pwiki_is_root_page($post=false){
    if(!$post) global $post;
    if (!$post->post_parent) return true;
}
function pwiki_get_root_page_id($post=false){
    if(!$post) global $post;
    if ($post->post_type!=  pencil_wiki()->post_type_slug) return false;
    $root_page_id = ( empty( $post->ancestors ) ) ? $post->ID : end( $post->ancestors );
    return $root_page_id;
}
function pwiki_get_root_page($post=false){
    if(!$post) global $post;
    $page_id = pwiki_get_root_page_id($post);
    if(!$page_id) return false;
    return get_page($page_id);
}
function pwiki_parent_is_root($post=false){
    if(!$post) global $post;
    $root_id = pwiki_get_root_page_id($post);
    if($root_id==$post->post_parent) return true;
    return false;
}
function pwiki_has_children($post=false){
    if(!$post) global $post;
    return pencil_wiki()->has_children( $post );
}

/**
 * Function used to check if the search form will be made through a wiki or through all wikis.
 * Returning false means we will search inside all wiki pages
 * Returning ID (true) means we will search inside this wiki and its subpages.
 *
 * @global type $post
 * @param type $parent_id - Value can be
 * FALSE : detects search type depending on the page we are browsing
 * 0 : search all wikis
 * (id) : search under this parent.
 * @param type $get_root - retrieve root page.
 * @return boolean 
 */

function pwiki_single_wiki_search_form_post_id($parent_id=false, $get_root=true) {
    global $post;
    
    //if "0", force search through all wikis
    if ($parent_id===0) return false;

    if(!$parent_id) $parent_id=$post->ID;

    if($get_root){
        $parent_post = get_post($parent_id);
        $parent_id=pwiki_get_root_page_id($parent_post);
    }

    if(get_post_type($parent_id)!=pencil_wiki()->post_type_slug) return false;
    return $parent_id;
    
}

function pwiki_get_search_form($echo = true, $parent_id=false, $get_root=true) {
        global $post;
	do_action( 'pwiki_get_search_form' );
        /*
	$search_form_template = locate_template('searchform-wiki.php');
	if ( '' != $search_form_template ) {
		require($search_form_template);
		return;
	}
         */
        
        $inside_wiki_id = pwiki_single_wiki_search_form_post_id($parent_id,$get_root);
        
        if(!$inside_wiki_id){//we are not inside a wiki
            $inside = __('All Wikis','pencil-wiki');
        }else{//we are inside a wiki
            $parent_post = get_post($inside_wiki_id);
            $inside = $parent_post->post_title;
            
        }
        $placeholder = sprintf(__('in %s','pencil-wiki'),$inside);


	$form = '<form role="search" method="get" id="searchform" action="' . esc_url( home_url( '/' ) ) . '" >
	<div><label class="screen-reader-text" for="s">' . __('Search for:') . '</label>
	<input type="text" value="' . get_search_query() . '" name="s" id="s" placeholder="'.$placeholder.'"/>
        <input type="hidden" name="post_type" value="'.pencil_wiki()->post_type_slug.'" />';
        
        if ($inside_wiki_id){
            $form .= '<input type="hidden" name="post_parent" value="'.$inside_wiki_id.'" />';
        }
        
	$form.= '<input type="submit" id="searchsubmit" value="'. esc_attr__('Search') .'" />
	</div>
	</form>';

	if ( $echo )
		echo apply_filters('pwiki_get_search_form', $form);
	else
		return apply_filters('pwiki_get_search_form', $form);
}

function pwiki_get_template_part( $slug, $name = null ) {
    do_action( "pwiki_get_template_part_{$slug}", $slug, $name );

    $templates = array();
    if ( isset($name) )
    $templates[] = "{$slug}-{$name}.php";
    $templates[] = "{$slug}.php";
    pwiki_locate_template($templates, true, false);
}

function pwiki_locate_template( $template_names, $load = false, $require_once = false ) {
	$located = '';
	foreach ( (array) $template_names as $template_name ) {
		if ( ! $template_name )
			continue;

		if ( file_exists( pencil_wiki()->templates_dir . $template_name ) ) {
			$located = pencil_wiki()->templates_dir . $template_name;
			break;
		}
	}

	if ( $load && '' != $located )
		load_template( $located, $require_once );

	return $located;
}

function pwiki_add_page_link($text=false,$check_cap=false){
    $link = pwiki_get_add_page_link($check_cap);
    if(!$link) return false;

    $link_text = empty( $text ) ? pencil_wiki()->post_type_labels['add_new'] : $text;

    ?>
        <a title="<?php echo $link_text;?>" href="<?php echo $link;?>"><?php echo $link_text;?></a>
    <?php
}
    function pwiki_get_add_page_link($check_cap=false){
        if (($check_cap) && (!current_user_can('edit_wiki_pages') )) return;
        return admin_url( 'post-new.php?post_type='.pencil_wiki()->post_type_slug);
    }

function pwiki_edit_page_link($text=false,$check_cap=false){
    $link = pwiki_get_edit_page_link($check_cap);
    if(!$link) return false;

    $link_text = empty( $text ) ? pencil_wiki()->post_type_labels['edit_item'] : $text;
    
    $classes=array();
    if (pwiki_is_post_locked()){
        $classes[]='pwiki-locked';
        if (pwiki_is_post_parent_branchlocked($post_id)){
            $classes[]='pwiki-branchlocked';
        }
        if ( !current_user_can( 'lock_wiki_pages' ) ){
            $link='#';
        }
    }
    if($classes)$classes_str=' class="'.implode(' ',$classes).'"';

    ?>
        <a<?php echo $classes_str;?> title="<?php echo $link_text;?>" href="<?php echo $link;?>"><?php echo $link_text;?></a>
    <?php
}
    function pwiki_get_edit_page_link($check_cap=false){
        if(get_post_type($post)!=pencil_wiki()->post_type_slug) return;
        if (($check_cap) && (!current_user_can('edit_wiki_pages') )) return;

        return get_edit_post_link();
    }

function pwiki_is_post_locked( $post_id=false ){
    return pencil_wiki()->is_post_locked( $post_id );
}
function pwiki_is_post_parent_branchlocked( $post_id=false ){
    return pencil_wiki()->is_post_parent_branchlocked( $post_id );
}
    
?>
