<?php
/**
 * Script to check differences between users
 * Run this from WordPress root: php check-user-differences.php
 */

require_once('wp-load.php');

// Get all users
$users = get_users(array(
    'orderby' => 'user_registered',
    'order' => 'ASC'
));

echo "=== בדיקת הבדלים בין יוזרים ===\n\n";

$user_data = array();

foreach ($users as $user) {
    $user_id = $user->ID;
    
    // Basic user info
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
    
    // Get all user meta
    $all_meta = get_user_meta($user_id);
    $data['meta_count'] = count($all_meta);
    
    // Check specific meta keys that might be relevant
    $important_meta = array(
        'pmpro_old_level',
        'pmpro_old_level_id',
        'pmpro_visits',
        'pmpro_last_activity',
        'wpum_last_login',
        'wpum_last_activity',
        'session_tokens',
        'wp_capabilities',
        'wp_user_level',
    );
    
    $data['important_meta'] = array();
    foreach ($important_meta as $meta_key) {
        $value = get_user_meta($user_id, $meta_key, true);
        if ($value !== false && $value !== '') {
            $data['important_meta'][$meta_key] = is_array($value) ? 'ARRAY(' . count($value) . ')' : substr(strval($value), 0, 50);
        }
    }
    
    // Check capabilities
    $user_obj = new WP_User($user_id);
    $data['roles'] = $user_obj->roles;
    $data['capabilities_count'] = count($user_obj->allcaps);
    
    $user_data[] = $data;
}

// Display results
echo "סה\"כ יוזרים: " . count($user_data) . "\n\n";

foreach ($user_data as $data) {
    echo "--- יוזר ID: {$data['ID']} ({$data['user_login']}) ---\n";
    echo "נרשם: {$data['user_registered']}\n";
    echo "Email: {$data['user_email']}\n";
    
    if (isset($data['pmpro_has_membership'])) {
        echo "PMPro Membership: {$data['pmpro_has_membership']}\n";
    }
    
    if (isset($data['pmpro_level'])) {
        echo "PMPro Level: {$data['pmpro_level']} (ID: {$data['pmpro_level_id']})\n";
        if ($data['pmpro_startdate']) {
            echo "  Start: {$data['pmpro_startdate']}\n";
        }
        if ($data['pmpro_enddate']) {
            echo "  End: {$data['pmpro_enddate']}\n";
        }
    }
    
    echo "Roles: " . implode(', ', $data['roles']) . "\n";
    echo "Capabilities: {$data['capabilities_count']}\n";
    echo "Meta fields: {$data['meta_count']}\n";
    
    if (!empty($data['important_meta'])) {
        echo "Important Meta:\n";
        foreach ($data['important_meta'] as $key => $value) {
            echo "  - {$key}: {$value}\n";
        }
    }
    
    echo "\n";
}

// Find differences
echo "\n=== הבדלים עיקריים ===\n\n";

// Group by registration date (old vs new)
$old_users = array();
$new_users = array();

// Assuming "new" users are registered after a certain date
// You can adjust this date based on when the working user was created
$cutoff_date = '2024-01-01'; // Adjust this!

foreach ($user_data as $data) {
    if ($data['user_registered'] >= $cutoff_date) {
        $new_users[] = $data;
    } else {
        $old_users[] = $data;
    }
}

echo "יוזרים ישנים (לפני {$cutoff_date}): " . count($old_users) . "\n";
echo "יוזרים חדשים (אחרי {$cutoff_date}): " . count($new_users) . "\n\n";

// Compare PMPro membership status
$old_with_membership = array_filter($old_users, function($u) { 
    return isset($u['pmpro_has_membership']) && $u['pmpro_has_membership'] === 'YES'; 
});
$new_with_membership = array_filter($new_users, function($u) { 
    return isset($u['pmpro_has_membership']) && $u['pmpro_has_membership'] === 'YES'; 
});

echo "יוזרים ישנים עם membership: " . count($old_with_membership) . "\n";
echo "יוזרים חדשים עם membership: " . count($new_with_membership) . "\n\n";

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

echo "Meta keys בישנים: " . implode(', ', $old_meta_keys) . "\n";
echo "Meta keys בחדשים: " . implode(', ', $new_meta_keys) . "\n";

$only_in_new = array_diff($new_meta_keys, $old_meta_keys);
$only_in_old = array_diff($old_meta_keys, $new_meta_keys);

if (!empty($only_in_new)) {
    echo "\n⚠️ Meta keys רק בחדשים: " . implode(', ', $only_in_new) . "\n";
}

if (!empty($only_in_old)) {
    echo "\n⚠️ Meta keys רק בישנים: " . implode(', ', $only_in_old) . "\n";
}

echo "\n=== סיום ===\n";
