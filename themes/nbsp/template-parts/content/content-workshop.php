<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_One
 * @since Twenty Twenty-One 1.0
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header alignwide one_line full_1200">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		
	</header><!-- .entry-header -->

	<div class="entry-content full_1200 workshop">
		<?php 
		
		the_content();
		
		$querystr = array(
			'posts_per_page' => 10,
			'post_type' => 'lesson',
			'post_status' => 'publish',
			'suppress_filters' => false,
			'orderby'   => 'date',
			'order'     => 'ASC'
			);
		$querystr['meta_key'] = 'workshop';
		$querystr['meta_value'] = $post->ID;
		
		$query = new WP_Query( $querystr );
		$ret = '';
		$i = 1;
		if ( $query->have_posts() ) : 
			while ( $query->have_posts() ) : 
				$query->the_post();			
				$ret .=  '<div class="full_1200">';						
					$ret .=  '<div class="workshop_item one_line">';						
						$ret .=  '<div class="crossword_title">';
							$ret .=  '<a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a>';
						$ret .= '</div>';															
					$ret .=  '</div>';
				$ret .=  '</div>';		
				$i++;
			endwhile;	
			wp_reset_postdata();
			
		else :
			$ret =  '<div class="full_1200 sorry_msg">';		
			$ret .= '<p>' . __( 'Sorry, no posts matched your criteria.', 'crossword' ) . '</p>';
			$ret .=  '</div>';
		endif;
		echo $ret;
	?>	
	</div><!-- .entry-content -->

	<footer class="entry-footer default-max-width">
		<?php twenty_twenty_one_entry_meta_footer(); ?>
	</footer><!-- .entry-footer -->


</article><!-- #post-<?php the_ID(); ?> -->
