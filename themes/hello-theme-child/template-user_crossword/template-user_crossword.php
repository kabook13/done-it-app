<?php
/**
 * Template Name: User Crossword
 *
 */

get_header();

// URL לכותרת מעוצבת
$header_saved_crosswords_url = 'https://higayonbarie.co.il/wp-content/uploads/2025/04/saved-crosswords.svg';
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header alignwide one_line full_1200 hb-user-crosswords-header">
		<?php 
		// הצגת כותרת מעוצבת
		echo '<div class="page-featured-image hb-page-header-logo">';
		echo '<img src="' . esc_url($header_saved_crosswords_url) . '" alt="התשבצים שלי" class="hb-header-logo-img" />';
			echo '</div>';
		?>
		
	</header><!-- .entry-header -->

	<div class="full_1200">
		<div class="user_crossword_list">
	<?php
  
   // בדיקה אם המשתמש מחובר
   $current_user_id = get_current_user_id();
   $is_logged_in = is_user_logged_in();
   
   // DEBUG - תמיד גלוי כדי לזהות בעיות
   echo '<!-- ========== USER CROSSWORDS DEBUG START ========== -->';
   echo '<!-- Current User ID: ' . $current_user_id . ' -->';
   echo '<!-- Is logged in: ' . ($is_logged_in ? 'YES' : 'NO') . ' -->';
   echo '<!-- User Agent: ' . esc_html($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . ' -->';
   echo '<!-- Request URI: ' . esc_html($_SERVER['REQUEST_URI'] ?? 'Unknown') . ' -->';
   echo '<!-- Page ID: ' . get_the_ID() . ' -->';
   echo '<!-- Page Template: ' . get_page_template_slug(get_the_ID()) . ' -->';
   
   if (!$current_user_id || !$is_logged_in) {
       $ret = '<div class="full_1200 sorry_msg">';
       $ret .= '<p>יש להתחבר כדי לראות את התשבצים שלך</p>';
       $ret .= '</div>';
       echo $ret;
       echo '<!-- DEBUG: User not logged in, returning early -->';
       echo '<!-- ========== USER CROSSWORDS DEBUG END ========== -->';
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
	
	echo '<!-- DEBUG: WP_Query by author found ' . $query->found_posts . ' posts -->';
	
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
	
	echo '<!-- Template loaded: template-user_crossword.php -->';
	echo '<!-- WP_Query by author: Found ' . $query->found_posts . ' posts -->';
	echo '<!-- Direct DB check (by post_author): Found ' . count($db_posts) . ' posts -->';
	
	// Check PMPro access
	if (function_exists('pmpro_has_membership_access')) {
		$pmpro_access = pmpro_has_membership_access(get_the_ID());
		echo '<!-- PMPro has_membership_access: ' . ($pmpro_access ? 'YES' : 'NO') . ' -->';
	}
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
			
			// קבלת מידע על התשבץ הבסיסי
			$base_crossword_title = '';
			$base_crossword_date = '';
			$base_crossword_image = '';
			$base_crossword_size = '';
			
			if ($base_crossword_id) {
				$base_crossword_post = get_post($base_crossword_id);
				if ($base_crossword_post && $base_crossword_post->post_type === 'crossword') {
					$base_crossword_title = $base_crossword_post->post_title;
					$base_crossword_date = get_the_date('d.m.Y', $base_crossword_id);
					
					// קבלת תמונה
					if (has_post_thumbnail($base_crossword_id)) {
						$base_crossword_image = get_the_post_thumbnail($base_crossword_id, 'thumbnail', array('class' => 'crossword-thumbnail'));
					} else {
						// תמונה ברירת מחדל - נשתמש ב-featured image של user_crossword או תמונה ברירת מחדל
						if (has_post_thumbnail($post->ID)) {
							$base_crossword_image = get_the_post_thumbnail($post->ID, 'thumbnail', array('class' => 'crossword-thumbnail'));
						} else {
							// אם יש תמונה ברירת מחדל מוגדרת
							if (defined('DEFAULT_CROSSWORD_IMAGE_ID') && DEFAULT_CROSSWORD_IMAGE_ID) {
								$default_image_url = wp_get_attachment_image_url(DEFAULT_CROSSWORD_IMAGE_ID, 'thumbnail');
								if ($default_image_url) {
									$base_crossword_image = '<img src="' . esc_url($default_image_url) . '" alt="תשבץ" class="crossword-thumbnail" />';
								}
							}
						}
					}
					
					// גודל התשבץ
					$size_x = get_field('size_x', $base_crossword_id);
					$size_y = get_field('size_y', $base_crossword_id);
					if ($size_x && $size_y) {
						$base_crossword_size = $size_x . ' × ' . $size_y;
					}
				}
			}
			
			// תאריך שמירה
			$saved_date = get_the_date('d.m.Y', $post->ID);
			$saved_time = get_the_time('H:i', $post->ID);
			
			// אינדיקציית סטטוס
			$status_indicator = '';
			if (function_exists('get_crossword_status') && function_exists('get_crossword_status_indicator') && $base_crossword_id) {
				$status = get_crossword_status($base_crossword_id);
				$status_indicator = get_crossword_status_indicator($status);
			}
			
			// בניית הכרטיס
			$ret .=  '<div class="user_crossword_card">';
				// תמונה
				$ret .=  '<div class="crossword_card_image">';
					if ($base_crossword_image) {
						$ret .= '<a href="' . esc_url($base_crossword_url) . '">' . $base_crossword_image . '</a>';
					}
				$ret .=  '</div>';
				
				// תוכן הכרטיס
				$ret .=  '<div class="crossword_card_content">';
					// כותרת - תמיד משתמש בכותרת של התשבץ הבסיסי
					$ret .=  '<div class="crossword_card_title">';
						$display_title = $base_crossword_title ? $base_crossword_title : ($base_crossword_id ? get_the_title($base_crossword_id) : $post->post_title);
						$ret .=  '<a href="' . esc_url($base_crossword_url) . '">' . esc_html($display_title) . '</a>';
					$ret .=  '</div>';
					
					// מידע נוסף
					$ret .=  '<div class="crossword_card_meta">';
						if ($base_crossword_size) {
							$ret .=  '<span class="crossword_meta_item"><span class="meta_label">גודל:</span> ' . esc_html($base_crossword_size) . '</span>';
						}
						$ret .=  '<span class="crossword_meta_item"><span class="meta_label">נשמר ב:</span> ' . esc_html($saved_date) . ' בשעה ' . esc_html($saved_time) . '</span>';
					$ret .=  '</div>';
				$ret .=  '</div>';
				
				// סטטוס וכפתור פעולה - באותה שורה
				$ret .=  '<div class="crossword_card_footer">';
					// סטטוס
					if ($status_indicator) {
						$ret .= '<div class="crossword_card_status">' . $status_indicator . '</div>';
					}
					// כפתור פעולה
					$ret .=  '<div class="crossword_card_action">';
						$ret .=  '<a href="' . esc_url($base_crossword_url) . '" class="crossword_view_btn">המשך פתרון</a>';
					$ret .=  '</div>';
				$ret .=  '</div>';
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
	
	echo '<!-- ========== USER CROSSWORDS DEBUG END ========== -->';



echo '</div>';
echo '</div>';

get_footer();

?>
</article>


