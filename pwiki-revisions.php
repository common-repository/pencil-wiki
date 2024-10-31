<?php

function pwiki_revisions_metabox_block(){

    if (get_post_status()!='auto-draft'){

        ?>

        <p>
                <strong><?php _e( 'Revision', 'pwiki' ); ?><?php if ( !current_user_can( 'lock_wiki_pages' ) ) echo '*';?></strong><br/>
                <input type="text" value="<?php bbp_form_reply_edit_reason(); ?>" class="widefat" name="pwiki_edit_reason" id="pwiki_edit_reason" placeholder="<?php _e( 'Reason for editing', 'pwiki' );?>" />

        </p>
        <?php
    }
}

function pwiki_revisions_save( $post_id, $post ) {

    // Revision Reason
    if ( !empty( $_POST['pwiki_edit_reason'] ) )
            $post_edit_reason = esc_attr( strip_tags( $_POST['pwiki_edit_reason'] ) );

    // Update revision log

    if ( empty( $post_edit_reason ) ){

        if( !current_user_can( 'lock_wiki_pages' ) ){
        //TO FIX ABORD SAVING
        }

    }else{



        $revision_id = wp_is_post_revision( $post_id );



        if ( !empty( $revision_id ) ) {
                pwiki_update_post_revision_log( array(
                        'post_id'    => $revision_id,//!! SWITCHED but that's how it works !
                        'revision_id' => $post_id,//!! SWITCHED but that's how it works !
                        'author_id'   => get_current_user_id(),
                        'reason'      => $post_edit_reason
                ) );
        }

    }
}

/**
* Update the revision log of the post
*
* Inspired by bbp_update_reply_revision_log() [bbPress]
*
* @param mixed $args Supports these args:
*  - post_id: post id
*  - author_id: Author id
*  - reason: Reason for editing
*  - revision_id: Revision id
* @uses format_revision_reason() To format the reason
* @uses get_post_raw_revision_log() To get the raw reply revision log
* @uses update_post_meta() To update the reply revision log meta
* @return mixed False on failure, true on success
*/
function pwiki_update_post_revision_log( $args = '' ) {
        $defaults = array (
                'reason'      => '',
                'post_id'    => 0,
                'author_id'   => 0,
                'revision_id' => 0
        );

        $r = wp_parse_args( $args, $defaults );
        extract( $r );

        // Populate the variables
        $reason      = pwiki_format_revision_reason( $reason );



        // Get the logs and append the new one to those
        $revision_log               = pwiki_get_post_raw_revision_log( $post_id );
        $revision_log[$revision_id] = array( 'author' => $author_id, 'reason' => $reason );

        // Finally, update
        update_post_meta( $post_id, 'pwiki_revision_log', $revision_log );

        return apply_filters( 'pwiki_update_post_revision_log', $revision_log, $post_id );
}

/**
* Formats the reason for editing the post.
*
* Inspired by bbp_format_revision_reason() [bbPress]
*  
* Does these things:
*  - Trimming
*  - Removing periods from the end of the string
*  - Trimming again
*
* @since bbPress (r2782)
*
* @param int $topic_id Optional. Topic id
* @return string Status of topic
*/
function pwiki_format_revision_reason( $reason = '' ) {
        $reason = (string) $reason;

        // Format reason for proper display
        if ( empty( $reason ) )
                return $reason;

        // Trimming
        $reason = trim( $reason );

        // We add our own full stop.
        while ( substr( $reason, -1 ) == '.' )
                $reason = substr( $reason, 0, -1 );

        // Trim again
        $reason = trim( $reason );

        return $reason;
}

/**
* Return the raw revision log of the post
*
* Inspired by bbp_get_reply_raw_revision_log() [bbPress]
*
* @param int $reply_id Optional. Reply id
* @uses bbp_get_reply_id() To get the reply id
* @uses get_post_meta() To get the revision log meta
* @uses apply_filters() Calls 'bbp_get_reply_raw_revision_log'
*                        with the log and reply id
* @return string Raw revision log of the reply
*/
function pwiki_get_post_raw_revision_log( $post_id = 0 ) {
        $revision_log = get_post_meta( $post_id, 'pwiki_revision_log', true );
        $revision_log = empty( $revision_log ) ? array() : $revision_log;

        return apply_filters( 'pwiki_get_post_raw_revision_log', $revision_log, $post_id );
}


