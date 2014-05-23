<?php
/**
 * Created by PhpStorm.
 * User: Paul
 * Date: 22/05/14
 * Time: 7:11 PM
 */

get_header(); ?>

<div id="content" class="wrap clearfix">

    <div id="main" class="eightcol first clearfix" role="main">

        <article id="club-<?php echo '1'; ?>" <?php post_class('clearfix'); ?> role="article" itemscope itemtype="http://schema.org/BlogPosting">

            <header class="article-header">
                <p class="vcard"><?php
                    //printf(__('<time datetime="%1$s" pubdate>%2$s</time>', 'serena'), get_the_time('Y-m-j'), get_the_time(get_option('date_format')) );
                    echo '<time datetime="' . date('Y-m-j') . '" pubdate>' . date('Y-m-j') . '</time>';
                    ?></p>
                <h1 class="entry-title single-title" itemprop="headline">CLUB <?php echo get_query_var('clubsapi'); ?></h1>
                <p class="author vcard"><?php
                    //printf(__('by %1$s, under %2$s', 'serena'), serena_get_the_author_posts_link(), get_the_category_list(', '));
                    echo 'by ' . serena_get_the_author_posts_link() . 'under nothing';
                    ?></p>

            </header> <!-- end article header -->

            <section class="entry-content clearfix" itemprop="articleBody">
                <?php echo "The content"; ?>
            </section> <!-- end article section -->

            <footer class="article-footer">
                article footer
                <?php //wp_link_pages(); ?>
                <?php //the_tags('<p class="tags"><span class="tags-title">' . __('Tags:', 'serena') . '</span> ', ', ', '</p>'); ?>
                <div class="post-link">
                    post link
                    <?php /*
                        previous_post_link('%link', 'prev');
                        next_post_link('%link', 'next');
                        */ ?>
                </div>
            </footer> <!-- end article footer -->

            <?php //comments_template(); ?>

        </article> <!-- end article -->

    </div> <!-- end #main -->

    <?php get_sidebar(); ?>

</div> <!-- end #content -->

<?php get_footer(); ?>
