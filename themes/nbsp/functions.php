<?php
add_action("wp_enqueue_scripts", "wp_child_theme");
function wp_child_theme() 
{  
	wp_enqueue_style("parent-stylesheet", get_template_directory_uri()."/style.css");    

	wp_enqueue_style("child-stylesheet", get_stylesheet_uri());
	wp_enqueue_script("child-scripts", get_stylesheet_directory_uri() . "/js/view.js", array("jquery"), "6.1.1", true);
	wp_enqueue_script( 'ajax-pagination',  get_stylesheet_directory_uri() . '/scripts/view.js', array( 'jquery' ), '1.0', true );
	wp_localize_script( 'ajax-pagination', 'ajaxpagination', array(	'ajaxurl' => admin_url( 'admin-ajax.php' )));
	
	global $wp_query;
	wp_localize_script( 'ajax-pagination', 'ajaxpagination', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'query_vars' => json_encode( $wp_query->query )
	));
	
}

function wp_child_theme_register_settings() 
{ 
	register_setting("wp_child_theme_options_page", "wp_child_theme_setting", "wct_callback");
}
add_action("admin_init", "wp_child_theme_register_settings");

function wp_child_theme_register_options_page() 
{
	add_options_page("Child Theme Settings", "Child Theme", "manage_options", "wp_child_theme", "wp_child_theme_register_options_page_form");
}
add_action("admin_menu", "wp_child_theme_register_options_page");

function wp_child_theme_register_options_page_form()
{ 
?>
<div id="wp_child_theme">
    <h1>Child Theme Options</h1>
    <h2>Include or Exclude Parent Theme Stylesheet</h2>
    <form method="post" action="options.php">
        <?php settings_fields("wp_child_theme_options_page"); ?>
        <p><label><input size="3" type="checkbox" name="wp_child_theme_setting" id="wp_child_theme_setting" <?php if((esc_attr(get_option("wp_child_theme_setting")) == "Yes")) { echo " checked "; } ?> value="Yes"> Tick To Disable The Parent Stylesheet (style.css) In Your Site HTML<label></p>
        <?php submit_button(); ?>
    </form>
    <p>Only Tick This Box If When You Inspect Your Source Code It Contains Your Parent Stylesheet style.css Two Times.</label></p>
</div>
<?php
}

add_action( 'wp_ajax_tpuzzle_check', 'tpuzzle_check' );
add_action( 'wp_ajax_nopriv_tpuzzle_check', 'tpuzzle_check' );

function tpuzzle_check() {	
    echo puzzle_check();    
    die();
}

function puzzle_check(){
	$the_form = $_POST['the_form'];
	if($the_form){ 
		$json_array = array();
		$width = ( get_query_var( 'size_x' ) ) ? get_query_var( 'size_x' ) : 1;
		if($width == 1){
			$width = ( $_POST['size_x'] ) ? $_POST['size_x'] : 1;
		}
		$length = ( get_query_var( 'size_y' ) ) ? get_query_var( 'size_y' ) : 1;
		if($length == 1){
			$length = ( $_POST['size_y'] ) ? $_POST['size_y'] : 1;
		}
		$the_post_id = ( get_query_var( 'the_post_id' ) ) ? get_query_var( 'the_post_id' ) : 1;
		if($the_post_id == 1){
			$the_post_id = ( $_POST['the_post_id'] ) ? $_POST['the_post_id'] : 1;
		}		
				
		$query = unicode_urldecode($the_form);
		$solution_json = json_decode(get_field( 'field_63e755ac9275c', $the_post_id), true);

		foreach (explode('&', $query) as $chunk) {
			// Split letter index and value
			$param = explode("=", $chunk);
			$cel_index = str_replace('cel_letter_','',urldecode($param[0]));
					
			$user_letter = str_replace('\\','',json_encode($param[1]));
			$solution_letter = str_replace('\\','',json_encode($solution_json[$cel_index]['letter']));
			
			if( $user_letter == '' or $user_letter != $solution_letter){
				$line = floor($cel_index / $width) + 1;
				$place = $cel_index % $width;
				
				 $ret =  " יש שגיאה בשורה " . $line . " במיקום " . $place ;
				return $ret;
			}
		}
		return "זהה!!!";
	}
	return 'תקלה';
}
add_action( 'wp_ajax_tpuzzle_save', 'tpuzzle_save' );
add_action( 'wp_ajax_nopriv_tpuzzle_save', 'tpuzzle_save' );