////////////////////////
////////////////////////
//Weird hacks to add the reason of the revision; waiting for WP team to add some better hooks !
////////////////////////
////////////////////////

add_action( 'admin_init', 'pwiki_revisions_hack_admin_init');


function pwiki_revisions_hack_admin_init(){

    ////POST PAGE (metabox)////
    remove_meta_box( 'revisionsdiv' , 'wiki_page' , 'normal' );//remove CORE meta box
    add_meta_box('revisionsdiv', __('Revisions'), 'pwiki_list_post_revisions_metabox', 'wiki_page', 'normal');//add CUSTOM meta box
    ////REVISIONS PAGE////
    add_action( 'admin_notices', 'pwiki_revisions_admin_menu');//that hook because we need to detect the page revision.php and the current post ID.

}

/**
 * dummy function used to populate the revision logs & to add/remove the 'author display name' filter; for the METABOX
 * @param type $post_id
 * @param type $args 
 */
function pwiki_list_post_revisions_metabox( $post_id = 0, $args = null ) {
    
    //populate the revision IDs and the log
    pwiki_populate_revision_logs();

    //add the filter
    add_filter( 'get_the_author_display_name','pwiki_revisions_display_name', 10,2 );
    
    //use WP core function
    wp_list_post_revisions();
    
    //remove the filter
    remove_filter( 'get_the_author_display_name','pwiki_revisions_display_name', 10,2 );
    
    //unset the vars
    unset($pwiki_revisions_ids,$pwiki_current_revision_key);
    
}

/**
 * dummy function used to populate the revision logs & to add/remove the 'author display name' filter; for the REVISION.PHP page
 * @global type $pagenow
 * @return type 
 */

function pwiki_revisions_admin_menu(){
    global $pagenow;
    if($pagenow!='revision.php') return;
    if(get_post_type()!='wiki_page') return;
    
    //populate the revision IDs and the log
    pwiki_populate_revision_logs();
    
    //add the filter
    add_filter( 'get_the_author_display_name','pwiki_revisions_display_name', 10,2 );
}

/**
 * Populate the revisions IDs for a post and his revisions log
 * @global type $pwiki_revisions_ids
 * @global int $pwiki_current_revision_key 
 */

function pwiki_populate_revision_logs(){
    global $pwiki_revisions;
    

    
    $revisions = wp_get_post_revisions( get_the_ID() );

    foreach ( $revisions as $revision ) {
        $ids[]=$revision->ID;
    }

    $pwiki_revisions['revisions_ids']=$ids;
    $pwiki_revisions['current_key']=0;
    $pwiki_revisions['log']=pwiki_get_post_raw_revision_log(get_the_ID());


}



/**
 * Displays the reason of the edition instead of the post author
 * @global type $pwiki_revisions
 * @param type $display_name
 * @param type $user_id
 * @return string 
 */

function pwiki_revisions_display_name($display_name,$user_id){
    global $pwiki_revisions;
    
    //get current revision id
    $current_revision_id = $pwiki_revisions['revisions_ids'][$pwiki_revisions['current_key']];
    $pwiki_revisions['current_key']++; //let this here  - increment revision ID key
    
    //get revision log
    $revision_log = $pwiki_revisions['log'][$current_revision_id];
    
    
    //reason
    $revision_reason = $revision_log['reason'];
    if(!$revision_reason) return $display_name;
    
    //author
    //do not use get_the_author_display_name or the filter will make an infinite loop !
    $revision_author_id = $revision_log['author'];
    $authordata = get_userdata( $revision_author_id );
    $revision_author = $authordata->display_name;
    
    
    $text = sprintf(__('edited by %1s because : "%s2"','pencil-wiki'),'<strong>'.$revision_author.'</strong>','<em>'.$revision_reason.'</em>');

    return $display_name.' — <span class="pwiki-revision-details">'.$text.'</span>';
}







	
?>
