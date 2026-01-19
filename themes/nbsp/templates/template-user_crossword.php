<?php
/**
 * Template Name: User Crossword
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
  
   // בדיקה אם המשתמש מחובר
   $current_user_id = get_current_user_id();
   if (!$current_user_id) {
       $ret = '<div class="full_1200 sorry_msg">';
       $ret .= '<p>יש להתחבר כדי לראות את התשבצים שלך</p>';
       $ret .= '</div>';
       echo $ret;
       return;
   }
   
   // Query - ננסה שתי גישות: לפי author (הכי פשוט) ולפי meta_query
   // קודם ננסה לפי author בלבד - זה הכי אמין
   $querystr = array(			
		'post_type' => 'user_crossword',
		'post_status' => 'publish',
		'author' => $current_user_id, // לפי author - הכי פשוט ואמין
		'posts_per_page' => -1, // כל התשבצים
		'orderby' => 'date',
		'order' => 'DESC'
	);
	
	$ret = '';
	$query = new WP_Query( $querystr );
	
	// DEBUG - תמיד גלוי כדי לזהות בעיות
	global $wpdb;
	
	// בדיקה ישירה ב-DB
	$db_posts = $wpdb->get_results($wpdb->prepare(
		"SELECT ID, post_author, post_title FROM {$wpdb->posts} 
		 WHERE post_type = 'user_crossword' 
		 AND post_status = 'publish' 
		 AND post_author = %d
		 ORDER BY post_date DESC",
		$current_user_id
	));
	
	// בדיקת meta - נבדוק גם את field_642eda143c316 (ACF field ID)
	$meta_posts = $wpdb->get_results($wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} 
		 WHERE (meta_key = 'user' OR meta_key = 'field_642eda143c316')
		 AND meta_value = %d",
		$current_user_id
	));
	
	// בדיקה גם ב-ACF reference field
	$acf_ref_posts = $wpdb->get_results($wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} 
		 WHERE meta_key = '_user' 
		 AND meta_value = %s",
		'field_642eda143c316'
	));
	
	echo '<!-- ========== DEBUG START ========== -->';
	echo '<!-- Template loaded: template-user_crossword.php -->';
	echo '<!-- Current User ID: ' . $current_user_id . ' -->';
	echo '<!-- Is logged in: ' . (is_user_logged_in() ? 'YES' : 'NO') . ' -->';
	echo '<!-- WP_Query by author: Found ' . $query->found_posts . ' posts -->';
	echo '<!-- Direct DB check (by post_author): Found ' . count($db_posts) . ' posts -->';
	if (!empty($db_posts)) {
		foreach ($db_posts as $db_post) {
			$acf_user = get_field('user', $db_post->ID);
			$acf_user_field_id = get_field('field_642eda143c316', $db_post->ID);
			echo '<!-- Post ID: ' . $db_post->ID . ', Title: ' . esc_html($db_post->post_title) . ', Author: ' . $db_post->post_author . ', ACF User (by name): ' . ($acf_user ? $acf_user : 'NULL') . ', ACF User (by field ID): ' . ($acf_user_field_id ? $acf_user_field_id : 'NULL') . ' -->';
		}
	}
	echo '<!-- Meta check (by user/user field): Found ' . count($meta_posts) . ' posts -->';
	if (!empty($meta_posts)) {
		foreach ($meta_posts as $meta_post) {
			$post_obj = get_post($meta_post->post_id);
			if ($post_obj && $post_obj->post_type === 'user_crossword') {
				echo '<!-- Meta Post ID: ' . $meta_post->post_id . ', Title: ' . esc_html($post_obj->post_title) . ', Author: ' . $post_obj->post_author . ' -->';
			}
		}
	}
	echo '<!-- ACF Reference field check: Found ' . count($acf_ref_posts) . ' posts -->';
	echo '<!-- ========== DEBUG END ========== -->';
	
	// אם לא מצאנו לפי author, ננסה לפי meta_query
	// נבדוק גם את 'user' וגם את 'field_642eda143c316' (ACF field ID)
	if (!$query->have_posts()) {
		$querystr2 = array(			
			'post_type' => 'user_crossword',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => 'user',
					'value'   => $current_user_id,
					'compare' => '=',
					'type'    => 'NUMERIC'
				),
				array(
					'key'     => 'field_642eda143c316',
					'value'   => $current_user_id,
					'compare' => '=',
					'type'    => 'NUMERIC'
				)
			)
		);
		$query = new WP_Query( $querystr2 );
		
		// DEBUG
		echo '<!-- DEBUG: Query by meta_query (user OR field_642eda143c316) found ' . $query->found_posts . ' posts -->';
	}
	
	if ( $query->have_posts() ) : 	
		$i = 1;
		while ( $query->have_posts() ) : 
			$query->the_post();
			
			// קבלת קישור לתשבץ הבסיסי
			$base_crossword_id = get_field('crossword');
			$base_crossword_url = $base_crossword_id ? get_permalink($base_crossword_id) : '#';
			
			// אינדיקציית סטטוס
			$status_indicator = '';
			if (function_exists('get_crossword_status') && function_exists('get_crossword_status_indicator') && $base_crossword_id) {
				$status = get_crossword_status($base_crossword_id);
				$status_indicator = get_crossword_status_indicator($status);
			}
			
			$ret .=  '<div class="user_crossword_line one_line">';
				$ret .=  '<div class="crossword_name">' . $post->post_title . '</div>';
				if ($status_indicator) {
					$ret .= '<div class="crossword_status_wrapper">' . $status_indicator . '</div>';
				}
				$ret .=  '<div class="crossword_ref"><a href="' . esc_url($base_crossword_url) . '">';
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

