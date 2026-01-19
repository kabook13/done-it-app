<?php
/**
 * Template Name: Workshop List
 *
 */

get_header();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header alignwide one_line full_1200">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		
	</header><!-- .entry-header -->

	<div class="full_1200">
		<div class="user_crossword_list">
	<?php
  
   $querystr = array(			
		'post_type' => 'workshop',
		'post_status' => 'publish'
		);
	
	
	$ret = '';
	$query = new WP_Query( $querystr );
	if ( $query->have_posts() ) : 	
		$i = 1;
		while ( $query->have_posts() ) : 
			$query->the_post();
			
			$ret .=  '<div class="user_crossword_line one_line">';	
				$ret .=  '<div class="crossword_date">' .  get_the_date('Y-m-d', $post->ID) . '</div>';
				$ret .=  '<div class="crossword_name"><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></div>';
				$ret .=  '<div class="crossword_ref"><a href="' . get_permalink( get_field( 'crossword' ) ) . '">';
					$ret .=  "הצג";
				$ret .=  '</a></div>';							
			$ret .=  '</div>';			
			
			$i++;		
		endwhile; 
		wp_reset_postdata();
	else :
		$ret =  '<div class="full_1200 sorry_msg">';		
		$ret .= '<p>לא נמצא מידע</p>';
		$ret .=  '</div>';
	endif;
	echo $ret;



echo '</div>';
echo '</div>';

get_footer();

?>
</article>
