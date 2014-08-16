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
                    <h1><?php echo esc_html( $current_club['name'] ); ?></h1>
                </div>
            </section>

        </div>

        <div class="et_pb_section usc-breadcrumbs et_section_regular">

            <div class="et_pb_row">
                <div class="et_pb_column et_pb_column_4_4">
                    <div class="et_pb_text et_pb_bg_layout_light et_pb_text_align_left">

                        <!-- @TODO: fix this -->



                        <div class="breadcrumbs">
                            <?php if(function_exists('bcn_display'))
                            {
                                bcn_display();
                            }?>

                            <!-- trailingslashit(get_bloginfo('wpurl'))
                                . trailingslashit("clubs/list/" . $club['organizationId']); -->
                            >
            <!--//clubs -->         <span typeof="v:Breadcrumb"><a rel="v:url" property="v:title" title="Go to Clubs." href="http://westernusc.org/clubs/">Clubs</a></span>
                            >
            <!--//clubs list -->    <span typeof="v:Breadcrumb"><a rel="v:url" property="v:title" title="Go to the Clubs List." href="http://westernusc.org/clubs/list/">Clubs List</a></span>
                            >
            <!--//this club -->     <span typeof="v:Breadcrumb"><span property="v:title"><?php echo esc_html( $current_club['name'] ); ?></span></span>
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

                        <div class="usc_clubs-article-info">

                            <?php //et_divi_post_meta();

                            //a href="http://westernusc.org/blog/author/uscadmin/" title="Posts by USCAdmin" rel="author">USCAdmin</a> | Aug 8, 2014 |  | </p>

                            $html_string    = "";

                            $array_of_categories = $current_club['categories'];

                            //if no categories are assigned, get_the_terms() returns 'false'
                            if( false !== $array_of_categories ) {

                                $html_string .= '<p class="post-meta">';

                                foreach( $array_of_categories as &$category) {

                                    //var_dump($department);

                                    $html_string .= '<a href="#" style="cursor:default;">';
                                    $html_string .= esc_html($category['categoryName']) . "</a>, ";

                                }
                                unset($category);

                                $html_string = trim($html_string, ", ");
                                $html_string .= ' | ' . date('F j, Y');
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
                                'shortName',
                                'summary',
                                'description',
                                'email',
                                'externalWebsite',
                                'facebookUrl',
                                'twitterUrl',
                                'flickrFeedUrl',
                                'youtubeChannelUrl',
                                'googleCalendarUrl',
                                'profileImageUrl',
                            );

                            foreach($keys as &$key) {

                                if( !empty( $current_club[$key] ))
                                    $html_string .= '<h3>' . $key .'</h3><p>' . esc_html($current_club[$key]) . "</p><br>";
                            }
                            unset($key);

                            $summary = esc_html($current_club['summary']);
                            $description = esc_html($current_club['description']);

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