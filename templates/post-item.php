<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="post-thumbnail">
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail( 'post-thumbnail' ); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>

        <div class="entry-meta">
            <span class="posted-on"><?php echo get_the_date(); ?></span>
            <span class="byline"> <?php the_author_posts_link(); ?></span>
        </div>
    </header>

    <div class="entry-summary">
        <?php the_excerpt(); ?>
    </div>

    <footer class="entry-footer">
        <?php
        if ( get_the_category_list() ) {
            echo '<span class="cat-links">' . wp_kses_post( get_the_category_list( ', ' ) ) . '</span>';
        }
        if ( get_the_tag_list() ) {
            echo '<span class="tags-links">' . wp_kses_post ( get_the_tag_list( '', ', ' ) ) . '</span>';
        }
        ?>
    </footer>
</article>