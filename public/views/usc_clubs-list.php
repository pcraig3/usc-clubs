<?php

//we are assuming at this point that we have the clubs array. if not, die oh so tragically.
if( !isset( $clubs_array ) )
    die(0);


$html_string = '<blockquote id="clubs">';

$total = intval( count($clubs_array) );

for($i = 0; $i < $total; $i++) {

    $current_club = $clubs_array[$i];

    $img_url = "";

    if( isset( $current_club['profileImageUrl'] ) )
        $img_url = esc_url( "http://" . $current_club['profileImageUrl'] );


    $html_string .= '<a href="http://testwestern.com/clubs/' . intval( $current_club['organizationId'] ) . '/" target="_self">';
    $html_string .= '<div class="clubs__box flag clearfix">';

    $html_string.= '<div class="flag__image">';

    if($img_url)
        $html_string .= '<img src="' . $img_url . '">';

    $html_string .= '</div>';

    $html_string .= '<div class="flag__body">';
    $html_string .= '<h3 class="alpha" title="' . esc_attr( $current_club['organizationId'] ) .
        '">' . esc_html( $current_club['name'] );

    $html_string .= '</h3></div>';
    $html_string .= '<span class="clubs__box__count">' . (intval( $current_club['id'] ) + 1) . '</span>';
    $html_string .= '</div><!--end of clubs__box--></a>';
}

$html_string .= "</blockquote><!--end of #clubs-->";

return $html_string;