<?php
/**
 * Plugin Name: HB Spark Test (Minimal Test)
 * Description: Minimal version for debugging
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

// Only the shortcode - nothing else
add_shortcode('hb_spark_test', function($atts) {
  return '<div><p>Test message - if you see this, the shortcode works.</p></div>';
});
