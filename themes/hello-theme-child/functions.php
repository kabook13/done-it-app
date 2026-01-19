<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

    // Enqueue the crossword JS file
    wp_enqueue_script(
        'crossword-scripts',
        get_stylesheet_directory_uri() . '/js/crossword.js',
        ['jquery'],
        HELLO_ELEMENTOR_CHILD_VERSION,
        true
    );
	

    // Localize the script to provide AJAX URL and query_vars if needed.
    wp_localize_script(
        'crossword-scripts',
        'ajaxpagination',
        array(
            'ajaxurl'    => admin_url( 'admin-ajax.php' ),
        )
    );
    
    // Localize login/register URLs for modal
    $login_page_id = function_exists('wpum_get_core_page_id') ? wpum_get_core_page_id('login') : 0;
    $register_page_id = function_exists('wpum_get_core_page_id') ? wpum_get_core_page_id('register') : 0;
    
    wp_localize_script(
        'crossword-scripts',
        'wpum_login_url',
        $login_page_id ? get_permalink($login_page_id) : wp_login_url()
    );
    
    wp_localize_script(
        'crossword-scripts',
        'wpum_register_url',
        $register_page_id ? get_permalink($register_page_id) : wp_registration_url()
    );
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );
add_action( 'wp_ajax_tpuzzle_check', 'tpuzzle_check' );
add_action( 'wp_ajax_nopriv_tpuzzle_check', 'tpuzzle_check' );

/**
 * Hide WordPress admin bar for non-admin users
 * Only administrators should see the admin bar
 * NOTE: This does NOT affect is_user_logged_in() - it only hides the visual admin bar
 */
add_filter('show_admin_bar', function($show) {
    // Only show admin bar to administrators
    if (current_user_can('administrator')) {
        return $show;
    }
    // Hide for all other users (subscribers, members, etc.)
    return false;
}, 99);

/**
 * Redirect users to /user/ after login (instead of PMPro account page)
 */
add_filter('login_redirect', function($redirect_to, $request, $user) {
    // If user is logged in and no specific redirect was requested
    if (!empty($user) && !empty($user->ID)) {
        // If redirect_to is empty or points to PMPro account page, redirect to /user/
        if (empty($redirect_to) || 
            $redirect_to == pmpro_url('account') || 
            strpos($redirect_to, '/membership-account') !== false) {
            // Redirect to /user/ page (the account dashboard)
            $user_page = get_page_by_path('user');
            if ($user_page) {
                return get_permalink($user_page->ID);
            }
            // Fallback to /user/ URL
            return home_url('/user/');
        }
    }
    return $redirect_to;
}, 10, 3);

/**
 * Also handle WPUM login redirect
 */
add_filter('wpum_redirect_after_login', function($redirect, $user) {
    // If no specific redirect was requested, redirect to /user/
    if (empty($redirect) || $redirect == get_permalink(wpum_get_core_page_id('login'))) {
        $user_page = get_page_by_path('user');
        if ($user_page) {
            return get_permalink($user_page->ID);
        }
        return home_url('/user/');
    }
    return $redirect;
}, 10, 2);

/**
 * Override PMPro login redirect to go to /user/ instead of PMPro account page
 */
add_filter('pmpro_login_redirect_url', function($redirect_to, $request, $user) {
    // If no specific redirect was requested, or redirect points to PMPro account page, redirect to /user/
    if (empty($redirect_to) || 
        $redirect_to == pmpro_url('account') || 
        strpos($redirect_to, '/membership-account') !== false) {
        $user_page = get_page_by_path('user');
        if ($user_page) {
            return get_permalink($user_page->ID);
        }
        return home_url('/user/');
    }
    return $redirect_to;
}, 10, 3);

/**
 * Prevent PMPro from redirecting specific pages before PMPro's own redirect runs
 * This must run BEFORE PMPro's template_redirect (priority 0)
 */
add_action('template_redirect', function() {
    // Only process if this is a page
    if (!is_page()) {
        return;
    }
    
    global $post;
    if (!$post) {
        return;
    }
    
    // Check if this is a page that should be accessible
    $page_template = get_page_template_slug($post->ID);
    $page_slug = get_post_field('post_name', $post->ID);
    
    // Check if this is "התשבצים שלי" page
    $is_user_crosswords = false;
    if ($page_template === 'template-user_crossword.php') {
        $is_user_crosswords = true;
    } elseif (in_array($page_slug, ['user_crosswords_page', 'התשבצים-שלי'])) {
        $is_user_crosswords = true;
    }
    
    // Check if this is "שיעורים" page
    $is_lessons = ($page_slug === 'שיעורים' || $page_slug === 'lessons');
    
    // If this is one of our special pages and user is logged in, allow access
    if ($is_user_crosswords && is_user_logged_in()) {
        // Allow access - don't let PMPro redirect
        return;
    }
    
    // If this is lessons page and user has membership, allow access
    if ($is_lessons && is_user_logged_in() && function_exists('pmpro_hasMembershipLevel') && pmpro_hasMembershipLevel()) {
        // Allow access - don't let PMPro redirect
        return;
    }
}, 0); // Priority 0 = runs BEFORE PMPro's template_redirect

/**
 * Redirect from PMPro account page to /user/ page
 * This ensures users always see our custom account dashboard instead of PMPro's default
 */
