<?php
/**
 * Email footer.
 *
 * @package Assurance
 * @version 9.8.0 (base)
 */

defined( 'ABSPATH' ) || exit;

$palette = assurance_email_palette();
$font    = assurance_email_font();
/*
 * WooCommerce's "Footer text" setting is deliberately not printed here.
 * It ships defaulted to `{site_title}<br />{store_address}`, which resolves
 * to exactly the store name and street the structured block below already
 * prints from the same options — so every email carried the name and address
 * twice, the first copy being the less complete of the two (it omits
 * store_address_2 and the city). The block below is this theme's footer of
 * record; extra footer copy belongs in it, not in a free-text option above it.
 */
$store_phone = get_option( 'woocommerce_store_phone' );
$store_email = assurance_shop_contact_email();
$address     = trim( get_option( 'woocommerce_store_address' ) . ' ' . get_option( 'woocommerce_store_address_2' ) );
$city        = get_option( 'woocommerce_store_city' );
?>
						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td id="template_footer" style="padding:24px 34px 28px; background-color:<?php echo esc_attr( $palette['surface_alt'] ); ?>; border-top:1px solid <?php echo esc_attr( $palette['line'] ); ?>;">

							<p style="margin:0 0 4px; font-family:<?php echo esc_attr( $font ); ?>; font-size:13px; line-height:1.7; color:<?php echo esc_attr( $palette['ink'] ); ?>; font-weight:700;">
								<?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?>
							</p>

							<?php if ( $address ) : ?>
								<p style="margin:0 0 2px; font-family:<?php echo esc_attr( $font ); ?>; font-size:13px; line-height:1.7; color:<?php echo esc_attr( $palette['ink_muted'] ); ?>;">
									<?php echo esc_html( trim( $address . ( $city ? ', ' . $city : '' ) ) ); ?>
								</p>
							<?php endif; ?>

							<p style="margin:0 0 10px; font-family:<?php echo esc_attr( $font ); ?>; font-size:13px; line-height:1.7; color:<?php echo esc_attr( $palette['ink_muted'] ); ?>;">
								<?php if ( $store_phone ) : ?>
									<?php esc_html_e( 'ফোন', 'assurance' ); ?>:
									<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $store_phone ) ); ?>" style="color:<?php echo esc_attr( $palette['ink_soft'] ); ?>; text-decoration:none;"><?php echo esc_html( $store_phone ); ?></a>
									&nbsp;·&nbsp;
								<?php endif; ?>
								<a href="mailto:<?php echo esc_attr( $store_email ); ?>" style="color:<?php echo esc_attr( $palette['ink_soft'] ); ?>; text-decoration:none;"><?php echo esc_html( $store_email ); ?></a>
							</p>

							<p style="margin:0; font-family:<?php echo esc_attr( $font ); ?>; font-size:13px; line-height:1.7;">
								<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:<?php echo esc_attr( $palette['accent'] ); ?>; text-decoration:none; font-weight:700;">
									<?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?>
								</a>
							</p>
						</td>
					</tr>
				</table>

				<p style="margin:16px 0 0; font-family:<?php echo esc_attr( $font ); ?>; font-size:11px; line-height:1.6; color:<?php echo esc_attr( $palette['ink_faint'] ); ?>; text-align:center;">
					<?php esc_html_e( 'এই ইমেইলটি স্বয়ংক্রিয়ভাবে পাঠানো হয়েছে।', 'assurance' ); ?>
				</p>

			</td>
		</tr>
	</table>
</body>
</html>
