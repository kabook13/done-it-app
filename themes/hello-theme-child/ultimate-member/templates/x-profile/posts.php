<?php
 /* Template: posts One */
/**
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/profile/posts-single.php
 *
 * Page: "Profile"
 *
 * @version 2.6.1
 *
 * @var object $posts
 * @var int    $count_posts
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the current profile user ID (the profile being viewed).
$current_profile_id = um_profile_id();

// Build a custom query that retrieves posts of type 'crossword' and 'user_crossword'
// created by the current profile user.
$args = array(
    'post_type'      => array( 'crossword', 'user_crossword' ),
    'posts_per_page' => 10,
    'post_status'    => 'publish',
    'author'         => $current_profile_id,
    'orderby'        => 'date',
    'order'          => 'DESC',
);

// You can add debugging: error_log(print_r($args,true));

$profile_query = new WP_Query( $args );
?>
