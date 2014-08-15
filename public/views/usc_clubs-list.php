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
            <input type="text" id="search_box" class="searchbox" placeholder="^.^"/>
        </aside>
        <aside id="nav_menu-categoryNames-1000" class="filterjs__filter__checkbox__wrapper et_pb_widget widget_nav_menu" >
            <h4 class="widgettitle">Remuneration</h4>
            <ul id="categoryNames">
                <?php
                /*

                Our JS can just build this, right?

                $remuneration_values = array(
                    'paid',
                    'volunteer'
                );

                foreach( $remuneration_values as &$remuneration_value ) {

                    $checked_by_default = ( ! $is_remuneration ) ? "checked" : ( $remuneration === $remuneration_value ) ? "checked" : "" ;

                    echo '<li><label class="' . $checked_by_default . '">'
                        .   '<input id="' . $remuneration_value . '" value="' . $remuneration_value . '" type="checkbox">';
                    echo ucfirst($remuneration_value) . '</label>';
                    echo '</li>';

                }
                unset( $remuneration_value );
                */

                ?>
            </ul>
        </aside>
    </div>
    <br>
    <div class="filterjs__list__wrapper">
        <div class="filterjs__loading filterjs__loading--ajax">
            <img class="filterjs__loading__img" title="go mustangs!"
                 src="<?php echo plugins_url( 'assets/horse.gif', __DIR__ ); ?>" alt="Loading" height="91" width="160">
            <p class="filterjs__loading__status">
                * Loading *
            </p>
        </div>

        <!--div class="filterjs__list__crop"-->
        <div class="filterjs__list" id="usc_clubs_list"></div>
        <!--/div-->
    </div>
    <div class="clearfix cf"></div>
</div>

<?php

$html_string = ob_get_clean();


$html_string .= '<blockquote id="clubs_list__wrapper">';

$total = intval( count($clubs_array) );

for($i = 0; $i < $total; $i++) {

    $current_club = $clubs_array[$i];

    $img_url = "";

    if( isset( $current_club['profileImageUrl'] ) )
        $img_url = esc_url( "http://" . $current_club['profileImageUrl'] );


    $html_string .= '<div class="clubs__box flag clearfix"><a href="http://testwestern.com/clubs/' . intval( $current_club['organizationId'] ) . '/" target="_self">';
    //$html_string .= '<div class="clubs__box flag clearfix">';

    $html_string.= '<div class="flag__image">';

    if($img_url)
        $html_string .= '<img src="' . $img_url . '">';

    $html_string .= '</div>';

    $html_string .= '<div class="flag__body">';
    $html_string .= '<h3 class="alpha" title="' . esc_attr( $current_club['organizationId'] ) .
        '">' . esc_html( $current_club['name'] );

    $html_string .= '</h3></div>';
    $html_string .= '<span class="clubs__box__count">' . (intval( $current_club['id'] ) + 1) . '</span>';
    $html_string .= '</a></div><!--end of clubs__box-->';
}

$html_string .= "</blockquote><!--end of #clubs-->";

return $html_string;