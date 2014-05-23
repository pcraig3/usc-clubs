<?php
/**
 * Builds a dynamically-generated page from the github API for clubs
 *
 */

//GET THE DATA

$desired_club_id = intval( get_query_var('clubsapi') );

//function returns the clubs on github as a json array.
$clubs_array = Testwestern_Testplugin::call_api();

if( ! is_array( $clubs_array ) ) {
    return false;
}

$max = intval( $clubs_array['total'] );

$current_club = $previous_club = $next_club = null;

for($i = 0; $i < $max && is_null( $current_club ); $i++) {

    //if it matches
    if( $desired_club_id === $clubs_array['clubs'][$i]['organizationId']) {

        if( $i > 0 ) {
            $previous_club = $clubs_array['clubs'][$i - 1];
        }

        if( $i < ( $max-1 ) ) {
            $next_club = $clubs_array['clubs'][$i + 1];
        }

        $current_club = $clubs_array['clubs'][$i];
    }
}

if( ! is_null( $current_club ) ) {
    return false;
}


//CHANGE THE PAGE TITLE

$club_name = esc_html( $current_club['name'] );

add_filter( 'wp_title', function( $title ) use ( $club_name ) {

        return $club_name . " | testwestern.com";
});

///BUILD THE PAGE

/*          "organizationId": 1646,
            "name": "Acapella Project",
            "shortName": "TAP",
            "summary": "The Acapella Project is an a-cappella choir which aims to bring its unique brand of music to the community! Our first rehearsal is this coming Sunday, September 22, from 1 to 3 pm in VAC100. Hope to see you all there!",
            "description": "The Acapella Project is a USC governed acapella choir which provides an open forum for those interested in singing, arranging, and beatboxing a-cappella. We accept members regardless of experience, and aim to educate and improve singers&rsquo; musicianship, vocal health, and performing experience.&nbsp;",
            "email": "theacapellaproject@gmail.com",
            "facebookUrl": "http://www.facebook.com/theacapellaproject",
            "twitterUrl": "",
            "profileImageUrl": "westernu.collegiatelink.net/images/W170xL170/0/noshadow/Profile/3afeb90c7fbd43c5b6cf8d4b40b7966d.jpg",
            "id": 0
 */

get_header(); ?>

<div id="content" class="wrap clearfix">

    <div id="main" class="eightcol first clearfix" role="main">

        <article id="club-<?php echo intval( $current_club['organizationId'] ); ?>" class="post type-post status-publish format-standard hentry category-club clearfix"
                 role="article" itemscope itemtype="http://schema.org/BlogPosting">

            <header class="article-header">
                <p class="vcard"><?php
                    //printf(__('<time datetime="%1$s" pubdate>%2$s</time>', 'serena'), get_the_time('Y-m-j'), get_the_time(get_option('date_format')) );
                    echo '<time datetime="' . date('Y-m-j') . '" pubdate>' . date('F d, Y') . '</time>';
                    ?></p>
                <h1 class="entry-title single-title" itemprop="headline"><?php echo esc_html( $current_club['name'] ); ?></h1>
                <p class="author vcard"><?php
                    //printf(__('by %1$s, under %2$s', 'serena'), serena_get_the_author_posts_link(), get_the_category_list(', '));
                    $email = sanitize_email( $current_club['email'] );

                    if($email)
                        echo '<a href="mailto:' . antispambot( $email, 1 ) .
                            '" title="Click to e-mail" >' . antispambot( $email ) . '</a><br>';
                    echo '<em>Category</em>';
                    ?></p>

            </header> <!-- end article header -->

            <br>
            <section class="entry-content clearfix" itemprop="articleBody">
                <?php

                    if($current_club['profileImageUrl']) {
                        $profile_image_url = "http://" . $current_club['profileImageUrl'];

                        echo '<h3>LOGO</h3>';
                        echo '<img class="club-logo" src="' . esc_url( $profile_image_url ) . '">';
                    }

                    $content_added = false;

                    if( ! empty($current_club['summary']) ) {
                        $content_added = true;

                        echo '<h3>SUMMARY</h3>';
                        echo '<p>' . esc_html($current_club['summary']) . '</p>';
                        echo '<br>';
                    }

                    if( ! empty($current_club['description']) ) {
                        $content_added = true;

                        echo '<h3>DESCRIPTION</h3>';
                        echo '<p>' . esc_html($current_club['description']) . '</p>';
                        echo '<br>';
                    }

                    if( ! $content_added ) {
                        echo '<p>' . 'Whoops!  Looks like we don\'t have any content for this club yet.';
                    }
                ?>
            </section> <!-- end article section -->

            <footer class="article-footer">
                <?php //wp_link_pages(); ?>
                <?php //the_tags('<p class="tags"><span class="tags-title">' . __('Tags:', 'serena') . '</span> ', ', ', '</p>'); ?>
                <div class="post-link">
                    <?php

                    if( ! is_null($previous_club) ) {
                        echo '<a rel="prev" href="http://testwestern.com/clubs/' . intval($previous_club['organizationId']) . '/">' .
                            esc_html($previous_club['name']) . '</a>';
                    }

                    if( ! is_null($next_club) ) {
                        echo '<a rel="next" href="http://testwestern.com/clubs/' . intval($next_club['organizationId']) . '/">' .
                            esc_html($next_club['name']) . '</a>';
                    }
                    ?>
                </div>
                <div class="category-link">

                    <?php
                    echo '<a rel="back" href="http://testwestern.com/clubs-from-github/">' .
                         'Back to Clubs List</a>';
                    ?>

                </div>
            </footer> <!-- end article footer -->

            <?php //comments_template(); ?>

        </article> <!-- end article -->

    </div> <!-- end #main -->

    <?php get_sidebar(); ?>

</div> <!-- end #content -->

<?php get_footer(); ?>
