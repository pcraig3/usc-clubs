<?php

global $wp_query;
$wp_query->set('post_type','usc_clubs');

$current_club_name = esc_html( $current_club['name'] );

add_filter( 'wp_title', function( $title ) use ( $current_club_name ) {

    return $current_club_name . " | Western USC";
});

get_header();

// if we've gotten to this point, we have access to: $current_club
// and most likely to these two as well: $previous_club, $next_club

?>

    <div id="main-content" class="usc_clubs-main-content">

        <div class="et_pb_section et_pb_fullwidth_section et_section_regular">

            <section class="et_pb_fullwidth_header et_pb_bg_layout_dark et_pb_text_align_left">
                <div class="et_pb_row">
                    <h1><?php
                        echo esc_html( $current_club['name'] );
                        if( !empty( $current_club['shortName'] ) )
                            echo ' (' . esc_html( $current_club['shortName'] ) . ')';
                        ?>
                    </h1>
                </div>
            </section>

        </div>

        <div class="et_pb_section usc-breadcrumbs et_section_regular">

            <div class="et_pb_row">
                <div class="et_pb_column et_pb_column_4_4">
                    <div class="et_pb_text et_pb_bg_layout_light et_pb_text_align_left">

                        <div class="breadcrumbs">
                            <!-- Generating breadcrumbs with a bit of manual effort in the template file.-->
                            <?php
                            if(function_exists('bcn_display'))
                            {
                                bcn_display();
                            }
                            //as our page is not a native WordPress one, we're on our own with the breadcrumbs.
                            $manual_breadcrumbs = array(
                                'a'     => array( 'Clubs', 'Clubs List' ),
                                'span'  => array( esc_html( $current_club['name'] ) )
                            );

                            foreach($manual_breadcrumbs as $key => $breadcrumb) {

                                if($key === 'a')
                                    foreach($breadcrumb as $link)
                                        echo ' > <span typeof="v:Breadcrumb"><a rel="v:url" property="v:title" title="Go to '. $link
                                            .'." href="' . trailingslashit(get_bloginfo('wpurl')) . str_replace(' ', '/', strtolower( $link ) ) . '/">' . $link . '</a></span>';

                                else if ($key === 'span')
                                    foreach($breadcrumb as $link)
                                        echo ' > <span typeof="v:Breadcrumb"><span property="v:title">' . $link . '</span></span>';
                            }

                            ?>
                        </div>

                    </div> <!-- .et_pb_text -->
                </div> <!-- .et_pb_column -->
            </div> <!-- .et_pb_row -->

        </div>

        <div id="usc_clubs-post" class="container">
            <div id="content-area" class="clearfix et_pb_row">
                <div class="et_pb_column et_pb_column_2_3">

                    <?php //while ( have_posts() ) : the_post(); ?>

                    <article id="post-<?php echo intval( $current_club['organizationId']); ?>"
                             class="post-<?php echo intval( $current_club['organizationId']); ?> usc_clubs type-usc_clubs status-publish hentry">

                        <div class="usc_clubs-article-info news-article-info ">

                            <?php //et_divi_post_meta();

                            //a href="http://westernusc.org/blog/author/uscadmin/" title="Posts by USCAdmin" rel="author">USCAdmin</a> | Aug 8, 2014 |  | </p>

                            $html_string    = "";

                            $array_of_categories = $current_club['categories'];

                            //if no categories are assigned, get_the_terms() returns 'false'
                            if( false !== $array_of_categories ) {

                                $html_string .= '<p class="post-meta">';
                                $html_string .= '<a href="#" style="cursor:default;">';


                                foreach( $array_of_categories as &$category) {

                                    //var_dump($department);

                                    $html_string .= esc_html($category['categoryName']) . ", ";

                                }
                                unset($category);

                                $html_string = trim($html_string, ", ");
                                $html_string .= '</a>';

                                $social_links = array(
                                    /* 'externalWebsite', */
                                    'facebookUrl',
                                    'twitterUrl',
                                    'flickrFeedUrl',
                                    'youtubeChannelUrl',
                                    'googleCalendarUrl'
                                );

                                foreach($social_links as &$social_link) {
                                    if( !empty( $current_club[$social_link]) )
                                        /* title=" visit us on Facebook! " */
                                        $html_string .= '<a href="' . esc_url( $current_club[$social_link]) . '" target="_blank" class="etmodules ' . $social_link . '"></a>' ;//. date('F j, Y');
                                }
                                unset($social_link);


                                $html_string .= '</p>';
                            }

                            echo $html_string;

                            ?>
                        </div>

                        <div class="usc_clubs-entry-content">
                            <br>
                            <?php

                            $html_string = '';

                            $keys = array(
                                'summary',
                                'description'
                                //'email',
                                //'profileImageUrl',
                            );

                            //some of the summaries are word-for-word what the descriptions are, except shorter
                            //so check the first (I don't know, maybe) 50 characters of each and if they match only print the summary.
                            if (
                                substr_compare(
                                    html_entity_decode( wp_strip_all_tags( $current_club['summary'] ) ),
                                        html_entity_decode( wp_strip_all_tags( $current_club['description'] ) ),
                                    0,  //start index
                                    50 //end index
                                ) === 0) {

                                //ie, if the summary and the description match, then remove the first element (summary) from array
                                array_shift($keys);

                            }

                            foreach($keys as &$key) {

                                if( !empty( $current_club[$key] ))
                                    $html_string .= '<p><span class="subheading">' . ucfirst($key) .'</span></p>'
                                        . '<p>' .  wp_strip_all_tags( $current_club[$key] ) . "</p><br>";
                            }
                            unset($key);


                            echo $html_string;


                            //start the bottom

                            $html_string = '<div class="usc_clubs__contact clearfix"><div class="usc_clubs__contact__child usc_clubs__contact__emails">';


                            $contact_string = '';

                            //echo '<a href="mailto:'.antispambot($email,1).'" title="Click to e-mail me" >'.antispambot($email).' </a>';
                            if( !empty($current_club['email']) )
                                $contact_string .= '<p style="color: #555555;"><strong>General Club Contact</strong><br>'
                                                . 'Email: <a href="mailto:' . antispambot( $current_club['email'] ,1 ) . '">' . antispambot( $current_club['email']) . '</a></p>';

                            if( !empty($current_club['primaryContactName']) && !empty($current_club['primaryContactCampusEmail']) )
                                $contact_string .= '<p style="color: #555555;"><strong>Primary Club Contact</strong><br>' .  esc_html($current_club['primaryContactName']) . '<br>'
                                    . 'Email: <a href="mailto:' . antispambot( $current_club['primaryContactCampusEmail'] ,1 ) . '">' . antispambot( $current_club['primaryContactCampusEmail']) . '</a></p>';



                            $html_string .= do_shortcode('[person title="Contact Information"]' . $contact_string . '[/person]');

                            $html_string .= '</div><div class="usc_clubs__contact__child usc_clubs__contact__buttons"><div class="button_area_at_the_bottom_of_a_single_usc_club btn-menu">';

                            $html_string .=     '<ul>';

                            if( !empty( $current_club['externalWebsite'] ) )
                                $html_string .=     '<li><a class="externalWebsite height_of_person_header" target="_blank" href="' . $this->wp_ajax->add_http_if_not_exists($current_club['externalWebsite']) . '">' . __( 'Visit Website', 'usc-clubs' ) .'</a></li>';

                            if( !empty( $current_club['profileUrl'] ) )
                                $html_string .=     '<li><a class="profileUrl height_of_person_header" target="_blank" href="' . $this->wp_ajax->add_http_if_not_exists($current_club['profileUrl']) . '">' . __( 'WesternLink Profile', 'usc-clubs' ) .'</a></li>';

                            //if( !empty( $current_club['facebookUrl'] ) )
                            //    $html_string .=     '<li><a class="facebookUrl height_of_person_header" target="_blank" href="' . $current_club['facebookUrl'] . '">' . __( 'Facebook Profile', 'usc-clubs' ) .'</a></li>';


                            $html_string .=     '</ul>';
                            $html_string .= '</div><!--end of .btn-menu --></div><!--end of .usc_clubs__contact__buttons --> ';

                            $html_string .= '</div><!--end of .usc_clubs__contact -->';

                            echo $html_string;



                            wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'Divi' ), 'after' => '</div>' ) ); ?>
                        </div> <!-- .entry-content -->

                    </article> <!-- .et_pb_post -->

                    <?php //endwhile; ?>

                </div> <!-- #left-area -->

                <?php if ( is_active_sidebar( 'usc_clubs_single_sidebar' ) ) : ?>
                    <div class="et_pb_column et_pb_column_1_3">
                        <div class="et_pb_widget_area et_pb_widget_arzea_right clearfix et_pb_bg_layout_light btn-menu">
                            <?php dynamic_sidebar( 'usc_clubs_single_sidebar' ); ?>
                        </div><!-- .et_pb_widget_area .btn-menu -->
                    </div><!-- .et_pb_column -->

                <?php endif; ?>

            </div> <!-- #content-area -->

        </div> <!-- .container -->
        <!--Comments section-->
        <div id="usc_clubs-comments" class="et_pb_section">
            <div class="et_pb_row">
            </div>
        </div>
        <!--comments section ends--->


    </div> <!-- #main-content -->

<?php get_footer(); ?>