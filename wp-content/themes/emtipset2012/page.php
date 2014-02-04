<?php get_header(); ?>
<div id="head">
    <div class="container">
        <div id="small-header">
            <div id="sub-menu-container">
                <ul id="sub-menu">
                    <?php
                    if ($post->post_parent) {
                        wp_list_pages('title_li=&child_of='. $post->post_parent);
                    } else {
                        wp_list_pages('title_li=&child_of=' . $post->ID);
                    }
                    ?>
                </ul>
            </div>
            <h1><?php echo apply_filters('the_content', get_page($post->ID)->post_title) ?></h1>
        </div>
    </div>
</div>

<div id="main">
    <div class="container">
        <div id="main-left">
            <div class="content-wrapper">
                <?php echo apply_filters('the_content', get_page($post->ID)->post_content) ?>
            </div>
        </div>
        <div id="main-right">
            <div class="content-wrapper">
                <? get_sidebar() ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