function tpuzzle_save() {	
    echo puzzle_save();    
    die();
}

function puzzle_save(){
	$the_post_id = ( get_query_var( 'the_post_id' ) ) ? get_query_var( 'the_post_id' ) : 1;
	if($the_post_id == 1){
		$the_post_id = ( $_POST['the_post_id'] ) ? $_POST['the_post_id'] : 1;
	}
	$the_form = $_POST['the_form'];
	if(isset($_POST['the_form'])){
		$query = unicode_urldecode($the_form);

		foreach (explode('&', $query) as $chunk) {
			$param = explode("=", $chunk);
			$i = str_replace('cel_letter_','',urldecode($param[0]));
			$json_array[$i]['letter'] = urldecode($param[1]);
			
		}
		
		
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
										'value'   => $the_post_id,
										
									),
									
									);	
		$query = new WP_Query( $querystr );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : 
				$query->the_post();
				update_field('field_642eda713c318', json_encode($json_array), $post->ID);  // the json
			endwhile; 
			wp_reset_postdata();
			return "עודכן בהצלחה!";
		}else{
			
			$wordpress_post = array(
				'post_title' => get_the_title($the_post_id),
				'post_status' => 'publish',
				'post_author' => 1,
				'post_type' => 'user_crossword'
				);
	 
			$insert_post_post_id = wp_insert_post( $wordpress_post );
			
			update_field('field_642eda143c316', get_current_user_id() , $insert_post_post_id);  //User
			update_field('field_642eda4c3c317', $the_post_id, $insert_post_post_id); // the crossword
			update_field('field_642eda713c318', json_encode($json_array), $insert_post_post_id);  // the json
		}
		
		return "נשמר בהצלחה!";//json_encode($json_array);
		
	}
	return 'תקלה';
}

function get_marked_borders_indexes() {
	// Keys of the stored words lengths.
	$words_length_keys = array(
		'first_word_length',
		'second_word_length',
		'third_word_length',
	);

	$marked_borders_indexes = array();
	// Initialize solution's words endings indexes for marked borders.
	for ($i = 0; $i < count($words_length_keys) - 1; $i++) {
		$current_word_length = get_sub_field($words_length_keys[$i]);
		$next_word_length = get_sub_field($words_length_keys[$i + 1]);
		// If next word length is 0 is means that there are no more words in this definition. No marked border should be added.
		if ($next_word_length == 0) {
			break;
		}
		// Add the first marked border index undependent in non-existing previous words.
		if ($i == 0) {
			array_push($marked_borders_indexes, $current_word_length - 1);
		}
		// Add the current marked border index - prev marked border index added with current word length.
		else {
			$prev_marked_border_index = $marked_borders_indexes[$i - 1];
			array_push($marked_borders_indexes, $current_word_length + $prev_marked_border_index);
		}
	}
	return $marked_borders_indexes;
}

