<?php
/**
 * Plugin Name: HB Testimonials
 * Description: Shortcodes להצגת המלצות לקוחות באתר. כולל 4 shortcodes: full, course, home, subscription.
 * Version: 1.0.0
 * Author: Higayon Barie
 * License: GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hb_get_testimonials_html' ) ) {
	/**
	 * פונקציית עזר להצגת המלצות
	 *
	 * @param string $mode 'full' | 'course' | 'home' | 'subscription'
	 * @return string HTML
	 * 
	 * שימוש:
	 * - עמוד הבית: [hb_testimonials_home]
	 * - עמוד הקורס: [hb_testimonials_course]
	 * - עמוד המנוי: [hb_testimonials_subscription]
	 * - עמוד "לקוחות ממליצים": [hb_testimonials_full]
	 */
	function hb_get_testimonials_html( $mode ) {
		static $styles_printed = false;
		static $scripts_printed = false;

		// מערך ההמלצות בסדר הנדרש: ח.ג, רונית, ורד
		$testimonials_data = [
			'hg' => [
				'id' => 'hg',
				'name' => 'ח.ג.',
				'full_text' => 'היי עדי היקר
תודה על הקורס לתשבצי הגיון שפתח לי עולם חדש מאתגר.
אני חובבת תשבצים . אבל שניסיתי לפתור תשבצי הגיון
נתקעתי וחשתי תסכול.
רק אחרי הקורס הדיגיטלי ב"הגיון בריא" הבנתי את צורת החשיבה
שיש בתשבצי הגיון ו"נתפסתי".
הקורס מלמד בצורה ידידותית וקלה עם הרבה דוגמאות והסברים.
עדיין לא קל לי לפתור תשבצי הגיון אבל מאתגר ומהנה , וההמשכיות
איתך של "תשבץ הגיון שבועי" עם הסברים להבנת ההגיון מהווה תירגול
מצויין לידע הרב שאתה מקנה.
ממליצה בחום לכל מי שרוצה להפעיל את הראש , לחשוב גם בצורה אחרת
בדרך מהנה ומאתגרת . אני התמכרתי!

ח.ג.',
				'short_quote' => 'רק אחרי הקורס הדיגיטלי ב"הגיון בריא" הבנתי את צורת החשיבה שיש בתשבצי הגיון ונתפסתי. אני התמכרתי!',
			],
			'ronit' => [
				'id' => 'ronit',
				'name' => 'סבתא רונית פלג',
				'full_text' => 'אני סבתא שפותרת תשבצים רגילים על בסיס יומיומי. 
אבל, בכל פעם שניסיתי לפתור תשבץ הגיון, נתקלתי בעולם זר, מוזר, בלתי ניתן לפענוח, ומאוד מסקרן…לפעמים ניסיתי והתאכזבתי בכל פעם מחדש.
והנה, נקרתה בפני הזדמנות להשתתף בקורס בן ארבעה מפגשים שהתקיים בזום. נרשמתי…
בקורס השתתפו מבוגרים כמוני ולרובנו לא היה מושג כיצד ניגשים לנושא.
המדריך היה עדי עופר,  שבענווה, בשלווה, בתבונה, ברגישות ובסבלנות אינסופית ריכז קבוצה גדולה מאוד של אנשים שהמטירו שאלות בלי סוף.( לא מבינה איך הצליח…קוסם!)
באומנות ובצעדים קטנים, אך מאוד נחושים, בדרך מאוד מאורגנת וברורה, פרש עדי לפנינו את סודות תשבצי ההגיון ולאט לאט פתח בפנינו את השער לעולם הקסום והנפלא הזה. התהליך היה מובנה, ברור, מאורגן להפליא ומרתק.
מאז, בדיוק ובנאמנות, אני מתחדשת מידי יום שישי בתשבץ חדש ומאתגר שעדי מפרסם ושאני פותרת בשקיקה ובהנאה רבה.
בכל תשבץ אני פוגשת הגדרות שנונות, מאתגרות, לעיתים מצחיקות ותמיד, תמיד מהנות.
ממליצה בחום רב,
סבתא רונית פלג',
				'short_quote' => 'מאז, בדיוק ובנאמנות, אני מתחדשת מידי יום שישי בתשבץ חדש ומאתגר שעדי מפרסם ושאני פותרת בשקיקה ובהנאה רבה.',
			],
			'vered' => [
				'id' => 'vered',
				'name' => 'ורד',
				'full_text' => 'הגעתי לקורס בלי שום ידע על תשבצי הגיון. גם כשראיתי תשבץ פתור לא הבנתי איך הפתרון קשור לחידה.
ואז הגעתי לקורס שהעביר עדי בצורה ברורה, מדויקת ובליווי שפע דוגמאות.
בסיומו של הקורס יכולתי כבר להבין את הפתרונות של רוב התשבצים.
קרוב לסיום הקורס נפתח החוג השבועי, אליו הצטרפתי בשמחה. משבוע לשבוע גדל החלק שפתרתי בתשבץ.
היום, לאחר כ- 4 שנים שהחוג מתקיים, יכולה להעיד שמצפה מדי שבוע ליום ו (פרסום תשבץ חדש) וליום ד, שעת המפגש בה אנו פותרים יחדיו,  בניהולו של עדי ובאווירה טובה.',
				'short_quote' => 'היום, לאחר כ- 4 שנים שהחוג מתקיים, יכולה להעיד שמצפה מדי שבוע ליום ו (פרסום תשבץ חדש) וליום ד, שעת המפגש בה אנו פותרים יחדיו, בניהולו של עדי ובאווירה טובה.',
			],
		];

		$titles = [
			'full' => 'לקוחות ממליצים',
			'course' => 'מה אומרים בוגרי הקורס?',
			'home' => 'מה אומרים על הקורס ותשבצי ההיגיון?',
			'subscription' => 'מה אומרים על התשבץ השבועי והחוג?',
		];

		$title = isset( $titles[ $mode ] ) ? $titles[ $mode ] : 'המלצות';

		// הוספת CSS רק בפעם הראשונה
		$html = '';
		if ( ! $styles_printed ) {
			$styles_printed = true;
			$html .= '<style>
.hb-testimonials-section {
	margin: 4rem 0;
	padding: 2.5rem 0;
	position: relative;
}

.hb-testimonials-section::before {
	content: "";
	position: absolute;
	top: 0;
	right: 0;
	left: 0;
	height: 1px;
	background: linear-gradient(to left, transparent, rgba(0,0,0,0.08) 20%, rgba(0,0,0,0.08) 80%, transparent);
}

.hb-testimonials-inner {
	max-width: 1100px;
	margin: 0 auto;
	padding: 0 1.5rem;
}

.hb-testimonials-title {
	text-align: center;
	margin: 0 auto 3rem;
	font-weight: 800;
	font-size: 2.5rem;
	position: relative;
	padding: 0 3rem;
	line-height: 1.2;
	color: #1a1a1a;
	letter-spacing: -0.02em;
	max-width: 900px;
}

.hb-testimonials-title::before {
	content: """;
	position: absolute;
	right: 0;
	top: -0.15em;
	font-size: 4.5rem;
	line-height: 1;
	opacity: 0.12;
	font-family: Georgia, serif;
	color: #4a90e2;
}

.hb-testimonials-title::after {
	content: "";
	position: absolute;
	bottom: -1rem;
	right: 50%;
	transform: translateX(50%);
	width: 60px;
	height: 4px;
	background: linear-gradient(to right, transparent, #4a90e2, transparent);
	border-radius: 2px;
}

.hb-testimonials-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 1.75rem;
	animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.hb-testimonial-card {
	background: #ffffff;
	border-radius: 16px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
	padding: 1.6rem 1.8rem;
	text-align: right;
	display: flex;
	flex-direction: column;
	gap: 0.85rem;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	border: 1px solid rgba(0,0,0,0.04);
	position: relative;
	overflow: hidden;
}

.hb-testimonial-card::before {
	content: "";
	position: absolute;
	top: 0;
	right: 0;
	width: 4px;
	height: 100%;
	background: linear-gradient(to bottom, #4a90e2, #7b68ee);
	opacity: 0;
	transition: opacity 0.3s ease;
}

.hb-testimonial-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 8px 24px rgba(0,0,0,0.1), 0 12px 32px rgba(0,0,0,0.06);
	border-color: rgba(0,0,0,0.08);
}

.hb-testimonial-card:hover::before {
	opacity: 1;
}

.hb-testimonial-short {
	font-size: 1em;
	line-height: 1.7;
	font-weight: 500;
	position: relative;
	padding-right: 1.2rem;
}

.hb-testimonial-short::before {
	content: """;
	position: absolute;
	right: 0;
	top: -0.2em;
	font-size: 1.8em;
	line-height: 1;
	opacity: 0.2;
	font-family: Georgia, serif;
}

.hb-testimonial-full {
	font-size: 0.95em;
	line-height: 1.75;
	animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
	from {
		opacity: 0;
		max-height: 0;
	}
	to {
		opacity: 1;
		max-height: 2000px;
	}
}

.hb-testimonial-full p {
	margin: 0 0 1rem 0;
}

.hb-testimonial-full p:last-child {
	margin-bottom: 0;
}

.hb-testimonial-name {
	margin-top: 0.75rem;
	font-weight: 600;
	font-size: 0.95em;
	opacity: 0.85;
	position: relative;
	padding-top: 0.75rem;
}

.hb-testimonial-name::before {
	content: "";
	position: absolute;
	top: 0;
	right: 0;
	width: 40px;
	height: 2px;
	background: linear-gradient(to left, #4a90e2, transparent);
	border-radius: 2px;
}

.hb-testimonial-toggle {
	align-self: flex-start;
	font-size: 0.85em;
	padding: 0;
	border: none;
	background: transparent;
	cursor: pointer;
	transition: all 0.2s ease;
	font-weight: 400;
	margin-top: 0.5rem;
	color: #4a90e2;
	text-decoration: none;
	position: relative;
	display: inline-flex;
	align-items: center;
	gap: 0.3rem;
}

.hb-testimonial-toggle::after {
	content: "↓";
	font-size: 0.9em;
	transition: transform 0.2s ease;
	display: inline-block;
}

.hb-testimonial-toggle:hover {
	color: #357abd;
	text-decoration: underline;
}

.hb-testimonial-toggle:hover::after {
	transform: translateY(2px);
}

.hb-testimonial-card[data-expanded="true"] .hb-testimonial-toggle::after {
	content: "↑";
}

@media (max-width: 768px) {
	.hb-testimonials-section {
		margin: 3rem 0;
		padding: 2rem 0;
	}
	
	.hb-testimonials-inner {
		padding: 0 1rem;
	}
	
	.hb-testimonials-title {
		font-size: 1.8rem;
		margin-bottom: 2.5rem;
		padding: 0 2rem;
		text-align: center;
	}

	.hb-testimonials-title::before {
		font-size: 3rem;
		right: 0;
	}
	
	.hb-testimonials-grid {
		grid-template-columns: 1fr;
		gap: 1.25rem;
	}
	
	.hb-testimonial-card {
		padding: 1.4rem 1.6rem;
	}
}
</style>';
		}

		// הוספת JavaScript רק בפעם הראשונה
		if ( ! $scripts_printed ) {
			$scripts_printed = true;
			$html .= '<script>
document.addEventListener("click", function (e) {
	var btn = e.target.closest(".hb-testimonial-toggle");
	if (!btn) return;

	var card = btn.closest("[data-hb-testimonial]");
	if (!card) return;

	var full = card.querySelector(".hb-testimonial-full");
	if (!full) return;

	var isOpen = !full.hasAttribute("hidden");

	if (isOpen) {
		full.setAttribute("hidden", "hidden");
		card.removeAttribute("data-expanded");
		btn.textContent = "לקריאת ההמלצה המלאה";
	} else {
		full.removeAttribute("hidden");
		card.setAttribute("data-expanded", "true");
		btn.textContent = "סגירת ההמלצה";
	}
});
</script>';
		}

		$html .= '<section class="hb-testimonials-section" dir="rtl">';
		$html .= '<div class="hb-testimonials-inner">';
		$html .= '<h2 class="hb-testimonials-title">' . esc_html( $title ) . '</h2>';
		$html .= '<div class="hb-testimonials-grid">';

		// לולאה על ההמלצות בסדר הנדרש (ח.ג, רונית, ורד)
		foreach ( $testimonials_data as $testimonial ) {
			$html .= '<article class="hb-testimonial-card" data-hb-testimonial>';
			
			// משפט קצר (תמיד מוצג)
			$html .= '<div class="hb-testimonial-short">';
			$html .= esc_html( $testimonial['short_quote'] );
			$html .= '</div>';

			// כפתור פתיחה/סגירה
			$html .= '<button type="button" class="hb-testimonial-toggle">לקריאת ההמלצה המלאה</button>';

			// טקסט מלא (נסתר כברירת מחדל)
			$html .= '<div class="hb-testimonial-full" hidden>';
			$text = $testimonial['full_text'];
			$paragraphs = array_filter( array_map( 'trim', explode( "\n", $text ) ) );
			foreach ( $paragraphs as $para ) {
				if ( ! empty( $para ) ) {
					$html .= '<p>' . esc_html( $para ) . '</p>';
				}
			}
			$html .= '</div>';

			// שם הממליצה
			$html .= '<div class="hb-testimonial-name">' . esc_html( $testimonial['name'] ) . '</div>';
			$html .= '</article>';
		}

		$html .= '</div>';
		$html .= '</div>';
		$html .= '</section>';

		return $html;
	}
}

// הגדרת shortcodes
if ( ! function_exists( 'hb_testimonials_full_shortcode' ) ) {
	function hb_testimonials_full_shortcode( $atts ) {
		return hb_get_testimonials_html( 'full' );
	}
	add_shortcode( 'hb_testimonials_full', 'hb_testimonials_full_shortcode' );
}

if ( ! function_exists( 'hb_testimonials_course_shortcode' ) ) {
	function hb_testimonials_course_shortcode( $atts ) {
		return hb_get_testimonials_html( 'course' );
	}
	add_shortcode( 'hb_testimonials_course', 'hb_testimonials_course_shortcode' );
}

if ( ! function_exists( 'hb_testimonials_home_shortcode' ) ) {
	function hb_testimonials_home_shortcode( $atts ) {
		return hb_get_testimonials_html( 'home' );
	}
	add_shortcode( 'hb_testimonials_home', 'hb_testimonials_home_shortcode' );
}

if ( ! function_exists( 'hb_testimonials_subscription_shortcode' ) ) {
	function hb_testimonials_subscription_shortcode( $atts ) {
		return hb_get_testimonials_html( 'subscription' );
	}
	add_shortcode( 'hb_testimonials_subscription', 'hb_testimonials_subscription_shortcode' );
}



