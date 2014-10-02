<?php

//we are assuming at this point that we have the clubs array. if not, die oh so tragically.
if( !isset( $clubs_array ) )
    die(0);

ob_start();

?>

    <div class="filterjs hidden">
        <div class="filterjs__filter">
            <aside  id="nav_menu-search-1000" class="filterjs__filter__search__wrapper et_pb_widget widget_nav_menu">
                <h4 class="widgettitle">Search Clubs</h4>
                <input type="text" id="search_box" class="searchbox" placeholder="´◡`"/>
            </aside>
            <aside id="nav_menu-categoryNames-1000" class="filterjs__filter__checkbox__wrapper et_pb_widget widget_nav_menu" >
                <h4 class="widgettitle">Categories</h4>
                <ul id="categoryNames">
                    <?php
                    /*

                    Our JS can just build this, right?

                    */
                    ?>
                </ul>
            </aside>
        </div>
        <br>
        <div class="filterjs__list__wrapper">
            <div class="filterjs__loading filterjs__loading--ajax">
                <img class="filterjs__loading__img" title="go mustangs!"
                     src="<?php echo plugins_url( '/' . $this->plugin_slug . '/assets/horse.gif') ?>" alt="Loading" height="91" width="160">
            </div>

            <!--div class="filterjs__list__crop"-->
            <div class="filterjs__list" id="usc_clubs_list" data-nonce="<?php echo wp_create_nonce("usc_clubs_list_nonce"); ?>"></div>
            <!--/div-->
        </div>
        <div class="clearfix cf"></div>
        <?php /* we're putting this in to ensure that the collapseomatic js file is included on the page */
        /* the collapseomatic rules are set on the filter fields using JS in public-filer.js */
        /* It's just two lines of JS. */
        do_shortcode('[expand title="placeholder"][\expand]'); ?>
    </div>

<?php

$html_string = ob_get_clean();

$total = USC_Clubs::get_instance()->clubs_count( $clubs_array );

$clubs_string = ( $total === 0 )
    ? 'No Clubs' :
    ( $total === 1 )
        ? '1 Club':
        $total . ' Clubs';
?>

    <h4 class="usc_clubs--count"><span id="counter"><?php echo $clubs_string; ?></span> Found</h4>

<?php

for($i = 0; $i < $total; $i++) {

    $current_club = $clubs_array[$i];

    $img_url = "";

    if( isset( $current_club['profileImageUrl'] ) )
        $img_url = esc_url( $this->wp_ajax->add_http_if_not_exists($current_club['profileImageUrl']) );

    /*
            html_string += '</article><!-- end of usc_club -->';
            return html_string;
    */

    $categories = $current_club['categories'];
    $total_categories = count( $categories );

    $html_string .= '<article class="usc_clubs type-usc_clubs et_pb_post media ';

    if( $total_categories < 1 )
        $html_string .= 'hidden';

    $html_string .= '">';

    if( !empty( $img_url )) {
        $html_string .= '<a href="' . esc_url( $current_club['url'] ) . '" class="img">';

        $html_string .=     '<img src="' . $img_url . '" alt="Logo for '
                                . esc_attr( $current_club['name'] ) . '" />';

        $html_string .= '</a>';
    }


    $html_string .=     '<div class="bd"><a href="' . esc_url( $current_club['url'] ) . '" title="' . esc_attr( $current_club['name'] ) . '"><h2>'
        . esc_html( $current_club['name'] );

    $html_string .= ( !empty($current_club['shortName']) ) ? ' (' . esc_html( $current_club['shortName'] ) . ')</h2></a>' : '</h2></a>';

    $html_string .=     '<p class="post-meta">';

    $categories = $current_club['categories'];
    $total_categories = count( $categories );

    for ($j = 0; $j < $total_categories; $j++) {
        $html_string .=     '<a>' . esc_html( $categories[$j]['categoryName'] ) . '</a>, ';
    }

    $html_string = trim($html_string, ", ");

    $html_string .=     '</p><!-- end of .post-meta -->';

    $html_string .= '</div><!-- .end of .bd --></article><!-- end of .usc_club -->';

}

return $html_string;