add_action('save_post','save_post_callback');
function save_post_callback($post_id){
    global $post; 
    if ($post->post_type != 'crossword'){
        return;
    }
	
	$json_array = array();
	// Initialize blacked crossword.
	$width = get_field( 'size_x' ); 
	$length = get_field( 'size_y' );
	$total_obj = $width * $length;

	for ($i = 1; $i <= $total_obj; $i++) { // TODO: I would start if from 0.
		// Set default value to black.
		$json_array[$i]['letter'] = 'X';
	}

	while ( have_rows('line') ) : the_row();							
		$number = get_sub_field('number');
		// Initialize starting index.
		$start_col = get_sub_field('place_y') - 1;			
		$start_row = get_sub_field('place_x') - 1;
		$start_point = ($start_row * $width) + $start_col + 1;  // TODO: This +1 is redundant if we start from 0.
	
        // Convert solution to array of unicodes.
		preg_match_all('/./u', get_sub_field('solution'), $current_solution);
        $current_solution = $current_solution[0];
		$current_solution_len = count($current_solution);

		$json_array[$start_point]['number'] = $number;
		$json_array[$start_point]['current_len'] = $current_solution_len;

        // Initialize solution's words endings indexes for marked borders.
        $marked_borders_indexes = get_marked_borders_indexes();

        // Save current solution in the right indexes in the JSON.
		if (1 == get_sub_field('vertical_or_horizontal')) {
			// Horizontal.
			for ($i = 0; $i < $current_solution_len; $i++) {
                $current_json_index = $start_point + $i;
				$json_array[$current_json_index]['letter'] = $current_solution[$i];
                if (in_array($i, $marked_borders_indexes)){
                    if ($i != ($width - $start_col) - 1){
                        $json_array[$current_json_index]['mark_border'] = 'left';
                    }
                }
			}
		} else {
			// Vertical.
			for ($i = 0; $i < $current_solution_len; $i++) {
				$current_json_index = $start_point + ($width * $i);
				$json_array[$current_json_index]['letter'] = $current_solution[$i];
                if (in_array($i, $marked_borders_indexes)){
                    if ($i != ($length - $start_row) - 1){
                        $json_array[$current_json_index]['mark_border'] = 'bottom';
                    }
                }
			}
		}
															
	endwhile; 

	return update_field( 'field_63e755ac9275c', json_encode($json_array), $post->ID);
}


function crosswords_list(){
	global $post;
    
	$query = array(
        'posts_per_page' => 10,
		'post_type' => 'crossword',
        'post_status' => 'publish',
		'suppress_filters' => false,
        'orderby'   => 'date',
        'order'     => 'DESC'
    );
	
    $query = new WP_Query( $query );
	$ret = '';
	$i = 1;
	if ( $query->have_posts() ) : 
		while ( $query->have_posts() ) : 
			$query->the_post();			
			$ret .=  '<div class="full_1200">';						
				$ret .=  '<div class="crossword_item one_line crossword_item_'. $i .'">';	
					$ret .=  '<div class="crossword_image">';
						$ret .= get_my_post_thumbnail($post->ID, 100, 100);
					$ret .=  '</div>';
					$ret .=  '<div class="crossword_data">';
						$ret .=  '<div class="crossword_date">' .  get_the_date('Y-m-d', $post->ID) . '</div>';
						$ret .=  '<div class="crossword_title">';
							$ret .=  '<a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a>';
						$ret .= '</div>';					
					$ret .=  '</div>';										
				$ret .=  '</div>';
			$ret .=  '</div>';		
			$i++;
		endwhile;	
		wp_reset_postdata();
		
	else :
		$ret =  '<div class="full_1200 sorry_msg">';		
		$ret .= '<p>' . __( 'Sorry, no posts matched your criteria.', 'kia' ) . '</p>';
		$ret .=  '</div>';
	endif;
	
	return $ret;
}


function get_my_post_thumbnail($post_ID, $width = NULL,$height = NULL ){
	if(!$post_ID){return '';}
	$image_id = get_post_thumbnail_id($post_ID) ;
	if(!$image_id){return '';}
	$image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', TRUE);
	$ret = '<img src="' . wp_get_attachment_url( $image_id ) . '" ';
	if($width){
		$ret .= 'width="' . $width . 'px" ';
	}
	if($height){
		$ret .= 'height="' . $height . 'px" ';
	}
	if($image_alt){
		$ret .= 'alt="' . $image_alt . '" ';
	}else{
		$ret .= 'alt="' . $image_id . '" ';
	}
	$ret .= '/>';
	return  $ret;
}

function unicode_urldecode($url){
    preg_match_all('/%u([[:alnum:]]{4})/', $url, $a);
   
    foreach ($a[1] as $uniord){
        $dec = hexdec($uniord);
        $utf = '';
       
        if ($dec < 128){
            $utf = chr($dec);
        }
        else if ($dec < 2048){
            $utf = chr(192 + (($dec - ($dec % 64)) / 64));
            $utf .= chr(128 + ($dec % 64));
        }
        else{
            $utf = chr(224 + (($dec - ($dec % 4096)) / 4096));
            $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
            $utf .= chr(128 + ($dec % 64));
        }
       
        $url = str_replace('%u'.$uniord, $utf, $url);
    }
   
    return urldecode($url);
}
