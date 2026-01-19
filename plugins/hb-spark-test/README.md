# HB Spark Test Plugin

## Overview

The Spark Test component displays AI success rate analysis with a visual gauge, methodology information, and a lab results table. It includes paywall functionality for premium content.

**Version 1.1.0** now uses a Custom Post Type system for managing lab entries dynamically through the WordPress admin.

## Features

- **Custom Post Type**: "Lab Entry" post type for managing entries through WordPress admin
- **Custom Fields**: Three custom fields per entry:
  - AI Methodology
  - Failure Analysis
  - The Spark Solution
- **Spark Score Gauge**: Animated semi-circle SVG gauge showing AI success rate percentage
- **Methodology Box**: Clean card displaying AI models used (o1, Claude 3.5, Gemini 1.5 Pro)
- **Lab Table**: Responsive table with RTL support showing:
  - Clue (Post Title or Content)
  - AI Methodology (Monospace font)
  - Failure Analysis
  - Human Solution (with lightbulb/spark aesthetic)
- **Paywall Integration**: Blurred overlay after first N rows
- **Mobile Responsive**: Transforms table into card-based layout on mobile
- **Viewport Animation**: Gauge animates when scrolled into view

## Usage

### Managing Lab Entries

1. Go to **Lab Entries** in the WordPress admin menu
2. Click **Add New Lab Entry**
3. Enter the **Title** (this becomes the "Clue" in the table)
4. Fill in the custom fields:
   - **מתודולוגיית AI**: The AI model/methodology used
   - **ניתוח כשלון**: Why the AI failed
   - **הניצוץ (פתרון אנושי)**: The human solution/insight
5. Publish the entry

### Basic Shortcode

```
[hb_spark_test]
```

### With Custom Parameters

```
[hb_spark_test score="65" free_rows="3" limit="10"]
```

### Parameters

- `score` (default: "42"): AI success rate percentage (0-100)
- `free_rows` (default: "3"): Number of rows to show before paywall
- `limit` (default: "-1"): Maximum number of entries to display (-1 for all)

## Styling

The component uses BEM (Block Element Modifier) methodology:

- Block: `.spark-test`
- Elements: `.spark-test__gauge-wrapper`, `.spark-test__methodology`, `.spark-test__lab`, etc.
- Modifiers: `.spark-test__table-row--locked`, `.spark-test__card--locked`, etc.

## Design System Integration

- Primary Color: `#DB8A16` (orange/gold)
- Font: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif
- RTL Support: Full right-to-left support for Hebrew text
- Responsive Breakpoints: 768px (tablet), 480px (mobile)

## Customization

To customize the CTA button link, edit the `href` attribute in the shortcode output:

```php
<a href="#" class="spark-test__cta-button">
```

Replace `#` with your subscription/checkout page URL.

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires CSS Grid and Flexbox support
- Intersection Observer API for viewport animations


