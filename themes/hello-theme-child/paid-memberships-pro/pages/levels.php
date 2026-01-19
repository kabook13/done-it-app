<?php
/**
 * Template: Levels
 * Version: 3.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.1
 *
 * @author Paid Memberships Pro
 */
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

$pmpro_levels = pmpro_sort_levels_by_order( pmpro_getAllLevels(false, true) );
$pmpro_levels = apply_filters( 'pmpro_levels_array', $pmpro_levels );

$level_groups  = pmpro_get_level_groups_in_order();

?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
	<?php
		if ( $pmpro_msg ) {
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
			<?php
		}
	?>
	<section id="pmpro_levels" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_levels' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
			<?php
				foreach ( $level_groups as $level_group ) {
					$levels_in_group = pmpro_get_level_ids_for_group( $level_group->id );

					// The pmpro_levels_array filter is sometimes used to hide levels from the levels page.
					// Let's make sure that every level in the group should still be displayed.
					$levels_to_show_for_group = array();
					foreach ( $pmpro_levels as $level ) {
						if ( in_array( $level->id, $levels_in_group ) ) {
							$levels_to_show_for_group[] = $level;
						}
					}

					if ( empty( $levels_to_show_for_group ) ) {
						continue;
					}
					?>
					<div id="pmpro_level_group-<?php echo esc_attr( $level_group->id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card pmpro_level_group', 'pmpro_level_group-' . esc_attr( $level_group->id ) ) ); ?>">
						<?php
							if ( count( $level_groups ) > 1  ) {
								?>
								<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php echo esc_html( $level_group->name ); ?></h2>
								<?php
							}
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
							<?php
								if ( count( $level_groups ) > 1  ) {
									if ( ! empty( $level_group->allow_multiple_selections ) ) {
										?>
										<p><?php esc_html_e( 'ניתן לבחור מספר רמות מקבוצה זו.', 'paid-memberships-pro' ); ?></p>
										<?php
									} else {
										?>
										<p><?php esc_html_e( 'ניתן לבחור רק רמה אחת מקבוצה זו.', 'paid-memberships-pro' ); ?></p>
										<?php
									}
									?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
									<?php
								}
							?>
							<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_levels_table', 'pmpro_levels_table' ) ); ?>">
								<thead>
									<tr>
										<th><?php esc_html_e('רמה', 'paid-memberships-pro' );?></th>
										<th><?php esc_html_e('מחיר', 'paid-memberships-pro' );?></th>	
										<th><span class="screen-reader-text"><?php esc_html_e( 'פעולה', 'paid-memberships-pro' ); ?></span></th>
									</tr>
								</thead>
								<tbody>
									<?php	
									$count = 0;
									foreach($levels_to_show_for_group as $level)
									{
										$user_level = pmpro_getSpecificMembershipLevelForUser( $current_user->ID, $level->id );
										$has_level = ! empty( $user_level );

										// Build the selectors for the single level elements.
										$element_classes = array();
										$element_classes[] = 'pmpro_level';
										if ( $has_level ) {
											$element_classes[] = 'pmpro_level-current';
										}
										$element_class = implode( ' ', array_unique( $element_classes ) );
									?>
									<tr id="pmpro_level-<?php echo esc_attr( $level->id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( $element_class, 'pmpro_level-' . esc_attr( $level->id ) ) ); ?>">
										<th data-title="<?php esc_attr_e( 'רמה', 'paid-memberships-pro' ); ?>"><?php echo esc_html( $level->name ); ?>
                                            <?php 
                                                // קוד להדפסת התיאור של הדרגה
                                                if ( ! empty( $level->description ) ) {
                                                    ?>
                                                    <div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level-description' ) ); ?>">
                                                        <?php echo wp_kses_post( wpautop( $level->description ) ); ?>
                                                    </div>
                                                    <?php
                                                }
                                            ?>
                                        </th>
										<td data-title="<?php esc_attr_e( 'מחיר', 'paid-memberships-pro' ); ?>">
											<?php
												$cost_text = pmpro_getLevelCost( $level, true, true );
												if ( ! empty($cost_text ) ) {
													// תיקון טקסטים בעייתיים
													// הסרת המילה "עכשיו"
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
													$cost_text = preg_replace('/מנוי\s*:\s*\./i', 'מנוי.', $cost_text);
													$cost_text = preg_replace('/מנוי\s*\.\s*\./i', 'מנוי.', $cost_text);
													$cost_text = preg_replace('/מנוי\s*\.\s*\.\s*\./i', 'מנוי.', $cost_text);
													$cost_text = preg_replace('/מנוי\s*:\s*\.\s*\./i', 'מנוי.', $cost_text);
													// תיקון "המחיר עבור חשבון הוא"
													$cost_text = preg_replace('/המחיר עבור חשבון הוא\s*\.\s*עכשיו/i', 'המחיר עבור המנוי הוא', $cost_text);
													$cost_text = preg_replace('/המחיר עבור חשבון הוא/i', 'המחיר עבור המנוי הוא', $cost_text);
													// תיקון רווחים מיותרים בין אותיות למילים
													$cost_text = preg_replace('/ב\s+סוג/i', 'בסוג', $cost_text);
													$cost_text = preg_replace('/ה\s+מחיר/i', 'המחיר', $cost_text);
													$cost_text = preg_replace('/ל\s+חודש/i', 'לחודש', $cost_text);
													$cost_text = preg_replace('/ל\s+שנה/i', 'לשנה', $cost_text);
													?>
													<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level-price' ) ); ?>"><?php echo wp_kses_post( $cost_text ); ?></p>
													<?php
												}

												$expiration_text = pmpro_getLevelExpiration($level);
												if ( ! empty($expiration_text ) ) {
													?>
													<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level-expiration' ) ); ?>"><?php echo wp_kses_post( $expiration_text ); ?></p>
													<?php
												}
											?>
										</td>
										<td>
										<?php if ( ! $has_level ) { ?>                	
											<a aria-label="<?php echo esc_attr( sprintf( __('בחר את רמת החברות %s', 'paid-memberships-pro' ), $level->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-select', 'pmpro_btn-select' ) ); ?>" href="<?php echo esc_url( pmpro_url( "checkout", "?pmpro_level=" . $level->id, "https" ) ) ?>"><?php esc_html_e('בחר', 'paid-memberships-pro' );?></a>
										<?php } else { ?>      
											<?php
												//if it's a one-time-payment level, offer a link to renew	
												if( pmpro_isLevelExpiringSoon( $user_level ) && $level->allow_signups ) {
													?>
														<a aria-label="<?php echo esc_attr( sprintf( __('חדש את רמת החברות %s שלך', 'paid-memberships-pro' ), $level->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-renew pmpro_btn-select', 'pmpro_btn-select' ) ); ?>" href="<?php echo esc_url( pmpro_url( "checkout", "?pmpro_level=" . $level->id, "https" ) ) ?>"><?php esc_html_e('חדש', 'paid-memberships-pro' );?></a>
													<?php
												} else {
													?>
														<a aria-label="<?php echo esc_attr( sprintf( __('צפה בחשבון רמת החברות %s שלך', 'paid-memberships-pro' ), $level->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-outline', 'pmpro_btn' ) ); ?>" href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('הרמה שלך', 'paid-memberships-pro' );?></a>
													<?php
												}
											?>
										<?php } ?>
										</td>
									</tr>
									<?php
									}
									?>
								</tbody>
							</table>
						</div> <!-- end pmpro_card_content -->
					</div> <!-- end pmpro_card -->
					<?php
				}
			?>
		</div> <!-- end pmpro_section_content -->
	</section> <!-- end pmpro_section -->
</div> <!-- end pmpro -->

