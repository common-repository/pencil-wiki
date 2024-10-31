<div id="pwiki-navigation" class="main-navigation" role="navigation">
        <h3 class="menu-toggle"><?php _e( 'Wiki Menu', 'pencil-index' ); ?></h3>

            <ul class="pwiki-menu">
                <li>
                    <a id="pwiki-index-label" class="pwiki-menu-label" href=""><?php
                    
                        if (pwiki_single_wiki_search_form_post_id()){//single wiki search
                            $rootpage = pwiki_get_root_page();

                            $index_link_txt = sprintf(__('%s Index','pencil-wiki'),$rootpage->post_title);
                        }else{//search trough all wikis.
                            $index_link_txt = __('Wikis Index','pencil-wiki');
                        }
                    
                    echo $index_link_txt;?></a>
                    
                    <ul class="sub-menu">

                        <?php
                            $root_page = pwiki_get_root_page();
                            $pwiki_menu_walker = new Pencil_Wiki_Walker_Page();
                            $children_args = array(
                                'child_of'     => $root_page->ID,
                                'post_type'    => pencil_wiki()->post_type_slug,
                                'title_li'     => '',
                                'echo'          => 0,
                                'walker'        => $pwiki_menu_walker
                            );
                            $children_args = apply_filters('pwiki_single_post_menu_args',$children_args,get_the_ID());
                            $wiki_children = wp_list_pages( $children_args );
                            echo $wiki_children;
                        ?>
                    </ul>
                </li>
                <li class="pwiki-menu-adminlink alignright">
                    <?php pwiki_edit_page_link(__('Edit This Page','pencil-wiki'));?>
                </li>
                <li class="pwiki-menu-adminlink alignright">
                    <?php pwiki_add_page_link(__('Add Page','pencil-wiki'));?>
                </li>
                <li class="pwiki-menu-search">
                    <a class="pwiki-menu-search-label" href=""><?php
                    
                    if (pwiki_single_wiki_search_form_post_id()){//single wiki search
                        $slink_txt = __('Search Wiki','pencil-wiki');
                    }else{//search trough all wikis.
                        $slink_txt = __('Search Wikis','pencil-wiki');
                    }
                    
                    echo $slink_txt;?></a>
                    <?php pwiki_get_search_form();?>
                </li>
            </ul>
</div>