add_action('template_redirect', function() {
    // Don't redirect if already on /user/ page
    $user_page = get_page_by_path('user');
    if (!$user_page) {
        return;
    }
    
    $user_url = untrailingslashit(str_replace(home_url(), '', get_permalink($user_page->ID)));
    $current_url = untrailingslashit(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    
    // If already on /user/ page, don't redirect
    if ($current_url === $user_url || $current_url === '/user') {
        return;
    }
    
    // Check if we're on the PMPro account page
    $is_pmpro_account = false;
    
    // Method 1: Check exact URL match for /membership-account/
    if ($current_url === '/membership-account' || $current_url === '/membership-account/') {
        $is_pmpro_account = true;
    }
    
    // Method 2: Check if URL contains 'membership-account' (common PMPro account page slug)
    // BUT: Don't redirect if we're on /membership-account/your-profile/ (allow profile editing)
    if ((strpos($current_url, '/membership-account') !== false || strpos($current_url, 'membership-account') !== false) &&
        strpos($current_url, '/membership-account/your-profile') === false) {
        $is_pmpro_account = true;
    }
    
    // Method 3: Check using PMPro function (only if user is logged in)
    if (is_user_logged_in() && function_exists('pmpro_is_account_page') && pmpro_is_account_page()) {
        $is_pmpro_account = true;
    }
    
    // Method 4: Check if we're on the PMPro account page by URL
    if (function_exists('pmpro_url')) {
        $pmpro_account_url = untrailingslashit(str_replace(home_url(), '', pmpro_url('account')));
        if ($current_url === $pmpro_account_url || strpos($current_url, $pmpro_account_url) === 0) {
            $is_pmpro_account = true;
        }
    }
    
    // Method 5: Check if we're on a page that contains [pmpro_account] shortcode
    if (is_page()) {
        global $post;
        if ($post && has_shortcode($post->post_content, 'pmpro_account')) {
            $is_pmpro_account = true;
        }
    }
    
    // Redirect if we're on PMPro account page
    if ($is_pmpro_account) {
        // If user is logged in, redirect to /user/
        if (is_user_logged_in()) {
            wp_safe_redirect(get_permalink($user_page->ID));
            exit;
        }
        // If user is not logged in, redirect to login page with redirect_to parameter
        else {
            $login_url = wp_login_url(get_permalink($user_page->ID));
            wp_safe_redirect($login_url);
            exit;
        }
    }
}, 1);

/**
 * Allow access to specific pages for logged-in users with membership
 * This ensures PMPro doesn't block access to pages that should be accessible
 * 
 * IMPORTANT: This filter ONLY affects specific pages:
 * - "התשבצים שלי" - all logged-in users
 * - "שיעורים" - logged-in users with membership
 * 
 * It does NOT affect:
 * - Archive page (תשבצים) - requires membership via PMPro settings
 * - Weekly crossword page (תשבץ-שבועי) - requires membership via PMPro settings
 * - Individual crossword posts - requires membership via PMPro settings
 */
add_filter('pmpro_has_membership_access_filter', function($hasaccess, $post, $user, $levels) {
    // Only process pages (not posts, archives, or other post types)
    if (!is_page()) {
        return $hasaccess; // Let PMPro handle non-page content normally
    }
    
    // Only allow access if user is logged in
    if (!is_user_logged_in()) {
        return $hasaccess; // Let PMPro handle non-logged-in users normally
    }
    
    // Check if this is the "התשבצים שלי" page by template
    $page_template = get_page_template_slug($post->ID);
    if ($page_template === 'template-user_crossword.php') {
        return true; // Allow access for all logged-in users (only this page)
    }
    
    // Check if this is the "התשבצים שלי" page by slug
    $page_slug = get_post_field('post_name', $post->ID);
    if (in_array($page_slug, ['user_crosswords_page', 'התשבצים-שלי'])) {
        return true; // Allow access for all logged-in users (only this page)
    }
    
    // Check if this is the "שיעורים" page - allow access if user has membership
    if ($page_slug === 'שיעורים' || $page_slug === 'lessons') {
        // Check if user has any membership level
        if (function_exists('pmpro_hasMembershipLevel') && pmpro_hasMembershipLevel()) {
            return true; // Allow access for logged-in users with membership
        }
        // If no membership, let PMPro handle it normally (will redirect to subscription page)
        return $hasaccess;
    }
    
    // For all other pages (including "תשבץ-שבועי", "תשבצים", etc.):
    // Return $hasaccess unchanged - let PMPro handle membership restrictions normally
    // This ensures only members can access archive and weekly crossword pages
    return $hasaccess;
}, 10, 4);

/**
 * Disable "עמודי משתמש" Elementor template to prevent conflicts
 * This template might cause Display Conditions issues
 */
add_filter('elementor/theme/get_template_ids', function($template_ids, $template_type) {
    // If this is a single page template, check if it's the problematic "עמודי משתמש" template
    if ($template_type === 'single') {
        // Find the template by title
        $user_pages_template = get_posts(array(
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'title' => 'עמודי משתמש',
            'meta_query' => array(
                array(
                    'key' => '_elementor_template_type',
                    'value' => 'single',
                ),
            ),
        ));
        
        if (!empty($user_pages_template)) {
            $template_id = $user_pages_template[0]->ID;
            // Remove this template from the list
            if (isset($template_ids[$template_id])) {
                unset($template_ids[$template_id]);
            }
        }
    }
    
    return $template_ids;
}, 10, 2);

/**
 * Alternative: Prevent Elementor from applying "עמודי משתמש" template
 */
add_filter('elementor/theme/get_template_id', function($template_id, $template_type) {
    if ($template_type === 'single' && $template_id) {
        // Check if this is the "עמודי משתמש" template
        $template_post = get_post($template_id);
        if ($template_post && $template_post->post_title === 'עמודי משתמש') {
            // Return null to prevent this template from being applied
            return null;
        }
    }
    return $template_id;
}, 10, 2);

/**
 * Function to delete "עמודי משתמש" template programmatically
 * Run this once via admin or WP-CLI to delete the template
 */
function delete_user_pages_template() {
    if (!current_user_can('delete_posts')) {
        return false;
    }
    
    $user_pages_template = get_posts(array(
        'post_type' => 'elementor_library',
        'post_status' => 'any', // Include draft, trash, etc.
        'posts_per_page' => 1,
        'title' => 'עמודי משתמש',
        'meta_query' => array(
            array(
                'key' => '_elementor_template_type',
                'value' => 'single',
            ),
        ),
    ));
    
    if (!empty($user_pages_template)) {
        $template_id = $user_pages_template[0]->ID;
        // Delete permanently (skip trash)
        $result = wp_delete_post($template_id, true);
        return $result !== false;
    }
    
    return false;
}

// Uncomment the line below to delete the template (run once, then comment again)
// add_action('admin_init', function() { if (isset($_GET['delete_user_pages_template']) && current_user_can('delete_posts')) { delete_user_pages_template(); wp_redirect(admin_url('edit.php?post_type=elementor_library&deleted=1')); exit; } });

function my_theme_pmpro_init_styles() {
	if ( defined( 'PMPRO_VERSION' ) ) {
		wp_enqueue_style( 'paid-memberships-pro-customizations', get_stylesheet_directory_uri() . '/paid-memberships-pro.css', array(), '1.0' );
	}
}
add_action( 'wp_enqueue_scripts', 'my_theme_pmpro_init_styles' );


if ( function_exists('acf_add_local_field_group') ):

	acf_add_local_field_group(array(
		'key' => 'group_63e5f1a919b11',
		'title' => 'crossword',
		'fields' => array(
			array(
				'key' => 'field_63e741a0d44d3',
				'label' => 'רוחב',
				'name' => 'size_x',
				'aria-label' => '',
				'type' => 'number',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => 7,
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'min' => 1,
				'max' => 30,
				'step' => '',
			),
			array(
				'key' => 'field_63e741e1d44d5',
				'label' => 'גובה',
				'name' => 'size_y',
				'aria-label' => '',
				'type' => 'number',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => 7,
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'min' => 1,
				'max' => 30,
				'step' => '',
			),
			array(
				'key' => 'field_63e741d9d44d4',
				'label' => 'הגדרות',
				'name' => 'line',
				'aria-label' => '',
				'type' => 'repeater',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => '',
				'min' => 0,
				'max' => 0,
				'layout' => 'table',
				'button_label' => 'Add Row',
				'sub_fields' => array(
					array(
						'key' => 'field_63e74237d44d6',
						'label' => 'מספר',
						'name' => 'number',
						'aria-label' => '',
						'type' => 'number',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => 1,
						'max' => 100,
						'step' => 1,
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_63e7426ed44d8',
						'label' => 'מיקום שורה',
						'name' => 'place_x',
						'aria-label' => '',
						'type' => 'number',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => 1,
						'max' => 30,
						'step' => 1,
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_63e7426dd44d7',
						'label' => 'מיקום עמודה',
						'name' => 'place_y',
						'aria-label' => '',
						'type' => 'number',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => 1,
						'max' => 100,
						'step' => 1,
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_63e742ecd44da',
						'label' => 'מאוזן או מאונך',
						'name' => 'vertical_or_horizontal',
						'aria-label' => '',
						'type' => 'radio',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'choices' => array(
							1 => 'מאוזן',
							2 => 'מאונך',
						),
						'allow_null' => 0,
						'other_choice' => 0,
						'default_value' => 'מאוזן',
						'layout' => 'horizontal',
						'return_format' => 'value',
						'save_other_choice' => 0,
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_63e742bed44d9',
						'label' => 'הגדרה',
						'name' => 'definition',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_63e74446d30bb',
						'label' => 'פתרון',
						'name' => 'solution',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_67efb8837eda0',
						'label' => 'הסבר פתרון',
						'name' => 'solution_exp',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_63efadf5dbeec',
						'label' => 'אורך מילה ראשונה',
						'name' => 'first_word_length',
						'aria-label' => '',
						'type' => 'number',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => 1,
						'max' => 50,
						'step' => '',
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_63efae52dbeed',
						'label' => 'אורך מילה שנייה',
						'name' => 'second_word_length',
						'aria-label' => '',
						'type' => 'number',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
						'parent_repeater' => 'field_63e741d9d44d4',
					),
					array(
						'key' => 'field_63efae69dbeee',
						'label' => 'אורך מילה שלישית',
						'name' => 'third_word_length',
						'aria-label' => '',
						'type' => 'number',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
						'parent_repeater' => 'field_63e741d9d44d4',
					),
				),
				'rows_per_page' => 20,
			),
			array(
				'key' => 'field_63e755ac9275c',
				'label' => 'JSON',
				'name' => 'json',
				'aria-label' => '',
				'type' => 'textarea',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
				'maxlength' => '',
				'rows' => '',
				'new_lines' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'crossword',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
		'show_in_rest' => 0,
	));
	
	endif;
	
		
	if ( function_exists('acf_add_local_field_group') ):
	
	acf_add_local_field_group(array(
		'key' => 'group_642eda0047bab',
		'title' => 'User Crossword',
		'fields' => array(
			array(
				'key' => 'field_642eda143c316',
				'label' => 'User',
				'name' => 'user',
				'aria-label' => '',
				'type' => 'user',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'role' => '',
				'allow_null' => 1,
				'multiple' => 0,
				'return_format' => 'id',
			),
			array(
				'key' => 'field_642eda4c3c317',
				'label' => 'Crossword',
				'name' => 'crossword',
				'aria-label' => '',
				'type' => 'post_object',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'post_type' => array(
					0 => 'crossword',
				),
				'taxonomy' => '',
				'allow_null' => 1,
				'multiple' => 0,
				'return_format' => 'id',
				'ui' => 1,
			),
			array(
				'key' => 'field_642eda713c318',
				'label' => 'JSON',
				'name' => 'json',
				'aria-label' => '',
				'type' => 'textarea',
				'instructions' => '',
				'required' => 1,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
				'maxlength' => '',
				'rows' => '',
				'new_lines' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'user_crossword',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => array(
			0 => 'permalink',
			1 => 'the_content',
			2 => 'excerpt',
			3 => 'discussion',
			4 => 'comments',
			5 => 'revisions',
			6 => 'author',
			7 => 'format',
			8 => 'page_attributes',
			9 => 'featured_image',
			10 => 'categories',
			11 => 'tags',
			12 => 'send-trackbacks',
		),
		'active' => true,
		'description' => '',
		'show_in_rest' => false,
	));
	
	endif;
	
	

function cptui_register_my_cpts_crossword() {

	/**
	 * Post Type: Crosswords.
	 */

	$labels = [
		"name" => esc_html__( "Crosswords", "hello-elementor-child" ),
		"singular_name" => esc_html__( "Crossword", "hello-elementor-child" ),
	];

	$args = [
		"label" => esc_html__( "Crosswords", "hello-elementor-child" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => false,
		"rewrite" => [ "slug" => "crossword", "with_front" => true ],
		"query_var" => true,
		"supports" => [ "title", "editor", "thumbnail", "author" ],
		"taxonomies" => [ "post_tag" ],
		"show_in_graphql" => false,
	];

	register_post_type( "crossword", $args );
}

add_action( 'init', 'cptui_register_my_cpts_crossword' );

function cptui_register_my_cpts_user_crossword() {

	/**
	 * Post Type: User Crosswords.
	 */

	$labels = [
		"name" => esc_html__( "User Crosswords", "hello-elementor-child" ),
		"singular_name" => esc_html__( "User Crossword", "hello-elementor-child" ),
	];

	$args = [
		"label" => esc_html__( "User Crosswords", "hello-elementor-child" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => false,
		"rewrite" => [ "slug" => "user_crossword", "with_front" => true ],
		"query_var" => true,
		"supports" => [ "title", "editor", "thumbnail" ],
		"show_in_graphql" => false,
	];

	register_post_type( "user_crossword", $args );
}

add_action( 'init', 'cptui_register_my_cpts_user_crossword' );

if ( ! defined( 'DEFAULT_CROSSWORD_IMAGE_ID' ) ) {
    define( 'DEFAULT_CROSSWORD_IMAGE_ID', 1459 ); // Replace 1234 with your default image's attachment ID.
}
function set_default_featured_image( $post_id ) {
    // Only run for posts of type 'crossword' or 'user_crossword'
    $post_type = get_post_type( $post_id );
    if ( ! in_array( $post_type, array( 'crossword', 'user_crossword' ) ) ) {
        return;
    }
    
    // Avoid autosaves and revisions.
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    
    // If there is already a featured image, do nothing.
    if ( has_post_thumbnail( $post_id ) ) {
        return;
    }
    
    // Set the default featured image if defined.
    if ( defined( 'DEFAULT_CROSSWORD_IMAGE_ID' ) ) {
        set_post_thumbnail( $post_id, DEFAULT_CROSSWORD_IMAGE_ID );
    }
}
add_action( 'save_post', 'set_default_featured_image' );


function tpuzzle_check() {	
    echo puzzle_check();    
    die();
}

function decode_solution_letter($raw) {
    // אם האות כבר היא תו רגיל (לא Unicode escape), החזר אותה
    if (mb_strlen($raw, 'UTF-8') === 1) {
        return $raw;
    }
    
    // אם האות מתחילה ב-u (ללא backslash), הוסף backslash
    if (preg_match('/^u[0-9a-f]{4}$/i', $raw)) {
        $raw = '\\' . $raw;
    }
    
    // אם האות מתחילה ב-\u, השתמש ב-json_decode
    if (strpos($raw, '\\u') === 0 || strpos($raw, '\u') === 0) {
        $decoded = json_decode('"' . $raw . '"');
        if ($decoded !== null) {
            return $decoded;
        }
    }
    
    // אם כלום לא עבד, החזר את המקורי
    return $raw;
}

function puzzle_check(){
    // קבלת הנתונים - לא להשתמש ב-sanitize_text_field על $the_form כי זה עלול להרוס תווים עבריים
    $the_form = isset($_POST['the_form']) ? $_POST['the_form'] : '';
    $width = isset($_POST['size_x']) ? absint($_POST['size_x']) : 1;
    $length = isset($_POST['size_y']) ? absint($_POST['size_y']) : 1;
    $the_post_id = isset($_POST['the_post_id']) ? absint($_POST['the_post_id']) : 0;

    if(empty($the_form) || !$the_post_id){
        return 'תקלה: נתונים חסרים';
    }

    // Verify the post exists and is the correct type
    $post_type = get_post_type($the_post_id);
    if(!in_array($post_type, ['crossword', 'user_crossword'])) {
        return 'תקלה: סוג תשבץ לא חוקי';
    }
    
    // Retrieve dimensions and post id from POST.
    $the_post_id = isset($_POST['the_post_id']) ? $_POST['the_post_id'] : 1;
	
    // Determine the base crossword post ID.
    $post_type = get_post_type($the_post_id);
    if($post_type === 'user_crossword'){
        $base_id = get_field('crossword', $the_post_id);
    } else {
        $base_id = $the_post_id;
    }
    
    // Retrieve the solution JSON from the base crossword.
    $solution_field = get_field('field_63e755ac9275c', $base_id);
    if(empty($solution_field)){
        return 'תקלה';
    }
    $solution_json = json_decode($solution_field, true);
    if(!is_array($solution_json)){
        return 'תקלה';
    }
    
    // Decode the submitted form input.
    // $the_form כבר מגיע כ-URL encoded string מ-$.param()
    // נסה תחילה עם unicode_urldecode (פורמט %uXXXX)
    // אם זה לא עובד, נסה עם urldecode רגיל (UTF-8)
    $query = unicode_urldecode($the_form);
    // אם unicode_urldecode לא עשה כלום (אין %uXXXX), נסה urldecode רגיל
    if($query === $the_form && strpos($the_form, '%u') === false){
        $query = urldecode($the_form);
    }
    $chunks = explode('&', $query);
    
    // אוסף של כל התאים שהמשתמש מילא
    $filled_cells = array();
    
    foreach($chunks as $chunk){
        $param = explode("=", $chunk, 2); // limit to 2 parts in case value contains =
        if(count($param) < 2){
            continue;
        }
        // decode את שם הפרמטר
        $param_name = urldecode($param[0]);
        $cel_index = str_replace('cel_letter_', '', $param_name);
        $cel_index = intval($cel_index); // ודא שזה מספר
        
        if($cel_index <= 0){
            continue; // skip אם זה לא מספר תקין
        }
        
        // קבלת האות מהמשתמש
        $user_letter_raw = urldecode($param[1]);
        // אם עדיין יש encoding, נסה decode נוסף (למקרה של double encoding)
        if(strpos($user_letter_raw, '%') !== false){
            $user_letter_raw = urldecode($user_letter_raw);
        }
        // ניקוי והמרה לאות קטנה
        $user_letter = mb_strtolower(trim($user_letter_raw), 'UTF-8');
        
        // אם המשתמש מילא את התא (לא ריק), שמור אותו לבדיקה
        if($user_letter !== '' && mb_strlen($user_letter, 'UTF-8') > 0){
            $filled_cells[$cel_index] = $user_letter;
        }
    }
    
    // אם המשתמש לא מילא כלום, זה לא תקין
    if(empty($filled_cells)){
        return 'תקלה: לא הוזנו תשובות';
    }
    
    // בדוק כל תא שהמשתמש מילא
    foreach($filled_cells as $cel_index => $user_letter){
        // ודא שהתא קיים בפתרון
        if(!isset($solution_json[$cel_index]['letter'])){
            continue; // תא לא קיים בפתרון - דלג
        }
        
        $raw_letter = $solution_json[$cel_index]['letter'];
        
        // המרת האות מהפתרון
        $solution_letter_decoded = decode_solution_letter($raw_letter);
        $solution_letter = mb_strtolower(trim($solution_letter_decoded), 'UTF-8');
        
        // בדוק אם האות שהמשתמש הזין תואמת לפתרון
        if($user_letter !== $solution_letter){
            // תיקון חישוב מיקום - ה-index מתחיל מ-1, לא מ-0
            $zero_based_index = $cel_index - 1;
            $line = floor($zero_based_index / $width) + 1;
            $place = ($zero_based_index % $width) + 1; // +1 כי המיקום מתחיל מ-1
            
            return " יש שגיאה בשורה " . $line . " במיקום " . $place;
        }
    }
    
    // אם הגענו עד כאן, כל התאים שהמשתמש מילא תואמים לפתרון
    return "זהה!!!";
}
add_action( 'wp_ajax_tpuzzle_save', 'tpuzzle_save' );
add_action( 'wp_ajax_nopriv_tpuzzle_save', 'tpuzzle_save' );

function tpuzzle_save() {	
    echo puzzle_save();    
    die();
}

if ( ! defined( 'DEFAULT_CROSSWORD_IMAGE_ID' ) ) {
    define( 'DEFAULT_CROSSWORD_IMAGE_ID', 1234 ); // Replace 1234 with your default image attachment ID.
}

function puzzle_save(){
    // בדיקה אם המשתמש מחובר
    if (!is_user_logged_in()) {
        return 'יש להתחבר כדי לשמור התקדמות';
    }
    
    // Retrieve required values (assumes they are sent via POST)
    $the_form    = isset($_POST['the_form']) ? $_POST['the_form'] : '';
    $size_x      = isset($_POST['size_x']) ? $_POST['size_x'] : '';
    $size_y      = isset($_POST['size_y']) ? $_POST['size_y'] : '';
    $the_post_id = isset($_POST['the_post_id']) ? $_POST['the_post_id'] : '';

    if( empty($the_form) || empty($the_post_id) ){
        return 'תקלה: נתונים חסרים';
    }
    
    // Build the JSON array from the serialized form.
    $json_array = array();
    foreach (explode('&', $the_form) as $chunk) {
        $param = explode("=", $chunk);
        if (count($param) < 2) continue;
        $i = str_replace('cel_letter_', '', urldecode($param[0]));
        $json_array[$i]['letter'] = urldecode($param[1]);
    }
    
    // Encode the JSON for saving
    $json_data = json_encode($json_array);
    $user_id   = get_current_user_id();
    
    // בדיקה נוספת - אם אין user_id, משהו לא בסדר
    if (!$user_id) {
        return 'שגיאה: לא ניתן לזהות משתמש';
    }
    
    // Check if the provided post ID is already a user_crossword post.
    if( get_post_type($the_post_id) === 'user_crossword' ){
        // We are editing an existing user_crossword: update it directly.
        update_field('field_642eda713c318', $json_data, $the_post_id);
        return "עודכן בהצלחה!";
    } else {
        // We are working on a base crossword, so check for an existing user_crossword post.
        $query_args = array(
            'posts_per_page' => 1,
            'post_type'      => 'user_crossword',
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'user',
                    'value'   => $user_id,
                    'compare' => '='
                ),
                array(
                    'key'     => 'crossword',
                    'value'   => $the_post_id,
                    'compare' => '='
                )
            )
        );
        $query = new WP_Query( $query_args );
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $existing_id = get_the_ID();
                update_field('field_642eda713c318', $json_data, $existing_id);
            }
            wp_reset_postdata();
            return "עודכן בהצלחה!";
        } else {
            // No existing user_crossword found; create a new one.
            $post_data = array(
                'post_title'  => get_the_title($the_post_id),
                'post_status' => 'publish',
                'post_author' => $user_id,
                'post_type'   => 'user_crossword'
            );
            $new_id = wp_insert_post( $post_data );
            // Update ACF fields:
            update_field('field_642eda143c316', $user_id, $new_id);    // User field
            update_field('field_642eda4c3c317', $the_post_id, $new_id);   // Crossword (base post ID) field
            update_field('field_642eda713c318', $json_data, $new_id);      // JSON (user progress) field
            
            // Set default featured image for user_crosswords.
            if( ! has_post_thumbnail( $new_id ) ) {
                set_post_thumbnail( $new_id, DEFAULT_CROSSWORD_IMAGE_ID );
            }
            
            return "נשמר בהצלחה!";
        }
    }
}

function puzzle_show_explanation($post_id){
	$explanation = get_field('explanation', $post_id);
	if(empty($explanation)){
		return 'אין הסבר';
	}
	return $explanation;
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
    // Only run for posts of type 'crossword'
    if ( get_post_type($post_id) != 'crossword' ){
        return;
    }
    
    // Avoid autosaves and revisions.
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    
    $json_array = array();
    // Retrieve grid dimensions.
    $width = get_field( 'size_x', $post_id ); 
    $length = get_field( 'size_y', $post_id );
    $total_obj = $width * $length;
    
    // Initialize every cell to "X"
    for ($i = 1; $i <= $total_obj; $i++) {
        $json_array[$i]['letter'] = 'X';
    }
    
    // Process each definition row (ACF repeater field "line")
    while ( have_rows('line', $post_id) ) : the_row();
        $number = get_sub_field('number');
        // Calculate starting index (convert to zero-based and then add 1)
        $start_col = get_sub_field('place_y') - 1;            
        $start_row = get_sub_field('place_x') - 1;
        $start_point = ($start_row * $width) + $start_col + 1;
    
        // Retrieve the solution string, and remove any spaces.
        $solution_raw = get_sub_field('solution');
        $solution_clean = str_replace(' ', '', $solution_raw);
        
        // Split the cleaned solution into an array of Unicode characters.
        preg_match_all('/./u', $solution_clean, $current_solution);
        $current_solution = $current_solution[0];
        $current_solution_len = count($current_solution);
    
        // Mark the starting cell with the clue number and length.
        $json_array[$start_point]['number'] = $number;
        $json_array[$start_point]['current_len'] = $current_solution_len;
    
        // Get marked borders indexes (using your helper function).
        $marked_borders_indexes = get_marked_borders_indexes();
    
        // Depending on the orientation, fill in the solution letters.
        if ( 1 == get_sub_field('vertical_or_horizontal') ) {
            // Horizontal: iterate across the row.
            for ($j = 0; $j < $current_solution_len; $j++) {
                $current_json_index = $start_point + $j;
                $json_array[$current_json_index]['letter'] = $current_solution[$j];
                if ( in_array($j, $marked_borders_indexes) ){
                    if ($j != ($width - $start_col) - 1){
                        $json_array[$current_json_index]['mark_border'] = 'left';
                    }
                }
            }
        } else {
            // Vertical: iterate down the column.
            for ($k = 0; $k < $current_solution_len; $k++) {
                $current_json_index = $start_point + ($width * $k);
                $json_array[$current_json_index]['letter'] = $current_solution[$k];
                if ( in_array($k, $marked_borders_indexes) ){
                    if ($k != ($length - $start_row) - 1){
                        $json_array[$current_json_index]['mark_border'] = 'bottom';
                    }
                }
            }
        }
        
    endwhile; 

    return update_field( 'field_63e755ac9275c', json_encode($json_array), $post_id);
}


add_action( 'elementor/query/user_crosswords_current_user', function( $query ) {
    $user_id = get_current_user_id();
    if (!$user_id) return;

    $meta_query = array(
        array(
            'key'     => 'user',
            'value'   => $user_id,
            'compare' => '='
        )
    );
    $query->set('meta_query', $meta_query);
});

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

// Register AJAX actions for logged-in and non-logged-in users to fetch solution explanations
add_action('wp_ajax_show_solution_explanations', 'show_solution_explanations');
add_action('wp_ajax_nopriv_show_solution_explanations', 'show_solution_explanations');

/**
 * AJAX handler to fetch solution explanations for a crossword.
 *
 * This function retrieves the 'solution_exp' field for each definition in the crossword
 * and returns it as a JSON response. It is triggered via an AJAX request.
 */
function show_solution_explanations() {
    // Get the post ID from the AJAX request
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Validate the post ID
    if (!$post_id) {
        wp_send_json_error('Invalid post ID'); // Return an error response if the post ID is invalid
    }

    $explanations = array(); // Initialize an array to store explanations

    // Loop through the 'line' repeater field to fetch definitions and explanations
    if (have_rows('line', $post_id)) {
        while (have_rows('line', $post_id)) {
            the_row();
            $definition = get_sub_field('definition'); // Fetch the definition
            $solution_exp = get_sub_field('solution_exp'); // Fetch the solution explanation

            // Add the definition and explanation to the array
            $explanations[] = array(
                'definition' => $definition,
                'solution_exp' => $solution_exp
            );
        }
    }

    // Return the explanations as a JSON success response
    wp_send_json_success($explanations);
}

// ==========================================================
// PMPRO Fixes, Translations & Conditional Shortcode (שחזור מלא)
// ==========================================================

/**
 * החלפת פונקציית טעינת ה-CSS הקיימת כדי למנוע מטמון
 */
function pmpro_force_css_update() {
    if ( defined( 'PMPRO_VERSION' ) ) {
        // השתמש ב-time() כגרסה כדי לוודא מספר גרסה חדש בכל רענון
        $version = time(); 
        wp_enqueue_style( 
            'paid-memberships-pro-customizations', 
            get_stylesheet_directory_uri() . '/paid-memberships-pro.css', 
            array(), 
            $version 
        );
    }
}
remove_action( 'wp_enqueue_scripts', 'my_theme_pmpro_init_styles' ); 
add_action( 'wp_enqueue_scripts', 'pmpro_force_css_update', 100 );


/**
 * תרגום טקסטים קשים ב-PMPro (Levels, Checkout, והודעות)
 * שופר עם יותר תרגומים ותיקון בעיות עדכון
 */
function my_pmpro_text_translations($translated_text, $text, $domain) {
    if ($domain === 'paid-memberships-pro' || $domain === 'default') {
        // תרגומים ספציפיים - בדיקה מדויקת
        $translations = array(
            // == תיקוני Checkout והודעות ==
            'Membership Information' => 'פרטי מנוי',
            'The price for membership is %s.' => 'התשלום עבור המנוי הוא %s.',
            'The price for membership is <strong>%s</strong> now' => 'המחיר עבור המנוי הוא <strong>%s</strong> עכשיו',
            'The price for an account is %s now.' => 'המחיר עבור המנוי הוא %s עכשיו',
            'The price for an account is %s per %s.' => 'המחיר עבור המנוי הוא %s לכל %s',
            'and then %s per month for %d more months.' => '',
            'and then %s per year for %d more years.' => '',
            'You have selected the %s membership level. Your current membership level will be removed.' => 'בחרת במנוי %s. המנוי הקיים שלך יוסר.',
            'I agree to the' => 'קראתי ואני מאשר את',
            'per' => 'ל-',
            'Only change the fields you want to update.' => 'עדכן רק את השדות שברצונך לשנות.',
            'Your membership level is currently active.' => 'מנוי זה פעיל כרגע.',
            'United States' => 'בחר מדינה',
            'will be removed' => 'יוסר',
            'membership level.' => 'רמת מנוי.',
            
            // == תיקוני Levels (בחירת מנוי) ==
            'Level' => 'מנוי', // שונה מ"סוג המנוי" כדי למנוע "סוג המנוי:."
            'Price' => 'מחיר',
            'You may select only one level from this group' => '',
            'Submit and Check Out' => 'שלח והמשך לתשלום',
            'Choose a Membership Level' => 'בחר דרגת מנוי',
            'Check Out' => 'המשך לתשלום',
            'Username' => 'שם משתמש',
            'Password' => 'סיסמה',
            'Email Address' => 'כתובת אימייל',
            
            // == תרגומים נוספים ==
            'Confirm Email' => 'אימות אימייל',
            'Confirm Password' => 'אשר סיסמה',
            'First Name' => 'שם פרטי',
            'Last Name' => 'שם משפחה',
            'Address' => 'כתובת',
            'City' => 'עיר',
            'State' => 'מחוז / איזור',
            'Postal Code' => 'מיקוד',
            'Country' => 'מדינה',
            'Phone' => 'טלפון',
            'Card Number' => 'מספר כרטיס',
            'Expiration Date' => 'תאריך תפוגה',
            'Security Code (CVC)' => 'קוד אבטחה (CVC)',
            'Discount Code' => 'קוד קופון',
            'Apply' => 'החל',
            'Processing...' => 'מעבד...',
            'Please wait' => 'אנא המתן',
            'Submit and Confirm' => 'שלח ואשר', // תרגום לכפתור במנוי חינם
        );
        
        // בדיקה מדויקת של הטקסט
        if (isset($translations[$text])) {
            return $translations[$text];
        }
        
        // בדיקה חלקית לטקסטים עם משתנים
        foreach ($translations as $key => $value) {
            if (strpos($text, $key) !== false && strpos($key, '%') !== false) {
                // אם הטקסט מכיל את המפתח (עם משתנים)
                return $value;
            }
        }
        
        // תיקון טקסטים עם "999 more" - הסתרה
        if (preg_match('/\d+\s+more\s+(months?|years?)/i', $text)) {
            return '';
        }
        
        // תיקון טקסטים עם "per and then" - הסתרה
        if (strpos($text, 'per and then') !== false) {
            return '';
        }
    }
    return $translated_text;
}
add_filter('gettext', 'my_pmpro_text_translations', 999, 3);
add_filter('gettext_with_context', 'my_pmpro_text_translations', 999, 3);
add_filter('ngettext', 'my_pmpro_text_translations', 999, 5);

/**
 * פילטר נוסף לתיקון טקסטים ספציפיים של PMPro
 */
function my_pmpro_fix_cost_text($cost_text, $level) {
    // הסתרת טקסטים עם "999 more"
    $cost_text = preg_replace('/\s*and\s+then\s+[^.]*\s+for\s+\d+\s+more\s+(months?|years?)[^.]*/i', '', $cost_text);
    $cost_text = preg_replace('/\s*per\s+and\s+then[^.]*/i', '', $cost_text);
    
    // הסרת המילה "עכשיו" אחרי מחירים
    $cost_text = preg_replace('/\s+עכשיו\s*/i', '', $cost_text);
    
    // תיקון נקודות מיותרות וטקסטים מוזרים
    $cost_text = preg_replace('/\s*\.\s*\./i', '.', $cost_text); // נקודה כפולה -> נקודה אחת
    // הסרת "סוג מנוי:." / "סוג מנוי." / "סוג מנוי:" מכל המקומות - כל הווריאציות
    // תופס גם עם רווחים, נקודות, נקודתיים, HTML entities וכו'
    // תיקון: תופס גם "סוג המנוי" (עם ה' הידיעה)
    $cost_text = preg_replace('/סוג\s*המנוי\s*[:：]\s*[\.。]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*המנוי\s*[\.。]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*המנוי\s*[:：]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[\.。]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[:：]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[\.。]\s*[\.。]/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*[\.。]/iu', '', $cost_text);
    $cost_text = preg_replace('/מנוי\s*:\s*\./i', 'מנוי.', $cost_text); // "מנוי:." -> "מנוי."
    $cost_text = preg_replace('/מנוי\s*\.\s*\./i', 'מנוי.', $cost_text); // "מנוי.." -> "מנוי."
    
    // תיקון "המחיר עבור חשבון הוא"
    $cost_text = preg_replace('/המחיר עבור חשבון הוא\s*\.\s*עכשיו/i', 'המחיר עבור המנוי הוא', $cost_text);
    $cost_text = preg_replace('/המחיר עבור חשבון הוא/i', 'המחיר עבור המנוי הוא', $cost_text);
    
    // תיקון רווחים מיותרים בין אותיות למילים
    $cost_text = preg_replace('/ב\s+סוג/i', 'בסוג', $cost_text); // "ב סוג" -> "בסוג"
    $cost_text = preg_replace('/ה\s+מחיר/i', 'המחיר', $cost_text); // "ה מחיר" -> "המחיר"
    $cost_text = preg_replace('/ל\s+חודש/i', 'לחודש', $cost_text); // "ל חודש" -> "לחודש"
    $cost_text = preg_replace('/ל\s+שנה/i', 'לשנה', $cost_text); // "ל שנה" -> "לשנה"
    
    return trim($cost_text);
}
add_filter('pmpro_level_cost_text', 'my_pmpro_fix_cost_text', 10, 2);

/**
 * פילטר ישיר על הפלט של pmpro_getLevelCost - תופס את כל הקריאות
 * משתמש ב-output buffering כדי לתפוס את כל הטקסטים
 */
function my_pmpro_fix_all_output($content) {
    if (is_admin()) {
        return $content;
    }
    
    // בדיקה אם זה דף PMPro או מכיל טקסטים של PMPro
    $is_pmpro_page = false;
    if (function_exists('pmpro_is_checkout') && pmpro_is_checkout()) {
        $is_pmpro_page = true;
    }
    if (function_exists('pmpro_is_levels_page') && pmpro_is_levels_page()) {
        $is_pmpro_page = true;
    }
    if (function_exists('pmpro_is_account_page') && pmpro_is_account_page()) {
        $is_pmpro_page = true;
    }
    // גם בדפים שמכילים טקסטים של PMPro
    if (strpos($content, 'pmpro_level') !== false || strpos($content, 'סוג מנוי') !== false || strpos($content, 'המחיר עבור') !== false || strpos($content, 'בחרת ב') !== false) {
        $is_pmpro_page = true;
    }
    
    if (!$is_pmpro_page) {
        return $content;
    }
    
    // הסרת המילה "עכשיו" אחרי מחירים
    $content = preg_replace('/\s+עכשיו\s*/i', '', $content);
    
    // הסרת "סוג מנוי:." / "סוג מנוי." / "סוג מנוי:" מכל המקומות - כל הווריאציות
    // תופס גם עם רווחים, נקודות, נקודתיים, HTML entities וכו'
    // תיקון: תופס גם "סוג המנוי" (עם ה' הידיעה)
    $content = preg_replace('/סוג\s*המנוי\s*[:：]\s*[\.。]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*המנוי\s*[\.。]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*המנוי\s*[:：]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[\.。]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[:：]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[\.。]\s*[\.。]/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*[\.。]/iu', '', $content);
    
    // תיקון נקודה כפולה אחרי "מנוי" - כל וריאציה אפשרית
    $content = preg_replace('/מנוי\s*:\s*\./i', 'מנוי.', $content);
    $content = preg_replace('/מנוי\s*\.\s*\./i', 'מנוי.', $content);
    $content = preg_replace('/מנוי\s*\.\s*\.\s*\./i', 'מנוי.', $content);
    $content = preg_replace('/מנוי\s*:\s*\.\s*\./i', 'מנוי.', $content);
    
    // תיקון "המחיר עבור חשבון הוא"
    $content = preg_replace('/המחיר עבור חשבון הוא\s*\.\s*עכשיו/i', 'המחיר עבור המנוי הוא', $content);
    $content = preg_replace('/המחיר עבור חשבון הוא/i', 'המחיר עבור המנוי הוא', $content);
    
    // תיקון רווחים מיותרים בין אותיות למילים
    $content = preg_replace('/ב\s+סוג/i', 'בסוג', $content);
    $content = preg_replace('/ה\s+מחיר/i', 'המחיר', $content);
    $content = preg_replace('/ל\s+חודש/i', 'לחודש', $content);
    $content = preg_replace('/ל\s+שנה/i', 'לשנה', $content);
    
    return $content;
}
// הוסף פילטרים על כל הפלטים - priority גבוה מאוד כדי לתפוס הכל
add_filter('the_content', 'my_pmpro_fix_all_output', 99999);
add_filter('wp_kses_post', 'my_pmpro_fix_all_output', 99999);
add_filter('pmpro_level_description', 'my_pmpro_fix_all_output', 99999);
add_filter('pmpro_level_cost_text', 'my_pmpro_fix_all_output', 99999);
add_filter('pmpro_level_name_text', 'my_pmpro_fix_all_output', 99999);

// Output buffering - תופס את כל הפלט לפני שהוא מוצג
function my_pmpro_start_output_buffer() {
    if (function_exists('pmpro_is_checkout') && pmpro_is_checkout() || 
        function_exists('pmpro_is_levels_page') && pmpro_is_levels_page()) {
        ob_start('my_pmpro_fix_all_output');
    }
}
function my_pmpro_end_output_buffer() {
    if (function_exists('pmpro_is_checkout') && pmpro_is_checkout() || 
        function_exists('pmpro_is_levels_page') && pmpro_is_levels_page()) {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }
}
add_action('template_redirect', 'my_pmpro_start_output_buffer', 1);
add_action('wp_footer', 'my_pmpro_end_output_buffer', 99999);

/**
 * פילטר נוסף לתיקון טקסטים ב-checkout - עובד על כל הפלטים
 */
function my_pmpro_fix_all_texts($content) {
    if (is_admin()) {
        return $content;
    }
    
    // הסרת המילה "עכשיו" אחרי מחירים
    $content = preg_replace('/\s+עכשיו\s*/i', '', $content);
    
    // הסרת "סוג מנוי:." / "סוג מנוי." / "סוג מנוי:" מכל המקומות - כל הווריאציות
    // תופס גם עם רווחים, נקודות, נקודתיים, HTML entities וכו'
    // תיקון: תופס גם "סוג המנוי" (עם ה' הידיעה)
    $content = preg_replace('/סוג\s*המנוי\s*[:：]\s*[\.。]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*המנוי\s*[\.。]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*המנוי\s*[:：]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[\.。]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[:：]\s*/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[\.。]\s*[\.。]/iu', '', $content);
    $content = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*[\.。]/iu', '', $content);
    
    // תיקון נקודה כפולה אחרי "מנוי" - כל וריאציה אפשרית
    $content = preg_replace('/מנוי\s*:\s*\./i', 'מנוי.', $content); // "מנוי:." -> "מנוי."
    $content = preg_replace('/מנוי\s*\.\s*\./i', 'מנוי.', $content); // "מנוי.." -> "מנוי."
    $content = preg_replace('/מנוי\s*\.\s*\.\s*\./i', 'מנוי.', $content); // "מנוי..." -> "מנוי."
    $content = preg_replace('/מנוי\s*:\s*\.\s*\./i', 'מנוי.', $content); // "מנוי:.." -> "מנוי."
    
    // תיקון "המחיר עבור חשבון הוא .עכשיו"
    $content = preg_replace('/המחיר עבור חשבון הוא\s*\.\s*עכשיו/i', 'המחיר עבור המנוי הוא', $content);
    
    // תיקון "בחרת ב X סוג מנוי:." - הסרת "סוג מנוי:."
    $content = preg_replace('/בחרת ב\s+([^<]*?)\s+סוג מנוי:\s*\./i', 'בחרת במנוי $1.', $content);
    $content = preg_replace('/בחרת ב\s+([^<]*?)\s+סוג מנוי\./i', 'בחרת במנוי $1.', $content);
    $content = preg_replace('/בחרת ב\s+([^<]*?)\s+סוג מנוי:/i', 'בחרת במנוי $1.', $content);
    
    // תיקון "בחרת ב X ." (ללא "סוג מנוי")
    $content = preg_replace('/בחרת ב\s+([^<]*?)\s*\.\s*</i', 'בחרת במנוי $1.<', $content);
    
    // תיקון רווחים מיותרים בין אותיות למילים
    $content = preg_replace('/ב\s+סוג/i', 'בסוג', $content); // "ב סוג" -> "בסוג"
    $content = preg_replace('/ה\s+מחיר/i', 'המחיר', $content); // "ה מחיר" -> "המחיר"
    $content = preg_replace('/ל\s+חודש/i', 'לחודש', $content); // "ל חודש" -> "לחודש"
    $content = preg_replace('/ל\s+שנה/i', 'לשנה', $content); // "ל שנה" -> "לשנה"
    
    return $content;
}
add_filter('pmpro_level_description', 'my_pmpro_fix_all_texts', 999);
add_filter('pmpro_level_cost_text', 'my_pmpro_fix_all_texts', 999);
// הסרת הפילטרים הישנים על the_content ו-wp_kses_post - יש לנו פילטר חדש עם priority גבוה יותר (my_pmpro_fix_all_output)

/**
 * פילטר ספציפי לתיקון טקסטים ב-checkout page
 */
function my_pmpro_fix_checkout_page_text($text) {
    if (is_admin()) {
        return $text;
    }
    
    // הסרת המילה "עכשיו" אחרי מחירים
    $text = preg_replace('/\s+עכשיו\s*/i', '', $text);
    
    // הסרת "סוג מנוי:." / "סוג מנוי." / "סוג מנוי:" מכל המקומות - כל הווריאציות
    // תופס גם עם רווחים, נקודות, נקודתיים, HTML entities וכו'
    // תיקון: תופס גם "סוג המנוי" (עם ה' הידיעה)
    $text = preg_replace('/סוג\s*המנוי\s*[:：]\s*[\.。]\s*/iu', '', $text);
    $text = preg_replace('/סוג\s*המנוי\s*[\.。]\s*/iu', '', $text);
    $text = preg_replace('/סוג\s*המנוי\s*[:：]\s*/iu', '', $text);
    $text = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*/iu', '', $text);
    $text = preg_replace('/סוג\s*מנוי\s*[\.。]\s*/iu', '', $text);
    $text = preg_replace('/סוג\s*מנוי\s*[:：]\s*/iu', '', $text);
    $text = preg_replace('/סוג\s*מנוי\s*[\.。]\s*[\.。]/iu', '', $text);
    $text = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*[\.。]/iu', '', $text);
    
    // תיקון נקודה כפולה אחרי "מנוי" - כל וריאציה אפשרית
    $text = preg_replace('/מנוי\s*:\s*\./i', 'מנוי.', $text); // "מנוי:." -> "מנוי."
    $text = preg_replace('/מנוי\s*\.\s*\./i', 'מנוי.', $text); // "מנוי.." -> "מנוי."
    $text = preg_replace('/מנוי\s*\.\s*\.\s*\./i', 'מנוי.', $text); // "מנוי..." -> "מנוי."
    $text = preg_replace('/מנוי\s*:\s*\.\s*\./i', 'מנוי.', $text); // "מנוי:.." -> "מנוי."
    
    // תיקון "המחיר עבור חשבון הוא .עכשיו"
    $text = preg_replace('/המחיר עבור חשבון הוא\s*\.\s*עכשיו/i', 'המחיר עבור המנוי הוא', $text);
    
    // תיקון "בחרת ב X סוג מנוי:." - הסרת "סוג מנוי:."
    $text = preg_replace('/בחרת ב\s+([^<]*?)\s+סוג מנוי:\s*\./i', 'בחרת במנוי $1.', $text);
    $text = preg_replace('/בחרת ב\s+([^<]*?)\s+סוג מנוי\./i', 'בחרת במנוי $1.', $text);
    $text = preg_replace('/בחרת ב\s+([^<]*?)\s+סוג מנוי:/i', 'בחרת במנוי $1.', $text);
    
    // תיקון רווחים מיותרים בין אותיות למילים
    $text = preg_replace('/ב\s+סוג/i', 'בסוג', $text); // "ב סוג" -> "בסוג"
    $text = preg_replace('/ה\s+מחיר/i', 'המחיר', $text); // "ה מחיר" -> "המחיר"
    $text = preg_replace('/ל\s+חודש/i', 'לחודש', $text); // "ל חודש" -> "לחודש"
    $text = preg_replace('/ל\s+שנה/i', 'לשנה', $text); // "ל שנה" -> "לשנה"
    
    return $text;
}
// הוסף פילטרים נוספים של PMPro
add_filter('pmpro_level_name_text', 'my_pmpro_fix_checkout_page_text', 999);
add_filter('pmpro_level_cost_text', 'my_pmpro_fix_checkout_page_text', 999);

/**
 * פילטר לתיקון טקסטים של pmpro_getLevelCost - עובד ישירות על הפלט
 */
function my_pmpro_fix_level_cost_output($cost_text) {
    if (is_admin()) {
        return $cost_text;
    }
    
    // הסרת המילה "עכשיו" אחרי מחירים
    $cost_text = preg_replace('/\s+עכשיו\s*/i', '', $cost_text);
    
    // הסרת "סוג מנוי:." / "סוג מנוי." / "סוג מנוי:" מכל המקומות - כל הווריאציות
    // תופס גם עם רווחים, נקודות, נקודתיים, HTML entities וכו'
    // תיקון: תופס גם "סוג המנוי" (עם ה' הידיעה)
    $cost_text = preg_replace('/סוג\s*המנוי\s*[:：]\s*[\.。]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*המנוי\s*[\.。]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*המנוי\s*[:：]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[\.。]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[:：]\s*/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[\.。]\s*[\.。]/iu', '', $cost_text);
    $cost_text = preg_replace('/סוג\s*מנוי\s*[:：]\s*[\.。]\s*[\.。]/iu', '', $cost_text);
    
    // תיקון נקודה כפולה אחרי "מנוי" - כל וריאציה אפשרית
    $cost_text = preg_replace('/מנוי\s*:\s*\./i', 'מנוי.', $cost_text); // "מנוי:." -> "מנוי."
    $cost_text = preg_replace('/מנוי\s*\.\s*\./i', 'מנוי.', $cost_text); // "מנוי.." -> "מנוי."
    $cost_text = preg_replace('/מנוי\s*\.\s*\.\s*\./i', 'מנוי.', $cost_text); // "מנוי..." -> "מנוי."
    $cost_text = preg_replace('/מנוי\s*:\s*\.\s*\./i', 'מנוי.', $cost_text); // "מנוי:.." -> "מנוי."
    
    // תיקון "המחיר עבור חשבון הוא .עכשיו"
    $cost_text = preg_replace('/המחיר עבור חשבון הוא\s*\.\s*עכשיו/i', 'המחיר עבור המנוי הוא', $cost_text);
    // תיקון "המחיר עבור חשבון הוא" (ללא נקודה)
    $cost_text = preg_replace('/המחיר עבור חשבון הוא/i', 'המחיר עבור המנוי הוא', $cost_text);
    
    // תיקון רווחים מיותרים בין אותיות למילים
    $cost_text = preg_replace('/ב\s+סוג/i', 'בסוג', $cost_text); // "ב סוג" -> "בסוג"
    $cost_text = preg_replace('/ה\s+מחיר/i', 'המחיר', $cost_text); // "ה מחיר" -> "המחיר"
    $cost_text = preg_replace('/ל\s+חודש/i', 'לחודש', $cost_text); // "ל חודש" -> "לחודש"
    $cost_text = preg_replace('/ל\s+שנה/i', 'לשנה', $cost_text); // "ל שנה" -> "לשנה"
    
    return $cost_text;
}
add_filter('pmpro_level_cost_text', 'my_pmpro_fix_level_cost_output', 999);

/**
 * אכיפת ברירת מחדל: ישראל כמדינת חיוב
 */
function my_pmpro_default_country_for_checkout($country) {
    return 'IL'; // IL הוא הקוד של ישראל
}
add_filter('pmpro_default_country', 'my_pmpro_default_country_for_checkout');


/**
 * Shortcode המציג תוכן רק אם רמת המנוי הנוכחית (בצ'קאאוט) תואמת
 * שימוש: [pmpro_content_by_level level="7"]תוכן לקורס[/pmpro_content_by_level]
 */
function pmpro_content_by_level_shortcode($atts, $content = null) {
    $atts = shortcode_atts(array(
        'level' => '0', 
    ), $atts, 'pmpro_content_by_level');
    
    $current_level_id = 0;
    if ( pmpro_is_checkout() && isset($_REQUEST['level']) ) {
        $current_level_id = (int)$_REQUEST['level'];
    }
    
    $target_levels = array_map('intval', explode(',', str_replace(' ', '', $atts['level'])));

    if ( in_array($current_level_id, $target_levels) ) {
        return do_shortcode($content);
    }
    
    return '';
}
add_shortcode('pmpro_content_by_level', 'pmpro_content_by_level_shortcode');

/**
 * וורדפרס: מאפשר שימוש באותיות עבריות בשם משתמש
 */
function pmpro_allow_hebrew_usernames($valid) {
    // מאפשר אותיות, מספרים, רווחים ואותיות בעברית.
    if (!empty($_POST['username']) && preg_match('/[^a-zA-Z0-9\s\p{Hebrew}_-]/u', $_POST['username'])) {
        $valid = false; // עדיין מציג שגיאה אם יש תווים אסורים אחרים
    } else {
        $valid = true;
    }
    return $valid;
}
add_filter('validate_username', 'pmpro_allow_hebrew_usernames', 10, 2);

// ==========================================================
// פונקציות עזר לבדיקת סטטוס תשבצים
// ==========================================================

/**
 * בדיקת סטטוס תשבץ עבור משתמש
 * מחזיר: 'completed' (פתור), 'in_progress' (בתהליך), 'not_started' (לא התחיל)
 * 
 * @param int $crossword_id - ID של התשבץ הבסיסי (post type: crossword)
 * @param int $user_id - ID של המשתמש (null = משתמש נוכחי)
 * @return string - הסטטוס
 */
function get_crossword_status($crossword_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // אם אין משתמש מחובר - תמיד "לא התחיל"
    if (!$user_id) {
        return 'not_started';
    }
    
    // חיפוש user_crossword עבור המשתמש והתשבץ הזה
    $query_args = array(
        'posts_per_page' => 1,
        'post_type'      => 'user_crossword',
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'user',
                'value'   => $user_id,
                'compare' => '='
            ),
            array(
                'key'     => 'crossword',
                'value'   => $crossword_id,
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($query_args);
    
    if (!$query->have_posts()) {
        return 'not_started';
    }
    
    // יש user_crossword - בדיקה אם פתור או בתהליך
    while ($query->have_posts()) {
        $query->the_post();
        $user_crossword_id = get_the_ID();
        $saved_json = get_field('json', $user_crossword_id);
        
        if (empty($saved_json)) {
            wp_reset_postdata();
            return 'not_started';
        }
        
        // קבלת הפתרון מהתשבץ הבסיסי
        $base_json = get_field('json', $crossword_id);
        if (empty($base_json)) {
            wp_reset_postdata();
            return 'in_progress'; // אם אין פתרון - נחשב בתהליך
        }
        
        // השוואה בין הפתרון השמור לפתרון הנכון
        $saved_cells = json_decode($saved_json, true);
        $base_cells = json_decode($base_json, true);
        
        if (!is_array($saved_cells) || !is_array($base_cells)) {
            wp_reset_postdata();
            return 'in_progress';
        }
        
        // בדיקה אם כל התאים תואמים
        $all_match = true;
        $has_progress = false;
        
        foreach ($base_cells as $index => $base_cell) {
            if (!isset($saved_cells[$index])) {
                continue;
            }
            
            $saved_letter = isset($saved_cells[$index]['letter']) ? $saved_cells[$index]['letter'] : '';
            $base_letter = isset($base_cell['letter']) ? $base_cell['letter'] : '';
            
            if (mb_strlen($saved_letter) > 0) {
                $has_progress = true;
            }
            
            if ($saved_letter !== $base_letter) {
                $all_match = false;
            }
        }
        
        wp_reset_postdata();
        
        if ($all_match && $has_progress) {
            return 'completed';
        } elseif ($has_progress) {
            return 'in_progress';
        } else {
            return 'not_started';
        }
    }
    
    wp_reset_postdata();
    return 'not_started';
}

/**
 * קבלת סמל/אינדיקציה ויזואלית לסטטוס תשבץ
 * 
 * @param string $status - הסטטוס ('completed', 'in_progress', 'not_started')
 * @return string - HTML של האינדיקציה
 */
function get_crossword_status_indicator($status) {
    $indicators = array(
        'completed' => '<span class="crossword-status crossword-status-completed" title="פתור">✓</span>',
        'in_progress' => '<span class="crossword-status crossword-status-in-progress" title="בתהליך">⏳</span>',
        'not_started' => '<span class="crossword-status crossword-status-not-started" title="לא התחיל">○</span>'
    );
    
    return isset($indicators[$status]) ? $indicators[$status] : $indicators['not_started'];
}


// ==========================================================
// תשבץ אוטומטי - קבלה מ-Google Cloud והצגה למשתמש
// ==========================================================

/**
 * רישום REST API endpoint לקבלת תשבץ מ-Google Cloud
 * URL: /wp-json/crossword/v1/generate
 */
add_action('rest_api_init', function() {
    register_rest_route('crossword/v1', '/generate', array(
        'methods' => 'POST',
        'callback' => 'handle_automated_crossword',
        'permission_callback' => '__return_true', // ניתן לשנות לאימות אם צריך
    ));
});

/**
 * טיפול בבקשה מ-Google Cloud - יצירת תשבץ חדש
 * 
 * מצפה ל-JSON עם המבנה הבא:
 * {
 *   "size_x": 7,
 *   "size_y": 7,
 *   "title": "כותרת התשבץ",
 *   "definitions": [
 *     {
 *       "number": 1,
 *       "place_x": 1,
 *       "place_y": 1,
 *       "vertical_or_horizontal": 1, // 1 = מאוזן, 2 = מאונך
 *       "definition": "הגדרה",
 *       "solution": "פתרון",
 *       "solution_exp": "הסבר פתרון (אופציונלי)",
 *       "first_word_length": 3,
 *       "second_word_length": 4,
 *       "third_word_length": 0
 *     }
 *   ],
 *   "secret_key": "מפתח סודי לאימות (אופציונלי)"
 * }
 */
function handle_automated_crossword($request) {
    // קבלת הנתונים מה-request
    $data = $request->get_json_params();
    
    // בדיקת תקינות בסיסית
    if (empty($data)) {
        return new WP_Error('invalid_data', 'נתונים לא תקינים', array('status' => 400));
    }
    
    // אימות (אם יש secret key)
    $secret_key = get_option('crossword_auto_secret_key', '');
    if (!empty($secret_key) && (!isset($data['secret_key']) || $data['secret_key'] !== $secret_key)) {
        return new WP_Error('unauthorized', 'לא מורשה', array('status' => 401));
    }
    
    // בדיקת שדות חובה
    if (empty($data['size_x']) || empty($data['size_y']) || empty($data['definitions'])) {
        return new WP_Error('missing_fields', 'שדות חובה חסרים', array('status' => 400));
    }
    
    // יצירת post חדש
    $post_data = array(
        'post_title'    => !empty($data['title']) ? sanitize_text_field($data['title']) : 'תשבץ אוטומטי - ' . date('Y-m-d H:i'),
        'post_content'  => '',
        'post_status'   => 'publish',
        'post_type'     => 'crossword',
        'post_author'   => 1, // משתמש ראשי
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        return new WP_Error('post_creation_failed', 'נכשל ביצירת התשבץ', array('status' => 500));
    }
    
    // שמירת שדות ACF
    update_field('size_x', intval($data['size_x']), $post_id);
    update_field('size_y', intval($data['size_y']), $post_id);
    
    // שמירת ההגדרות (repeater field)
    if (function_exists('update_field') && !empty($data['definitions'])) {
        $definitions = array();
        
        foreach ($data['definitions'] as $def) {
            $definitions[] = array(
                'number' => isset($def['number']) ? intval($def['number']) : 0,
                'place_x' => isset($def['place_x']) ? intval($def['place_x']) : 1,
                'place_y' => isset($def['place_y']) ? intval($def['place_y']) : 1,
                'vertical_or_horizontal' => isset($def['vertical_or_horizontal']) ? intval($def['vertical_or_horizontal']) : 1,
                'definition' => isset($def['definition']) ? sanitize_text_field($def['definition']) : '',
                'solution' => isset($def['solution']) ? sanitize_text_field($def['solution']) : '',
                'solution_exp' => isset($def['solution_exp']) ? sanitize_text_field($def['solution_exp']) : '',
                'first_word_length' => isset($def['first_word_length']) ? intval($def['first_word_length']) : 0,
                'second_word_length' => isset($def['second_word_length']) ? intval($def['second_word_length']) : 0,
                'third_word_length' => isset($def['third_word_length']) ? intval($def['third_word_length']) : 0,
            );
        }
        
        // עדכון שדה ה-repeater
        update_field('line', $definitions, $post_id);
    }
    
    // ה-save_post_callback יוצר אוטומטית את ה-JSON
    // אבל אם צריך לעשות זאת מיד:
    do_action('save_post', $post_id);
    
    // החזרת תשובה עם פרטי התשבץ שנוצר
    return new WP_REST_Response(array(
        'success' => true,
        'post_id' => $post_id,
        'permalink' => get_permalink($post_id),
        'edit_link' => admin_url('post.php?post=' . $post_id . '&action=edit'),
        'message' => 'התשבץ נוצר בהצלחה'
    ), 200);
}

/**
 * AJAX endpoint לחיצה על כפתור יצירת תשבץ אוטומטי
 * מפעיל את המנוע ב-Google Cloud
 */
add_action('wp_ajax_generate_auto_crossword', 'generate_auto_crossword_ajax');
add_action('wp_ajax_nopriv_generate_auto_crossword', 'generate_auto_crossword_ajax');

function generate_auto_crossword_ajax() {
    // בדיקת nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'generate_crossword_nonce')) {
        wp_send_json_error(array('message' => 'אימות נכשל'));
        return;
    }
    
    // קבלת פרמטרים (אם יש)
    $params = isset($_POST['params']) ? $_POST['params'] : array();
    
    // URL של המנוע ב-Google Cloud
    $cloud_function_url = get_option('crossword_cloud_function_url', '');
    
    if (empty($cloud_function_url)) {
        wp_send_json_error(array('message' => 'כתובת המנוע לא מוגדרת'));
        return;
    }
    
    // שליחת בקשה ל-Google Cloud
    $response = wp_remote_post($cloud_function_url, array(
        'timeout' => 60, // 60 שניות
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($params),
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'שגיאה בתקשורת עם המנוע: ' . $response->get_error_message()));
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        wp_send_json_error(array('message' => 'המנוע החזיר שגיאה: ' . $response_code));
        return;
    }
    
    // המנוע ב-Google Cloud אמור להחזיר JSON עם נתוני התשבץ
    $crossword_data = json_decode($response_body, true);
    
    if (empty($crossword_data)) {
        wp_send_json_error(array('message' => 'נתונים לא תקינים מהמנוע'));
        return;
    }
    
    // יצירת request פנימי ל-REST API endpoint שלנו
    $rest_request = new WP_REST_Request('POST', '/crossword/v1/generate');
    $rest_request->set_header('Content-Type', 'application/json');
    $rest_request->set_body(json_encode($crossword_data));
    
    $rest_response = handle_automated_crossword($rest_request);
    
    if (is_wp_error($rest_response)) {
        wp_send_json_error(array('message' => $rest_response->get_error_message()));
        return;
    }
    
    // החזרת תשובה למשתמש
    $response_data = $rest_response->get_data();
    wp_send_json_success($response_data);
}

/**
 * הוספת JavaScript לכפתור יצירת תשבץ אוטומטי
 */
function enqueue_auto_crossword_script() {
    wp_add_inline_script('crossword-scripts', '
        jQuery(document).ready(function($) {
            // כפתור יצירת תשבץ אוטומטי
            $(document).on("click", "#generate-auto-crossword", function(e) {
                e.preventDefault();
                var $button = $(this);
                var originalText = $button.text();
                
                // הצגת טעינה
                $button.prop("disabled", true).html("<span class=\"loading-spinner\"></span>יוצר תשבץ...");
                
                // שליחת בקשה
                $.ajax({
                    url: ajaxpagination.ajaxurl,
                    type: "POST",
                    data: {
                        action: "generate_auto_crossword",
                        nonce: "' . wp_create_nonce('generate_crossword_nonce') . '",
                        params: {} // ניתן להוסיף פרמטרים כאן
                    },
                    success: function(response) {
                        if (response.success) {
                            // הצגת הודעה והפנייה לתשבץ החדש
                            alert("התשבץ נוצר בהצלחה!");
                            if (response.data.permalink) {
                                window.location.href = response.data.permalink;
                            } else {
                                window.location.reload();
                            }
                        } else {
                            alert("שגיאה: " + (response.data.message || "שגיאה לא ידועה"));
                            $button.prop("disabled", false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("שגיאה בתקשורת: " + error);
                        $button.prop("disabled", false).text(originalText);
                    }
                });
            });
        });
    ');
}
add_action('wp_enqueue_scripts', 'enqueue_auto_crossword_script', 20);

/**
 * יצירת דף הגדרות ב-Admin להגדרת כתובת המנוע
 */
add_action('admin_menu', 'crossword_auto_settings_menu');

function crossword_auto_settings_menu() {
    add_options_page(
        'הגדרות תשבץ אוטומטי',
        'תשבץ אוטומטי',
        'manage_options',
        'crossword-auto-settings',
        'crossword_auto_settings_page'
    );
}

function crossword_auto_settings_page() {
    // שמירת הגדרות
    if (isset($_POST['submit']) && check_admin_referer('crossword_auto_settings')) {
        $cloud_url = sanitize_text_field($_POST['cloud_function_url']);
        $secret_key = sanitize_text_field($_POST['secret_key']);
        
        update_option('crossword_cloud_function_url', $cloud_url);
        update_option('crossword_auto_secret_key', $secret_key);
        
        echo '<div class="notice notice-success"><p>ההגדרות נשמרו בהצלחה!</p></div>';
    }
    
    $current_url = get_option('crossword_cloud_function_url', '');
    $current_secret = get_option('crossword_auto_secret_key', '');
    ?>
    <div class="wrap">
        <h1>הגדרות תשבץ אוטומטי</h1>
        <form method="post" action="">
            <?php wp_nonce_field('crossword_auto_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cloud_function_url">כתובת המנוע ב-Google Cloud</label>
                    </th>
                    <td>
                        <input 
                            type="url" 
                            id="cloud_function_url" 
                            name="cloud_function_url" 
                            value="<?php echo esc_attr($current_url); ?>" 
                            class="regular-text"
                            placeholder="https://YOUR-REGION-YOUR-PROJECT.cloudfunctions.net/generate-crossword"
                        />
                        <p class="description">הזן את כתובת ה-Cloud Function שלך</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="secret_key">מפתח סודי (אופציונלי)</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="secret_key" 
                            name="secret_key" 
                            value="<?php echo esc_attr($current_secret); ?>" 
                            class="regular-text"
                            placeholder="השאר ריק אם לא צריך אימות"
                        />
                        <p class="description">מפתח לאימות בקשות מהמנוע</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('שמור הגדרות'); ?>
        </form>
        
        <hr>
        
        <h2>מידע</h2>
        <p><strong>REST API Endpoint:</strong> <code><?php echo home_url('/wp-json/crossword/v1/generate'); ?></code></p>
        <p><strong>ID כפתור:</strong> <code>generate-auto-crossword</code></p>
        <p><strong>AJAX Action:</strong> <code>generate_auto_crossword</code></p>
        
        <h3>איך להשתמש:</h3>
        <ol>
            <li>הזן את כתובת המנוע ב-Google Cloud למעלה</li>
            <li>הוסף כפתור בדף עם ID: <code>generate-auto-crossword</code></li>
            <li>המנוע צריך לשלוח POST ל-endpoint למעלה</li>
        </ol>
    </div>
    <?php
}

/**
 * תיקון תפריט הצד - הסתרת פריטים למשתמשים לא מחוברים
 * 
 * אם התפריט מוגדר ב-WordPress Menu (לא Elementor), הקוד הזה יסתיר פריטים
 * שמיועדים למשתמשים מחוברים בלבד.
 * 
 * שימוש: הוסף class "logged-in-only" לפריטי התפריט ב-WordPress Admin → Appearance → Menus
 */
function hide_menu_items_for_logged_out($items, $args) {
    if (is_user_logged_in()) {
        // אם המשתמש מחובר, הסר פריטים עם class "logged-out-only" (כמו "התחברות")
        foreach ($items as $key => $item) {
            $classes = $item->classes;
            // הסר פריטים עם class "logged-out-only"
            if (is_array($classes) && in_array('logged-out-only', $classes)) {
                unset($items[$key]);
                continue;
            }
            // גם הסר פריטים שכותרתם "התחברות" או "Login"
            // CRITICAL: Use wp_strip_all_tags() and mb_strpos() for proper Hebrew support
            $title = isset($item->title) ? mb_strtolower(wp_strip_all_tags($item->title)) : '';
            $url = isset($item->url) ? mb_strtolower($item->url) : '';
            if (mb_strpos($title, 'התחברות') !== false || 
                mb_strpos($title, 'login') !== false ||
                mb_strpos($url, '/login') !== false ||
                mb_strpos($url, '/התחברות') !== false) {
                unset($items[$key]);
            }
        }
    } else {
        // אם המשתמש לא מחובר, הסר פריטים עם class "logged-in-only"
        foreach ($items as $key => $item) {
            $classes = $item->classes;
            if (is_array($classes) && in_array('logged-in-only', $classes)) {
                unset($items[$key]);
            }
        }
    }
    return $items;
}
add_filter('wp_nav_menu_objects', 'hide_menu_items_for_logged_out', 10, 2);

/**
 * הוספת CSS להסתרת תפריטים ב-Elementor (אם אין Display Conditions)
 * 
 * אם התפריט מוגדר ב-Elementor ואין Elementor Pro, אפשר להשתמש ב-CSS הזה
 */
function add_menu_visibility_css() {
    ?>
    <style>
        /* הסתרת "התחברות" למשתמשים מחוברים - Elementor */
        body.logged-in .elementor-menu-item:has(a[href*="login"]),
        body.logged-in .elementor-menu-item:has(a[href*="התחברות"]),
        body.logged-in .elementor-menu-item.logged-out-only,
        body.logged-in #menu-item-login,
        body.logged-in .menu-item-login {
            display: none !important;
        }
        /* הסתרת תפריט למשתמשים מחוברים - אם מוגדר ב-Elementor */
        body:not(.logged-in) .elementor-widget-menu.logged-in-menu,
        body:not(.logged-in) .elementor-menu-toggle.logged-in-menu {
            display: none !important;
        }
    </style>
    <?php
}
add_action('wp_head', 'add_menu_visibility_css');

/**
 * Force body class "logged-in" on homepage if user is actually logged in
 * This fixes cache issues where body class might be missing
 * CRITICAL: Use very high priority to override any caching
 */
add_filter('body_class', function($classes) {
    if (is_front_page()) {
        // Force fresh check - don't rely on cached values
        $is_logged_in = is_user_logged_in();
        if ($is_logged_in && !in_array('logged-in', $classes)) {
            $classes[] = 'logged-in';
            // Also ensure user ID is available
            $user_id = get_current_user_id();
            if ($user_id) {
                $classes[] = 'user-' . $user_id;
            }
        } elseif (!$is_logged_in && in_array('logged-in', $classes)) {
            // Remove logged-in class if user is not actually logged in
            $classes = array_diff($classes, ['logged-in']);
        }
    }
    return $classes;
}, 99999); // Very high priority to override cache

/**
 * JavaScript to fix menu visibility on homepage (especially after login)
 * This ensures the menu is updated correctly even if cache shows old state
 */
function add_homepage_menu_fix_js() {
    if (!is_front_page()) {
        return;
    }
    ?>
    <script>
    (function() {
        'use strict';
        
        function checkAndUpdateMenu() {
            // Check if user is actually logged in - multiple methods for reliability
            // Method 1: Check boot element from homev2 plugin (PHP-set, most reliable for homepage)
            const boot = document.querySelector('.hb-homev2-boot');
            const bootLoggedIn = boot && boot.getAttribute('data-logged-in') === '1';
            
            // Method 2: Check body class (WordPress sets this, but may be cached)
            const bodyHasLoggedIn = document.body.classList.contains('logged-in');
            
            // Method 3: Check for user menu/astronaut icon
            const hasUserMenu = document.querySelector('.usermenu-icon');
            
            // Method 4: Check for logout link
            const hasLogoutLink = document.querySelector('a[href*="logout"], a[href*="התנתקות"]');
            
            // Priority: boot attribute is most reliable (set by PHP in real-time)
            // Fallback to body class and other indicators
            const isLoggedIn = bootLoggedIn || bodyHasLoggedIn || !!hasUserMenu || !!hasLogoutLink;
            
            // Debug logging
            console.log('🏠 Homepage menu check:', {
                boot: boot ? 'found' : 'not found',
                bootLoggedIn: bootLoggedIn,
                bodyHasLoggedIn: bodyHasLoggedIn,
                hasUserMenu: !!hasUserMenu,
                hasLogoutLink: !!hasLogoutLink,
                isLoggedIn: isLoggedIn
            });
            
            // Update boot element if other indicators say logged in but boot doesn't
            if (!bootLoggedIn && (bodyHasLoggedIn || hasUserMenu || hasLogoutLink) && boot) {
                console.log('🔧 Updating boot element: data-logged-in = 1');
                boot.setAttribute('data-logged-in', '1');
            }
            
            // Also force body class if user is logged in but class is missing
            if (isLoggedIn && !bodyHasLoggedIn) {
                console.log('🔧 Adding logged-in class to body');
                document.body.classList.add('logged-in');
            }
            
            // Find login menu items and update their URLs to subscription page
            const loginItems = document.querySelectorAll(
                '.elementor-menu-item a[href*="login"], ' +
                '.elementor-menu-item a[href*="התחברות"], ' +
                '.elementor-menu-item.logged-out-only, ' +
                '#menu-item-login, ' +
                '.menu-item-login'
            );
            
            // Get subscription page URL
            const subPageUrl = '<?php 
                $sub_page = get_page_by_path("מנוי-לתשבץ-היגיון-שבועי");
                echo $sub_page ? esc_js(get_permalink($sub_page->ID)) : esc_js(home_url("/מנוי-לתשבץ-היגיון-שבועי/"));
            ?>';
            
            // Update login links to point to subscription page instead of login/homepage
            loginItems.forEach(function(item) {
                const link = item.tagName === 'A' ? item : item.querySelector('a');
                if (link) {
                    const href = link.getAttribute('href');
                    // If link points to login, homepage, or is empty, redirect to subscription page
                    if (!href || 
                        href === '#' || 
                        href.includes('login') || 
                        href.includes('התחברות') ||
                        href === '<?php echo esc_js(home_url("/")); ?>' ||
                        href === '<?php echo esc_js(home_url()); ?>') {
                        link.setAttribute('href', subPageUrl);
                    }
                }
            });
            
            // Find user menu/astronaut icon
            const userMenuItems = document.querySelectorAll(
                '.usermenu-icon, ' +
                '.elementor-menu-item a[href*="/user/"], ' +
                '.elementor-menu-item.logged-in-only'
            );
            
            // Debug: Check what we found
            console.log('🔍 User menu items found:', {
                usermenuIcon: document.querySelector('.usermenu-icon') ? 'FOUND' : 'NOT FOUND',
                userLinks: document.querySelectorAll('.elementor-menu-item a[href*="/user/"]').length,
                loggedInOnly: document.querySelectorAll('.elementor-menu-item.logged-in-only').length,
                totalUserMenuItems: userMenuItems.length
            });
            
            if (isLoggedIn) {
                // Hide login items
                loginItems.forEach(function(item) {
                    const menuItem = item.closest('.elementor-menu-item') || item;
                    menuItem.style.display = 'none';
                    menuItem.style.visibility = 'hidden';
                });
                
                // Show user menu items - FORCE display
                userMenuItems.forEach(function(item) {
                    const menuItem = item.closest('.elementor-menu-item') || item;
                    const currentDisplay = window.getComputedStyle(menuItem).display;
                    console.log('👤 Showing user menu item:', {
                        element: menuItem.className,
                        currentDisplay: currentDisplay,
                        willShow: currentDisplay === 'none'
                    });
                    // Force display with !important via style attribute
                    menuItem.style.setProperty('display', '', 'important');
                    menuItem.style.setProperty('visibility', 'visible', 'important');
                    menuItem.style.setProperty('opacity', '1', 'important');
                });
                
                // CRITICAL: Force show ALL elements with user-related classes/IDs
                const forceShowSelectors = [
                    '.usermenu-icon',
                    '[class*="user"]',
                    '[id*="user"]',
                    '[class*="account"]',
                    '[id*="account"]',
                    '.elementor-menu-item a[href*="/user/"]',
                    '.elementor-menu-item.logged-in-only'
                ];
                
                forceShowSelectors.forEach(function(selector) {
                    try {
                        const elements = document.querySelectorAll(selector);
                        elements.forEach(function(el) {
                            // Skip if it's a login link
                            if (el.href && (el.href.includes('login') || el.href.includes('התחברות'))) {
                                return;
                            }
                            el.style.setProperty('display', '', 'important');
                            el.style.setProperty('visibility', 'visible', 'important');
                            el.style.setProperty('opacity', '1', 'important');
                        });
                    } catch (e) {
                        console.warn('Error with selector:', selector, e);
                    }
                });
                
                // If usermenu-icon doesn't exist, try to find it in all elements
                if (!document.querySelector('.usermenu-icon')) {
                    console.warn('⚠️ .usermenu-icon not found! Searching for alternatives...');
                    const allUserElements = document.querySelectorAll('[class*="user"], [class*="account"], [id*="user"], [id*="account"]');
                    console.log('🔍 Alternative user elements found:', allUserElements.length);
                    allUserElements.forEach(function(el, idx) {
                        if (idx < 5) { // Show first 5
                            console.log('  -', el.className || el.id, el.tagName);
                        }
                    });
                }
            } else {
                // Show login items
                loginItems.forEach(function(item) {
                    const menuItem = item.closest('.elementor-menu-item') || item;
                    menuItem.style.display = '';
                });
                
                // Hide user menu items
                userMenuItems.forEach(function(item) {
                    const menuItem = item.closest('.elementor-menu-item') || item;
                    menuItem.style.display = 'none';
                });
            }
        }
        
        // Force check immediately
        checkAndUpdateMenu();
        
        // Run on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkAndUpdateMenu);
        } else {
            checkAndUpdateMenu();
        }
        
        // Run after delays (in case menu loads dynamically)
        setTimeout(checkAndUpdateMenu, 100);
        setTimeout(checkAndUpdateMenu, 300);
        setTimeout(checkAndUpdateMenu, 500);
        setTimeout(checkAndUpdateMenu, 1000);
        setTimeout(checkAndUpdateMenu, 2000);
        setTimeout(checkAndUpdateMenu, 3000);
        
        // Watch for DOM changes (in case menu is loaded via AJAX)
        // Throttle to avoid excessive calls
        let menuCheckTimeout;
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(function() {
                clearTimeout(menuCheckTimeout);
                menuCheckTimeout = setTimeout(function() {
                    checkAndUpdateMenu();
                }, 200);
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
        
        // Also check when Elementor frontend is ready
        if (window.elementorFrontend) {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/global', checkAndUpdateMenu);
            // Also check when Elementor is fully loaded
            window.elementorFrontend.hooks.addAction('frontend/init', function() {
                setTimeout(checkAndUpdateMenu, 500);
            });
        }
        
        // Check on window load (after all resources are loaded)
        window.addEventListener('load', function() {
            setTimeout(checkAndUpdateMenu, 100);
        });
        
        // Check on focus (in case user switched tabs and came back)
        window.addEventListener('focus', function() {
            setTimeout(checkAndUpdateMenu, 100);
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'add_homepage_menu_fix_js');

/**
 * Custom Breadcrumbs Function
 * Displays breadcrumbs on all pages except homepage
 */
function custom_breadcrumbs() {
    if (is_front_page()) {
        return; // Don't show on homepage
    }
    
    $breadcrumbs = array();
    $breadcrumbs[] = '<a href="' . esc_url(home_url('/')) . '">בית</a>';
    
    if (is_singular('crossword')) {
        // For crossword posts - full path
        // בית > תשבצי היגיון > כל התשבצים / תשבץ שבועי > [שם התשבץ]
        
        // Check if it's a weekly crossword
        $is_weekly = false;
        $weekly_page = get_page_by_path('תשבץ-שבועי');
        if ($weekly_page) {
            // Check if current crossword is linked to weekly page
            $weekly_crosswords = get_posts(array(
                'post_type' => 'crossword',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'weekly_crossword',
                        'value' => '1',
                        'compare' => '='
                    )
                )
            ));
            foreach ($weekly_crosswords as $wc) {
                if ($wc->ID == get_the_ID()) {
                    $is_weekly = true;
                    break;
                }
            }
        }
        
        // Add "תשבצי היגיון" page
        $page1 = get_page_by_path('תשבצי-היגיון');
        if ($page1) {
            $breadcrumbs[] = '<a href="' . esc_url(get_permalink($page1->ID)) . '">תשבצי היגיון</a>';
        }
        
        // Add "כל התשבצים" or "תשבץ שבועי"
        if ($is_weekly && $weekly_page) {
            $breadcrumbs[] = '<a href="' . esc_url(get_permalink($weekly_page->ID)) . '">תשבץ שבועי</a>';
        } else {
            // Try to find "כל התשבצים" page or archive
            $all_crosswords_page = get_page_by_path('תשבצים');
            if ($all_crosswords_page) {
                $breadcrumbs[] = '<a href="' . esc_url(get_permalink($all_crosswords_page->ID)) . '">כל התשבצים</a>';
            } else {
                // Use archive link
                $post_type_obj = get_post_type_object('crossword');
                if ($post_type_obj && $post_type_obj->has_archive) {
                    $breadcrumbs[] = '<a href="' . esc_url(get_post_type_archive_link('crossword')) . '">כל התשבצים</a>';
                }
            }
        }
        
        $breadcrumbs[] = '<span class="breadcrumb-current">' . esc_html(get_the_title()) . '</span>';
        
    } elseif (is_page()) {
        $page = get_queried_object();
        $page_title = get_the_title();
        $page_slug = $page->post_name;
        $current_page_id = $page->ID;
        
        // Build breadcrumb path based on page slug and hierarchy
        // Add category pages for specific pages FIRST
        if ($page_slug == 'תשבץ-שבועי') {
            $page1 = get_page_by_path('תשבצי-היגיון');
            if ($page1 && $page1->ID != $current_page_id) {
                $breadcrumbs[] = '<a href="' . esc_url(get_permalink($page1->ID)) . '">תשבצי היגיון</a>';
            }
        } elseif ($page_slug == 'תשבצים-לדוגמה') {
            // Add "תשבצי היגיון" before examples page
            $page1 = get_page_by_path('תשבצי-היגיון');
            if ($page1 && $page1->ID != $current_page_id) {
                $breadcrumbs[] = '<a href="' . esc_url(get_permalink($page1->ID)) . '">תשבצי היגיון</a>';
            }
        } elseif ($page_slug == 'משחקי-מחשבה') {
            // Main games page - no parent needed, just show current page
        } elseif (strpos($page_slug, 'משחק') !== false || strpos($page_slug, 'game') !== false || 
                  $page_slug == 'מילת-היום' || $page_slug == 'connections' || $page_slug == 'sudoku') {
            // For individual game pages - add "משחקי מחשבה" first
            $games_page = get_page_by_path('משחקי-מחשבה');
            if ($games_page) {
                $breadcrumbs[] = '<a href="' . esc_url(get_permalink($games_page->ID)) . '">משחקי מחשבה</a>';
            } else {
                // Fallback if page doesn't exist
                $breadcrumbs[] = '<a href="' . esc_url(home_url('/משחקי-מחשבה/')) . '">משחקי מחשבה</a>';
            }
        } elseif ($page_slug == 'קורס' || $page_slug == 'מנויים') {
            // Course and membership pages - no specific parent, but could add general category
        }
        
        // Add parent pages if they exist (after category pages)
        if ($page->post_parent) {
            $ancestors = get_post_ancestors($page->ID);
            $ancestors = array_reverse($ancestors);
            foreach ($ancestors as $ancestor) {
                $ancestor_title = get_the_title($ancestor);
                $ancestor_slug = get_post_field('post_name', $ancestor);
                // Skip if already added as category page or if it's the current page
                if ($ancestor != $current_page_id && 
                    $ancestor_slug != 'תשבצי-היגיון' && 
                    $ancestor_slug != 'משחקי-מחשבה') {
                    $breadcrumbs[] = '<a href="' . esc_url(get_permalink($ancestor)) . '">' . esc_html($ancestor_title) . '</a>';
                }
            }
        }
        
        $breadcrumbs[] = '<span class="breadcrumb-current">' . esc_html($page_title) . '</span>';
        
    } elseif (is_singular('user_crossword')) {
        // בית > התשבצים שלי > [שם התשבץ]
        $my_crosswords_page = get_page_by_path('התשבצים-שלי');
        if ($my_crosswords_page) {
            $breadcrumbs[] = '<a href="' . esc_url(get_permalink($my_crosswords_page->ID)) . '">התשבצים שלי</a>';
        } else {
            $breadcrumbs[] = '<a href="' . esc_url(home_url('/התשבצים-שלי/')) . '">התשבצים שלי</a>';
        }
        $breadcrumbs[] = '<span class="breadcrumb-current">' . esc_html(get_the_title()) . '</span>';
        
    } elseif (is_post_type_archive('crossword')) {
        // בית > תשבצי היגיון > כל התשבצים
        $page1 = get_page_by_path('תשבצי-היגיון');
        if ($page1) {
            $breadcrumbs[] = '<a href="' . esc_url(get_permalink($page1->ID)) . '">תשבצי היגיון</a>';
        }
        $breadcrumbs[] = '<span class="breadcrumb-current">כל התשבצים</span>';
        
    } elseif (is_archive()) {
        $breadcrumbs[] = '<span class="breadcrumb-current">' . esc_html(get_the_archive_title()) . '</span>';
    } elseif (is_search()) {
        $breadcrumbs[] = '<span class="breadcrumb-current">חיפוש: ' . esc_html(get_search_query()) . '</span>';
    } elseif (is_404()) {
        $breadcrumbs[] = '<span class="breadcrumb-current">404 - עמוד לא נמצא</span>';
    }
    
    // Debug: Always show breadcrumbs if we have at least "בית"
    // But make sure we have more than just "בית" + current page
    if (count($breadcrumbs) < 2) {
        return; // Only "בית" - don't show breadcrumbs
    }
    
    echo '<nav class="breadcrumbs-nav" aria-label="פירורי לחם">';
    echo '<div class="breadcrumbs">';
    echo implode(' <span class="breadcrumb-separator">›</span> ', $breadcrumbs);
    echo '</div>';
    echo '</nav>';
}

/**
 * Add Facebook URL to Customizer
 */
function custom_footer_customize_register($wp_customize) {
    // Add section for footer settings
    $wp_customize->add_section('footer_settings', array(
        'title' => 'הגדרות Footer',
        'priority' => 30,
    ));
    
    // Add Facebook URL setting
    $wp_customize->add_setting('facebook_url', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    
    $wp_customize->add_control('facebook_url', array(
        'label' => 'קישור לפייסבוק',
        'section' => 'footer_settings',
        'type' => 'url',
        'description' => 'הזן את כתובת ה-URL של דף הפייסבוק שלך',
    ));
}
add_action('customize_register', 'custom_footer_customize_register');

/**
 * Hook breadcrumbs before content
 * DISABLED - Use [breadcrumbs] shortcode in Elementor instead to avoid duplication
 */
function add_breadcrumbs_hooks() {
    // Disabled - use shortcode instead
}
// add_action('wp', 'add_breadcrumbs_hooks'); // DISABLED

/**
 * Add breadcrumbs via content filter
 * DISABLED - Use [breadcrumbs] shortcode in Elementor instead to avoid duplication
 */
function add_breadcrumbs_to_content($content) {
    // Disabled - use shortcode instead
    return $content;
}
// add_filter('the_content', 'add_breadcrumbs_to_content', 5); // DISABLED

// Hook into Elementor's content output for better compatibility
// DISABLED - Use [breadcrumbs] shortcode in Elementor instead to avoid duplication
// add_action('elementor/page_templates/canvas/before_content', 'custom_breadcrumbs', 10);
// add_action('elementor/page_templates/header-footer/before_content', 'custom_breadcrumbs', 10);
// add_action('elementor/page_templates/full_width/before_content', 'custom_breadcrumbs', 10);

// Removed problematic code - use shortcode instead

// Also add breadcrumbs via shortcode for manual placement - USE THIS IN ELEMENTOR!
add_shortcode('breadcrumbs', 'custom_breadcrumbs');

/**
 * Display internal navigation links based on page type
 * Adds related links and navigation options to pages
 */
function display_internal_navigation_links() {
    $links = array();
    
    if (is_singular('crossword')) {
        // Links for crossword pages - prioritize "כל התשבצים" as main return link
        $all_crosswords_page = get_page_by_path('תשבצים');
        $weekly_page = get_page_by_path('תשבץ-שבועי');
        $examples_page = get_page_by_path('תשבצים-לדוגמה');
        $course_page = get_page_by_path('קורס-תשבצי-היגיון-אונליין');
        $membership_page = get_page_by_path('מנויים');
        
        // Main return link - "כל התשבצים" - check membership
        $all_crosswords_url = '';
        // Check if user is logged in AND has membership
        $has_membership = false;
        if (is_user_logged_in() && function_exists('pmpro_hasMembershipLevel')) {
            $has_membership = pmpro_hasMembershipLevel();
        }
        
        if ($has_membership) {
            // User has membership - try archive first (more reliable)
            $archive_url = get_post_type_archive_link('crossword');
            if ($archive_url && $archive_url != home_url('/') && $archive_url != home_url()) {
                $all_crosswords_url = $archive_url;
            } elseif ($all_crosswords_page && $all_crosswords_page->ID) {
                $test_url = get_permalink($all_crosswords_page->ID);
                if ($test_url && $test_url != home_url('/') && $test_url != home_url()) {
                    $all_crosswords_url = $test_url;
                }
            }
        } else {
            // User not logged in or no membership - link to login/register
            if (function_exists('wpum_get_core_page_id')) {
                $login_page_id = wpum_get_core_page_id('login');
                $register_page_id = wpum_get_core_page_id('register');
                if ($login_page_id) {
                    $all_crosswords_url = get_permalink($login_page_id);
                } elseif ($register_page_id) {
                    $all_crosswords_url = get_permalink($register_page_id);
                } else {
                    $all_crosswords_url = wp_login_url();
                }
            } else {
                $all_crosswords_url = wp_login_url();
            }
        }
        
        // Only add if URL is valid and not home page
        if ($all_crosswords_url && $all_crosswords_url != home_url('/') && $all_crosswords_url != home_url()) {
            $links[] = array(
                'title' => 'כל התשבצים',
                'url' => $all_crosswords_url,
                'icon' => '📋'
            );
        }
        
        // Other related links
        if ($weekly_page) {
            $links[] = array(
                'title' => 'תשבץ שבועי',
                'url' => get_permalink($weekly_page->ID),
                'icon' => '📅'
            );
        }
        if ($examples_page) {
            $links[] = array(
                'title' => 'תשבצים לדוגמה',
                'url' => get_permalink($examples_page->ID),
                'icon' => '🎯'
            );
        }
        if ($course_page && $course_page->ID) {
            $links[] = array(
                'title' => 'קורס מקוון',
                'url' => get_permalink($course_page->ID),
                'icon' => '🎓'
            );
            // Add course registration link - always show
            $links[] = array(
                'title' => 'הרשמה לקורס',
                'url' => get_permalink($course_page->ID),
                'icon' => '✍️'
            );
        }
        if ($membership_page) {
            $links[] = array(
                'title' => 'הצטרף למנויים',
                'url' => get_permalink($membership_page->ID),
                'icon' => '⭐'
            );
        }
        
    } elseif (is_single()) {
        // Regular blog posts - show key site destinations instead of home-only fallback
        $current_post_id = get_queried_object_id();
        $posts_page_id   = get_option('page_for_posts');
        $crossword_page  = get_page_by_path('תשבץ-שבועי');
        $course_page     = get_page_by_path('קורס-תשבצי-היגיון-אונליין');
        $membership_page = get_page_by_path('מנויים');
        $games_page      = get_page_by_path('משחקי-מחשבה');
        $examples_page   = get_page_by_path('תשבצים-לדוגמה');
        $all_crosswords_page = get_page_by_path('תשבצים');

        // Blog index / posts page
        if ($posts_page_id && $posts_page_id != $current_post_id) {
            $links[] = array('title' => 'כל הפוסטים', 'url' => get_permalink($posts_page_id), 'icon' => '📰');
        }

        // Common destinations
        if ($crossword_page && $crossword_page->ID != $current_post_id) {
            $links[] = array('title' => 'תשבץ שבועי', 'url' => get_permalink($crossword_page->ID), 'icon' => '📅');
        }
        if ($course_page && $course_page->ID != $current_post_id) {
            $links[] = array('title' => 'קורס מקוון', 'url' => get_permalink($course_page->ID), 'icon' => '🎓');
            // Add course registration link - always show if course page exists (not current post)
            $links[] = array('title' => 'הרשמה לקורס', 'url' => get_permalink($course_page->ID), 'icon' => '✍️');
        }
        if ($membership_page && $membership_page->ID != $current_post_id) {
            $links[] = array('title' => 'הצטרף למנויים', 'url' => get_permalink($membership_page->ID), 'icon' => '⭐');
        }
        if ($games_page && $games_page->ID != $current_post_id) {
            $links[] = array('title' => 'משחקי מחשבה', 'url' => get_permalink($games_page->ID), 'icon' => '🎮');
        }
        if ($examples_page && $examples_page->ID != $current_post_id) {
            $links[] = array('title' => 'תשבצים לדוגמה', 'url' => get_permalink($examples_page->ID), 'icon' => '🎯');
        }
        if ($all_crosswords_page && $all_crosswords_page->ID != $current_post_id) {
            $links[] = array('title' => 'כל התשבצים', 'url' => get_permalink($all_crosswords_page->ID), 'icon' => '📋');
        }

    } elseif (is_page()) {
        $page_slug = get_queried_object()->post_name;
        $current_page_id = get_queried_object()->ID;
        
        // Links for specific pages
        if ($page_slug == 'תשבץ-שבועי') {
            $all_crosswords_page = get_page_by_path('תשבצים');
            $examples_page = get_page_by_path('תשבצים-לדוגמה');
            $course_page = get_page_by_path('קורס-תשבצי-היגיון-אונליין');
            $membership_page = get_page_by_path('מנויים');
            
            // "כל התשבצים" - check membership
            $all_crosswords_url = '';
            // Check if user is logged in AND has membership
            $has_membership = false;
            if (is_user_logged_in() && function_exists('pmpro_hasMembershipLevel')) {
                $has_membership = pmpro_hasMembershipLevel();
            }
            
            if ($has_membership) {
                // Try archive first (more reliable)
                $archive_url = get_post_type_archive_link('crossword');
                if ($archive_url && $archive_url != home_url('/') && $archive_url != home_url()) {
                    $all_crosswords_url = $archive_url;
                } elseif ($all_crosswords_page && $all_crosswords_page->ID && $all_crosswords_page->ID != $current_page_id) {
                    $test_url = get_permalink($all_crosswords_page->ID);
                    if ($test_url && $test_url != home_url('/') && $test_url != home_url()) {
                        $all_crosswords_url = $test_url;
                    }
                }
            } else {
                // User not logged in or no membership - link to login/register
                if (function_exists('wpum_get_core_page_id')) {
                    $login_page_id = wpum_get_core_page_id('login');
                    $register_page_id = wpum_get_core_page_id('register');
                    if ($login_page_id) {
                        $all_crosswords_url = get_permalink($login_page_id);
                    } elseif ($register_page_id) {
                        $all_crosswords_url = get_permalink($register_page_id);
                    } else {
                        $all_crosswords_url = wp_login_url();
                    }
                } else {
                    $all_crosswords_url = wp_login_url();
                }
            }
            
            // Only add if URL is valid and not home page
            if ($all_crosswords_url && $all_crosswords_url != home_url('/') && $all_crosswords_url != home_url()) {
                $links[] = array('title' => 'כל התשבצים', 'url' => $all_crosswords_url, 'icon' => '📋');
            }
            if ($examples_page && $examples_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבצים לדוגמה', 'url' => get_permalink($examples_page->ID), 'icon' => '🎯');
            }
            if ($course_page && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'קורס מקוון', 'url' => get_permalink($course_page->ID), 'icon' => '🎓');
            }
            // Add course registration link - always show if course page exists (not current page)
            if ($course_page && $course_page->ID && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'הרשמה לקורס', 'url' => get_permalink($course_page->ID), 'icon' => '✍️');
            }
            if ($membership_page && $membership_page->ID != $current_page_id) {
                $links[] = array('title' => 'הצטרף למנויים', 'url' => get_permalink($membership_page->ID), 'icon' => '⭐');
            }
            
        } elseif ($page_slug == 'משחקי-מחשבה') {
            // Main games page - don't link to itself
            $crossword_page = get_page_by_path('תשבץ-שבועי');
            $course_page = get_page_by_path('קורס-תשבצי-היגיון-אונליין');
            $membership_page = get_page_by_path('מנויים');
            $examples_page = get_page_by_path('תשבצים-לדוגמה');
            
            if ($crossword_page && $crossword_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבץ שבועי', 'url' => get_permalink($crossword_page->ID), 'icon' => '📅');
            }
            if ($course_page && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'קורס מקוון', 'url' => get_permalink($course_page->ID), 'icon' => '🎓');
            }
            // Add course registration link
            if ($course_page && $course_page->ID && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'הרשמה לקורס', 'url' => get_permalink($course_page->ID), 'icon' => '✍️');
            }
            if ($membership_page && $membership_page->ID != $current_page_id) {
                $links[] = array('title' => 'הצטרף למנויים', 'url' => get_permalink($membership_page->ID), 'icon' => '⭐');
            }
            if ($examples_page && $examples_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבצים לדוגמה', 'url' => get_permalink($examples_page->ID), 'icon' => '🎯');
            }
            
        } elseif (strpos($page_slug, 'משחק') !== false || strpos($page_slug, 'game') !== false || 
                  $page_slug == 'מילת-היום' || $page_slug == 'connections' || $page_slug == 'sudoku') {
            // Individual game pages - prioritize "כל המשחקים" as main return link
            $games_page = get_page_by_path('משחקי-מחשבה');
            $crossword_page = get_page_by_path('תשבץ-שבועי');
            $course_page = get_page_by_path('קורס-תשבצי-היגיון-אונליין');
            $membership_page = get_page_by_path('מנויים');
            
            // Main return link - only if not on games page itself
            if ($games_page && $games_page->ID != $current_page_id) {
                $links[] = array('title' => 'כל המשחקים', 'url' => get_permalink($games_page->ID), 'icon' => '🎮');
            } elseif (!$games_page) {
                $links[] = array('title' => 'כל המשחקים', 'url' => home_url('/משחקי-מחשבה/'), 'icon' => '🎮');
            }
            
            // Other related links
            if ($crossword_page && $crossword_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבץ שבועי', 'url' => get_permalink($crossword_page->ID), 'icon' => '📅');
            }
            if ($course_page && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'קורס מקוון', 'url' => get_permalink($course_page->ID), 'icon' => '🎓');
            }
            // Add course registration link - always show if course page exists (not current page)
            if ($course_page && $course_page->ID && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'הרשמה לקורס', 'url' => get_permalink($course_page->ID), 'icon' => '✍️');
            }
            if ($membership_page && $membership_page->ID != $current_page_id) {
                $links[] = array('title' => 'הצטרף למנויים', 'url' => get_permalink($membership_page->ID), 'icon' => '⭐');
            }
            
        } elseif ($page_slug == 'קורס') {
            // Course page
            $membership_page = get_page_by_path('מנויים');
            $crossword_page = get_page_by_path('תשבץ-שבועי');
            $examples_page = get_page_by_path('תשבצים-לדוגמה');
            $all_crosswords_page = get_page_by_path('תשבצים');
            
            if ($membership_page && $membership_page->ID != $current_page_id) {
                $links[] = array('title' => 'הצטרף למנויים', 'url' => get_permalink($membership_page->ID), 'icon' => '⭐');
            }
            // Add course registration link - always show if course page exists (not current page)
            if ($course_page && $course_page->ID && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'הרשמה לקורס', 'url' => get_permalink($course_page->ID), 'icon' => '✍️');
            }
            if ($crossword_page && $crossword_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבץ שבועי', 'url' => get_permalink($crossword_page->ID), 'icon' => '📅');
            }
            if ($examples_page && $examples_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבצים לדוגמה', 'url' => get_permalink($examples_page->ID), 'icon' => '🎯');
            }
            if ($all_crosswords_page && $all_crosswords_page->ID != $current_page_id) {
                $links[] = array('title' => 'כל התשבצים', 'url' => get_permalink($all_crosswords_page->ID), 'icon' => '📋');
            }
            
        } elseif ($page_slug == 'מנויים') {
            // Membership page
            $course_page = get_page_by_path('קורס-תשבצי-היגיון-אונליין');
            $crossword_page = get_page_by_path('תשבץ-שבועי');
            $examples_page = get_page_by_path('תשבצים-לדוגמה');
            $all_crosswords_page = get_page_by_path('תשבצים');
            
            if ($course_page && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'קורס מקוון', 'url' => get_permalink($course_page->ID), 'icon' => '🎓');
            }
            // Add course registration link - always show if course page exists (not current page)
            if ($course_page && $course_page->ID && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'הרשמה לקורס', 'url' => get_permalink($course_page->ID), 'icon' => '✍️');
            }
            if ($crossword_page && $crossword_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבץ שבועי', 'url' => get_permalink($crossword_page->ID), 'icon' => '📅');
            }
            if ($examples_page && $examples_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבצים לדוגמה', 'url' => get_permalink($examples_page->ID), 'icon' => '🎯');
            }
            if ($all_crosswords_page && $all_crosswords_page->ID != $current_page_id) {
                $links[] = array('title' => 'כל התשבצים', 'url' => get_permalink($all_crosswords_page->ID), 'icon' => '📋');
            }
        } else {
            // Generic pages - add common links (but not the current page)
            $crossword_page = get_page_by_path('תשבץ-שבועי');
            $course_page = get_page_by_path('קורס-תשבצי-היגיון-אונליין');
            $membership_page = get_page_by_path('מנויים');
            $games_page = get_page_by_path('משחקי-מחשבה');
            $examples_page = get_page_by_path('תשבצים-לדוגמה');
            $all_crosswords_page = get_page_by_path('תשבצים');
            
            $current_page_id = get_queried_object()->ID;
            
            if ($crossword_page && $crossword_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבץ שבועי', 'url' => get_permalink($crossword_page->ID), 'icon' => '📅');
            }
            if ($course_page && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'קורס מקוון', 'url' => get_permalink($course_page->ID), 'icon' => '🎓');
            }
            // Add course registration link - always show if course page exists (not current page)
            if ($course_page && $course_page->ID && $course_page->ID != $current_page_id) {
                $links[] = array('title' => 'הרשמה לקורס', 'url' => get_permalink($course_page->ID), 'icon' => '✍️');
            }
            if ($membership_page && $membership_page->ID != $current_page_id) {
                $links[] = array('title' => 'הצטרף למנויים', 'url' => get_permalink($membership_page->ID), 'icon' => '⭐');
            }
            if ($games_page && $games_page->ID != $current_page_id) {
                $links[] = array('title' => 'משחקי מחשבה', 'url' => get_permalink($games_page->ID), 'icon' => '🎮');
            }
            if ($examples_page && $examples_page->ID != $current_page_id) {
                $links[] = array('title' => 'תשבצים לדוגמה', 'url' => get_permalink($examples_page->ID), 'icon' => '🎯');
            }
            if ($all_crosswords_page && $all_crosswords_page->ID != $current_page_id) {
                $links[] = array('title' => 'כל התשבצים', 'url' => get_permalink($all_crosswords_page->ID), 'icon' => '📋');
            }
        }
    }
    
    // Only add home link if no other links were found
    if (empty($links) && !is_front_page()) {
        $links[] = array(
            'title' => 'דף הבית',
            'url' => home_url('/'),
            'icon' => '🏠'
        );
    }
    
    if (empty($links)) {
        return;
    }
    
    // Determine section title based on page type
    $section_title = 'גלו עוד';
    $section_description = 'עמודים ותוכן מעניין נוסף באתר';
    
    if (is_singular('crossword')) {
        $section_title = 'גלו עוד תשבצים';
        $section_description = 'תשבצים נוספים ותוכן מעניין';
    } elseif (is_page()) {
        $page_slug = get_queried_object()->post_name;
        if ($page_slug == 'משחקי-מחשבה') {
            $section_title = 'גלו עוד באתר';
            $section_description = 'תשבצים, קורסים ומנויים';
        } elseif (strpos($page_slug, 'משחק') !== false || strpos($page_slug, 'game') !== false || 
            $page_slug == 'מילת-היום' || $page_slug == 'connections' || $page_slug == 'sudoku') {
            $section_title = 'גלו עוד משחקים';
            $section_description = 'משחקים נוספים ותוכן מעניין';
        } elseif ($page_slug == 'קורס') {
            $section_title = 'גלו אפשרויות נוספות';
            $section_description = 'מנויים, תשבצים ותוכן נוסף';
        } elseif ($page_slug == 'מנויים') {
            $section_title = 'גלו אפשרויות נוספות';
            $section_description = 'קורסים, תשבצים ותוכן נוסף';
        } elseif ($page_slug == 'תשבץ-שבועי') {
            $section_title = 'גלו עוד תשבצים';
            $section_description = 'תשבצים נוספים, קורסים ומנויים';
        } else {
            $section_title = 'גלו עוד באתר';
            $section_description = 'תשבצים, משחקים, קורסים ומנויים';
        }
    }
    
    echo '<div class="internal-navigation-links">';
    echo '<h3 class="nav-links-title">' . esc_html($section_title) . '</h3>';
    echo '<p class="nav-links-description">' . esc_html($section_description) . '</p>';
    echo '<ul class="nav-links-list">';
    foreach ($links as $link) {
        echo '<li class="nav-link-item">';
        echo '<a href="' . esc_url($link['url']) . '" class="nav-link">';
        if (!empty($link['icon'])) {
            echo '<span class="nav-link-icon">' . esc_html($link['icon']) . '</span>';
        }
        echo '<span class="nav-link-text">' . esc_html($link['title']) . '</span>';
        echo '<span class="nav-link-arrow">←</span>';
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

/**
 * Hook internal navigation links to content
 * DISABLED - Use [internal_links] shortcode in Elementor instead to avoid duplication
 */
function add_internal_navigation_to_content($content) {
    // Disabled - use shortcode instead
    return $content;
}
// add_filter('the_content', 'add_internal_navigation_to_content', 20); // DISABLED

// Hook into Elementor's content output for better compatibility
// DISABLED - Use [internal_links] shortcode in Elementor instead to avoid duplication
function add_internal_navigation_hooks() {
    // Disabled - use shortcode instead
}
// add_action('wp', 'add_internal_navigation_hooks'); // DISABLED

// Removed problematic Elementor hook - use shortcode instead

// Also available as shortcode - USE THIS IN ELEMENTOR!
add_shortcode('internal_links', 'display_internal_navigation_links');

/**
 * Shortcode to check differences between users
 * Usage: [check_user_differences]
 * Only accessible to administrators
 */
add_shortcode('check_user_differences', function() {
    if (!current_user_can('administrator')) {
        return '<p>רק מנהלים יכולים לגשת למידע זה.</p>';
    }
    
    $users = get_users(array(
        'orderby' => 'user_registered',
        'order' => 'ASC'
    ));
    
    $output = '<div style="font-family: monospace; padding: 20px; background: #f5f5f5;">';
    $output .= '<h2>בדיקת הבדלים בין יוזרים</h2>';
    $output .= '<p>סה"כ יוזרים: ' . count($users) . '</p>';
    
    $user_data = array();
    
    foreach ($users as $user) {
        $user_id = $user->ID;
        
        $data = array(
            'ID' => $user_id,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_registered' => $user->user_registered,
            'display_name' => $user->display_name,
        );
        
        // Check PMPro membership
        if (function_exists('pmpro_getMembershipLevelForUser')) {
            $level = pmpro_getMembershipLevelForUser($user_id);
            $data['pmpro_level'] = $level ? $level->name : 'NO MEMBERSHIP';
            $data['pmpro_level_id'] = $level ? $level->id : null;
            $data['pmpro_startdate'] = $level ? date('Y-m-d H:i:s', $level->startdate) : null;
            $data['pmpro_enddate'] = $level ? ($level->enddate ? date('Y-m-d H:i:s', $level->enddate) : 'LIFETIME') : null;
        }
        
        if (function_exists('pmpro_hasMembershipLevel')) {
            $data['pmpro_has_membership'] = pmpro_hasMembershipLevel(null, $user_id) ? 'YES' : 'NO';
        }
        
        // Get important meta
        $important_meta = array(
            'pmpro_old_level',
            'pmpro_old_level_id',
            'pmpro_visits',
            'pmpro_last_activity',
            'wpum_last_login',
            'wpum_last_activity',
            'session_tokens',
        );
        
        $data['important_meta'] = array();
        foreach ($important_meta as $meta_key) {
            $value = get_user_meta($user_id, $meta_key, true);
            if ($value !== false && $value !== '') {
                $data['important_meta'][$meta_key] = is_array($value) ? 'ARRAY(' . count($value) . ')' : substr(strval($value), 0, 100);
            }
        }
        
        // Check capabilities
        $user_obj = new WP_User($user_id);
        $data['roles'] = $user_obj->roles;
        
        $user_data[] = $data;
    }
    
    // Display results
    foreach ($user_data as $data) {
        $output .= '<div style="border: 1px solid #ddd; margin: 10px 0; padding: 10px; background: white;">';
        $output .= '<h3>יוזר ID: ' . esc_html($data['ID']) . ' (' . esc_html($data['user_login']) . ')</h3>';
        $output .= '<p><strong>נרשם:</strong> ' . esc_html($data['user_registered']) . '</p>';
        $output .= '<p><strong>Email:</strong> ' . esc_html($data['user_email']) . '</p>';
        
        if (isset($data['pmpro_has_membership'])) {
            $color = $data['pmpro_has_membership'] === 'YES' ? 'green' : 'red';
            $output .= '<p><strong>PMPro Membership:</strong> <span style="color: ' . $color . ';">' . esc_html($data['pmpro_has_membership']) . '</span></p>';
        }
        
        if (isset($data['pmpro_level'])) {
            $output .= '<p><strong>PMPro Level:</strong> ' . esc_html($data['pmpro_level']);
            if ($data['pmpro_level_id']) {
                $output .= ' (ID: ' . esc_html($data['pmpro_level_id']) . ')';
            }
            $output .= '</p>';
            if ($data['pmpro_startdate']) {
                $output .= '<p><strong>Start:</strong> ' . esc_html($data['pmpro_startdate']) . '</p>';
            }
            if ($data['pmpro_enddate']) {
                $output .= '<p><strong>End:</strong> ' . esc_html($data['pmpro_enddate']) . '</p>';
            }
        }
        
        $output .= '<p><strong>Roles:</strong> ' . esc_html(implode(', ', $data['roles'])) . '</p>';
        
        if (!empty($data['important_meta'])) {
            $output .= '<p><strong>Important Meta:</strong></p><ul>';
            foreach ($data['important_meta'] as $key => $value) {
                $output .= '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</li>';
            }
            $output .= '</ul>';
        }
        
        $output .= '</div>';
    }
    
    // Find differences
    $output .= '<h2>הבדלים עיקריים</h2>';
    
    // Group by registration date
    $old_users = array();
    $new_users = array();
    $cutoff_date = '2024-01-01'; // Adjust this date!
    
    foreach ($user_data as $data) {
        if ($data['user_registered'] >= $cutoff_date) {
            $new_users[] = $data;
        } else {
            $old_users[] = $data;
        }
    }
    
    $output .= '<p>יוזרים ישנים (לפני ' . $cutoff_date . '): ' . count($old_users) . '</p>';
    $output .= '<p>יוזרים חדשים (אחרי ' . $cutoff_date . '): ' . count($new_users) . '</p>';
    
    // Compare PMPro membership
    $old_with_membership = array_filter($old_users, function($u) { 
        return isset($u['pmpro_has_membership']) && $u['pmpro_has_membership'] === 'YES'; 
    });
    $new_with_membership = array_filter($new_users, function($u) { 
        return isset($u['pmpro_has_membership']) && $u['pmpro_has_membership'] === 'YES'; 
    });
    
    $output .= '<p>יוזרים ישנים עם membership: ' . count($old_with_membership) . '</p>';
    $output .= '<p>יוזרים חדשים עם membership: ' . count($new_with_membership) . '</p>';
    
    // Check for meta differences
    $old_meta_keys = array();
    $new_meta_keys = array();
    
    foreach ($old_users as $user) {
        foreach ($user['important_meta'] as $key => $value) {
            if (!in_array($key, $old_meta_keys)) {
                $old_meta_keys[] = $key;
            }
        }
    }
    
    foreach ($new_users as $user) {
        foreach ($user['important_meta'] as $key => $value) {
            if (!in_array($key, $new_meta_keys)) {
                $new_meta_keys[] = $key;
            }
        }
    }
    
    $only_in_new = array_diff($new_meta_keys, $old_meta_keys);
    $only_in_old = array_diff($old_meta_keys, $new_meta_keys);
    
    if (!empty($only_in_new)) {
        $output .= '<p style="color: red;"><strong>⚠️ Meta keys רק בחדשים:</strong> ' . esc_html(implode(', ', $only_in_new)) . '</p>';
    }
    
    if (!empty($only_in_old)) {
        $output .= '<p style="color: red;"><strong>⚠️ Meta keys רק בישנים:</strong> ' . esc_html(implode(', ', $only_in_old)) . '</p>';
    }
    
    $output .= '</div>';
    
    return $output;
});


