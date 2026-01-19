<?php
/**
 * Plugin Name: HB Check User Differences
 * Description: בדיקת הבדלים בין יוזרים - רק למנהלים
 * Version: 1.0.0
 * Author: HB
 */

if (!defined('ABSPATH')) {
    exit;
}

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
            
            // Check if startdate is in the future (might cause issues)
            if ($level && $level->startdate) {
                $start_timestamp = $level->startdate;
                $now = current_time('timestamp');
                $data['startdate_is_future'] = $start_timestamp > $now ? 'YES (⚠️)' : 'NO';
                $data['startdate_days_from_now'] = round(($start_timestamp - $now) / (60 * 60 * 24));
            }
        }
        
        if (function_exists('pmpro_hasMembershipLevel')) {
            $data['pmpro_has_membership'] = pmpro_hasMembershipLevel(null, $user_id) ? 'YES' : 'NO';
            // Check specific levels
            $data['pmpro_has_level_2'] = pmpro_hasMembershipLevel(2, $user_id) ? 'YES' : 'NO';
            $data['pmpro_has_level_8'] = pmpro_hasMembershipLevel(8, $user_id) ? 'YES' : 'NO';
            $data['pmpro_has_level_2_or_8'] = pmpro_hasMembershipLevel([2, 8], $user_id) ? 'YES' : 'NO';
        }
        
        // Check WordPress login status
        $data['wp_is_logged_in'] = is_user_logged_in() ? 'YES' : 'NO';
        
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
                if ($meta_key === 'session_tokens' && is_array($value)) {
                    // Show session token details
                    $session_info = array();
                    foreach ($value as $token => $session) {
                        $login_time = isset($session['login']) ? date('Y-m-d H:i:s', $session['login']) : 'N/A';
                        $expiration = isset($session['expiration']) ? date('Y-m-d H:i:s', $session['expiration']) : 'N/A';
                        $session_info[] = "Token: " . substr($token, 0, 8) . "... Login: $login_time, Expires: $expiration";
                    }
                    $data['important_meta'][$meta_key] = 'ARRAY(' . count($value) . ') - ' . implode(' | ', array_slice($session_info, 0, 3));
                } else {
                    $data['important_meta'][$meta_key] = is_array($value) ? 'ARRAY(' . count($value) . ')' : substr(strval($value), 0, 100);
                }
            }
        }
        
        // Check all user meta to find differences
        $all_meta = get_user_meta($user_id);
        $data['all_meta_keys'] = array_keys($all_meta);
        $data['meta_count'] = count($all_meta);
        
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
        
        if (isset($data['wp_is_logged_in'])) {
            $color = $data['wp_is_logged_in'] === 'YES' ? 'green' : 'red';
            $output .= '<p><strong>WordPress Logged In:</strong> <span style="color: ' . $color . ';">' . esc_html($data['wp_is_logged_in']) . '</span></p>';
        }
        
        if (isset($data['pmpro_has_membership'])) {
            $color = $data['pmpro_has_membership'] === 'YES' ? 'green' : 'red';
            $output .= '<p><strong>PMPro Membership:</strong> <span style="color: ' . $color . ';">' . esc_html($data['pmpro_has_membership']) . '</span></p>';
        }
        
        if (isset($data['pmpro_has_level_2_or_8'])) {
            $color = $data['pmpro_has_level_2_or_8'] === 'YES' ? 'green' : 'red';
            $output .= '<p><strong>PMPro Level 2 or 8:</strong> <span style="color: ' . $color . ';">' . esc_html($data['pmpro_has_level_2_or_8']) . '</span></p>';
            $output .= '<p style="font-size: 12px; color: #666;">Level 2: ' . esc_html($data['pmpro_has_level_2'] ?? 'N/A') . ' | Level 8: ' . esc_html($data['pmpro_has_level_8'] ?? 'N/A') . '</p>';
        }
        
        if (isset($data['pmpro_level'])) {
            $output .= '<p><strong>PMPro Level:</strong> ' . esc_html($data['pmpro_level']);
            if ($data['pmpro_level_id']) {
                $output .= ' (ID: ' . esc_html($data['pmpro_level_id']) . ')';
            }
            $output .= '</p>';
            if ($data['pmpro_startdate']) {
                $output .= '<p><strong>Start:</strong> ' . esc_html($data['pmpro_startdate']);
                if (isset($data['startdate_is_future']) && $data['startdate_is_future'] === 'YES (⚠️)') {
                    $output .= ' <span style="color: red; font-weight: bold;">⚠️ FUTURE DATE!</span>';
                }
                if (isset($data['startdate_days_from_now'])) {
                    $output .= ' (' . esc_html($data['startdate_days_from_now']) . ' days from now)';
                }
                $output .= '</p>';
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
        
        if (isset($data['meta_count'])) {
            $output .= '<p><strong>Total Meta Fields:</strong> ' . esc_html($data['meta_count']) . '</p>';
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
