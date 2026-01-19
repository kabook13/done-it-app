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
<?php echo do_shortcode('[print-me target="article"/]'); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header alignwide one_line full_1200">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		
	</header><!-- .entry-header -->

	<div class="entry-content full_1200">
		<?php
		the_content();

		$size_x = get_field( 'size_x' ); 
		$size_y = get_field( 'size_y' );
		$tabindex =1;
		$i =0;
		$total_cel =1;
		$vertical_txt ='';
		$horizontal_txt ='';
		$saved_json = '';
		
		$querystr = array(			
			'posts_per_page' => 1,
			'post_type' => 'user_crossword',
			'post_status' => 'publish',
		);
		
		$querystr['meta_query']  = array('relation' => 'AND',
									array(
										'key'     => 'user',
										'value'   => get_current_user_id(),
										
									),
									array(
										'key'     => 'crossword',
										'value'   => $post->ID,
										
									),
									
									);	
		$query = new WP_Query( $querystr );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : 
				$query->the_post();
				$saved_json = json_decode(get_field( 'json' ));
				
				
			endwhile; 
			wp_reset_postdata();
			
		}
		$base_json = json_decode(get_field( 'json' ));
		
		//echo json_encode($the_json, JSON_PRETTY_PRINT);		
		
		$the_puzzle ='<form action="/action_page.php" id="puzzle_form"><div class="puzzle_rep"><div class="puzzle">';
		foreach($base_json as $cel){           
			$puzzle_cel_letter = $cel->letter;
			$puzzle_cel_number = $cel->number ? $cel->number : 0 ;
			
			if($i==0){
				$the_puzzle .='<div class="puzzle_line">';
			}
			
			if($puzzle_cel_letter == "X"){
				$black_cell = ' black_cell';
			}else{
				$black_cell = '';
			}
			$mark_border = $cel->mark_border ? $cel->mark_border : '';
			if($mark_border){
				$mark_border = ' border_' . $mark_border;
			}
			$the_puzzle .='<div class="puzzle_cel puzzle_cel_' . $total_cel . $black_cell . $mark_border .'">';
				$the_puzzle .='<div class="puzzle_cel_number">';
				if($puzzle_cel_number > 0){
					$the_puzzle .='<div class="puzzle_cel_number_val">' . $puzzle_cel_number . '</div>';
				}
				$the_puzzle .='</div>';
				$the_puzzle .='<div class="puzzle_cel_letter">';
					if($puzzle_cel_letter != "X"){
						$puzzle_saved_cel_letter =  $saved_json->$total_cel->letter ? json_decode('"\\' . $saved_json->$total_cel->letter . '"') : '';
						$the_puzzle .='<div class="puzzle_cel_letter_val">';				
							$the_puzzle .= '<input type="text" value="' . $puzzle_saved_cel_letter . '" class="cel_letter" id="cel_letter_' . $total_cel .'" name="cel_letter_' . $total_cel .'" maxlength="1" tabindex="' . $tabindex++ .'">';
						$the_puzzle .='</div>';
					}
				$the_puzzle .='</div>';
			$the_puzzle .='</div>';
			
	
			$i = $i +1;
			if($i == $size_x){
				$the_puzzle .='</div>';
				$i=0;
			}
			$total_cel = $total_cel +1;			
        }
		$the_puzzle .='</div></div>';
		
		$the_puzzle .='<div class="puzzle_submit">';
		//$the_puzzle .=' <input type="submit" id="puzzle_check" value="בדיקה">';
		//$the_puzzle .=' <input type="submit" id="puzzle_save" value="שמירה">';		
		$the_puzzle .='</div></form>';
		$the_puzzle .=' <input type="hidden" id="size_x" name="size_x" value="' . $size_x .'">';
		$the_puzzle .=' <input type="hidden" id="size_y" name="size_y" value="' . $size_y .'">';
		$the_puzzle .=' <input type="hidden" id="the_post_id" name="the_post_id" value="' . $post->ID .'">';
		
		$the_puzzle .=' <button type="button" id="puzzle_check">בדיקה</button>';
		$the_puzzle .=' <button type="button" id="puzzle_save">שמירה</button>';	
		echo $the_puzzle;
		$i =0;
		while ( have_rows('line') ) : the_row();							
			$number = get_sub_field('number');			
			$definition = get_sub_field('definition');
			echo $place_x;
			echo $place_y;
			echo $solution;
			$i = $i +1; 
			$words_length = " (" . get_sub_field('first_word_length');
			$val = get_sub_field('second_word_length') ;
			if($val > 0 ){
				$words_length .= ", " . $val;
			}
			$val = get_sub_field('third_word_length') ;
			if($val > 0 ){
				$words_length .= ", " . $val;
			}
			$words_length .= ")";
			
			
			if(1 == get_sub_field('vertical_or_horizontal')){
				$vertical_txt .= $number . '. ' . $definition . $words_length . '</br>';
			}else{
				$horizontal_txt .= $number . '. ' . $definition . $words_length . '</br>';
			}																
		endwhile; 		 
		
		?>
		
		
		<div class="text-content puzzle_check-container"></div>
		
		<div class="text-content definitions_title">הגדרות</div>
		<div class="one_line definitions_obj">
			<div class="definitions definitions_r">
				<div class="text-content title">מאוזן</div>		 
				<div class="vertical_txt list"><?php echo $vertical_txt; ?></div>
			</div>
			<div class="definitions definitions_l">	
				<div class="text-content title">מאונך</div>		 
				<div class="horizontal_txt list"><?php echo $horizontal_txt; ?></div>
			</div>	
		</div>
		
	</div><!-- .entry-content -->

	<footer class="entry-footer default-max-width">
		<?php twenty_twenty_one_entry_meta_footer(); ?>
	</footer><!-- .entry-footer -->


</article><!-- #post-<?php the_ID(); ?> -->

