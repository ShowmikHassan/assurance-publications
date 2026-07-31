<?php
/**
 * Template Name: যোগাযোগ (Contact)
 *
 * Auto-picked up by WordPress for the page with slug "contact" — no
 * template assignment needed in the editor. Bypasses Blocksy's default
 * hero + sidebar canvas entirely, same pattern as woocommerce/archive-product.php.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="ap-contact">

	<section class="ap-band ap-contact-hero">
		<div class="ct-container ap-contact-hero__inner">
			<span class="ap-eyebrow"><?php esc_html_e( 'যোগাযোগ', 'assurance' ); ?></span>
			<h1 class="ap-contact-hero__title"><?php esc_html_e( 'আমাদের সাথে কথা বলুন', 'assurance' ); ?></h1>
			<p class="ap-contact-hero__sub">
				<?php esc_html_e( 'বই সংক্রান্ত কোনো প্রশ্ন, অর্ডার নিয়ে সমস্যা, অথবা বাল্ক অর্ডারের প্রয়োজন — যেকোনো বিষয়ে নিচের ফর্মে বার্তা পাঠান অথবা সরাসরি কল করুন। আমরা দ্রুততম সময়ে সাড়া দেব।', 'assurance' ); ?>
			</p>
		</div>
	</section>

	<section class="ap-band ap-contact-body">
		<div class="ct-container ap-contact-grid">

			<div class="ap-contact-info">
				<a class="ap-contact-card" href="https://www.google.com/maps/search/?api=1&query=3%2C+New+Paltan+Line%2C+Azimpur%2C+Dhaka+1000" target="_blank" rel="noopener">
					<span class="ap-contact-card__icon" aria-hidden="true"><?php assurance_the_icon( 'map-pin', array( 'size' => 20 ) ); ?></span>
					<span class="ap-contact-card__body">
						<strong class="ap-contact-card__label"><?php esc_html_e( 'ঠিকানা', 'assurance' ); ?></strong>
						<span class="ap-contact-card__value">৩, নিউ পল্টন লাইন, আজিমপুর, ঢাকা - ১০০০</span>
					</span>
				</a>

				<a class="ap-contact-card" href="tel:+8801341875192">
					<span class="ap-contact-card__icon" aria-hidden="true"><?php assurance_the_icon( 'phone', array( 'size' => 20 ) ); ?></span>
					<span class="ap-contact-card__body">
						<strong class="ap-contact-card__label"><?php esc_html_e( 'ফোন / হোয়াটসঅ্যাপ', 'assurance' ); ?></strong>
						<span class="ap-contact-card__value" dir="ltr">+880 1341-875192</span>
					</span>
				</a>

				<a class="ap-contact-card" href="mailto:assurance1996@gmail.com">
					<span class="ap-contact-card__icon" aria-hidden="true"><?php assurance_the_icon( 'mail', array( 'size' => 20 ) ); ?></span>
					<span class="ap-contact-card__body">
						<strong class="ap-contact-card__label"><?php esc_html_e( 'ইমেইল', 'assurance' ); ?></strong>
						<span class="ap-contact-card__value">assurance1996@gmail.com</span>
					</span>
				</a>

				<div class="ap-contact-card ap-contact-card--static">
					<span class="ap-contact-card__icon" aria-hidden="true"><?php assurance_the_icon( 'clock', array( 'size' => 20 ) ); ?></span>
					<span class="ap-contact-card__body">
						<strong class="ap-contact-card__label"><?php esc_html_e( 'সাপোর্ট সময়', 'assurance' ); ?></strong>
						<span class="ap-contact-card__value"><?php esc_html_e( 'শনি – বৃহস্পতি, সকাল ১০টা – রাত ৮টা', 'assurance' ); ?></span>
					</span>
				</div>

				<div class="ap-social">
					<strong class="ap-social__title"><?php esc_html_e( 'আমাদের সাথে যুক্ত থাকুন', 'assurance' ); ?></strong>

					<ul class="ap-social__list">
						<li>
							<a class="ap-social__link ap-social__link--fb" href="https://www.facebook.com/share/14jUBkCDSaj/" target="_blank" rel="noopener noreferrer">
								<span class="ap-social__icon" aria-hidden="true"><?php assurance_the_icon( 'facebook', array( 'size' => 18 ) ); ?></span>
								<span class="ap-social__text">
									<span class="ap-social__label"><?php esc_html_e( 'ফেসবুক পেজ', 'assurance' ); ?></span>
									<span class="ap-social__meta"><?php esc_html_e( 'নতুন বইয়ের খবর পান', 'assurance' ); ?></span>
								</span>
								<?php assurance_the_icon( 'arrow-right', array( 'size' => 15, 'class' => 'ap-social__go' ) ); ?>
							</a>
						</li>

						<li>
							<a class="ap-social__link ap-social__link--fb" href="https://www.facebook.com/share/g/1LxpKgBQZq/" target="_blank" rel="noopener noreferrer">
								<span class="ap-social__icon" aria-hidden="true"><?php assurance_the_icon( 'facebook-group', array( 'size' => 18 ) ); ?></span>
								<span class="ap-social__text">
									<span class="ap-social__label"><?php esc_html_e( 'ফেসবুক গ্রুপ', 'assurance' ); ?></span>
									<span class="ap-social__meta"><?php esc_html_e( 'পাঠকদের সাথে আলোচনা করুন', 'assurance' ); ?></span>
								</span>
								<?php assurance_the_icon( 'arrow-right', array( 'size' => 15, 'class' => 'ap-social__go' ) ); ?>
							</a>
						</li>

						<li>
							<a class="ap-social__link ap-social__link--wa" href="https://wa.me/8801341875192" target="_blank" rel="noopener noreferrer">
								<span class="ap-social__icon" aria-hidden="true"><?php assurance_the_icon( 'whatsapp', array( 'size' => 18 ) ); ?></span>
								<span class="ap-social__text">
									<span class="ap-social__label"><?php esc_html_e( 'হোয়াটসঅ্যাপ', 'assurance' ); ?></span>
									<span class="ap-social__meta"><?php esc_html_e( 'দ্রুত উত্তর পেতে মেসেজ দিন', 'assurance' ); ?></span>
								</span>
								<?php assurance_the_icon( 'arrow-right', array( 'size' => 15, 'class' => 'ap-social__go' ) ); ?>
							</a>
						</li>
					</ul>
				</div>
			</div>

			<form class="ap-contact-form" id="ap-contact-form" novalidate>
				<div class="ap-contact-form__row">
					<div class="ap-field">
						<label class="ap-field__label" for="ap-cf-name"><?php esc_html_e( 'নাম', 'assurance' ); ?></label>
						<input class="ap-field__input" type="text" id="ap-cf-name" name="name" autocomplete="name" required>
						<span class="ap-field__error" data-ap-error="name"></span>
					</div>

					<div class="ap-field">
						<label class="ap-field__label" for="ap-cf-phone"><?php esc_html_e( 'মোবাইল নম্বর', 'assurance' ); ?></label>
						<input class="ap-field__input" type="tel" id="ap-cf-phone" name="phone" autocomplete="tel" dir="ltr" required>
						<span class="ap-field__error" data-ap-error="phone"></span>
					</div>
				</div>

				<div class="ap-field">
					<label class="ap-field__label" for="ap-cf-email"><?php esc_html_e( 'ইমেইল', 'assurance' ); ?></label>
					<input class="ap-field__input" type="email" id="ap-cf-email" name="email" autocomplete="email" dir="ltr" required>
					<span class="ap-field__error" data-ap-error="email"></span>
				</div>

				<div class="ap-field">
					<label class="ap-field__label" for="ap-cf-message"><?php esc_html_e( 'বার্তা', 'assurance' ); ?></label>
					<textarea class="ap-field__input ap-field__input--textarea" id="ap-cf-message" name="message" rows="4" required></textarea>
					<span class="ap-field__error" data-ap-error="message"></span>
				</div>

				<div class="ap-field ap-field--honeypot" aria-hidden="true">
					<label for="ap-cf-website"><?php esc_html_e( 'Website', 'assurance' ); ?></label>
					<input type="text" id="ap-cf-website" name="website" tabindex="-1" autocomplete="off">
				</div>

				<p class="ap-contact-form__status" data-ap-form-status role="status" aria-live="polite" hidden></p>

				<button type="submit" class="ap-btn ap-btn--primary ap-btn--lg ap-btn--block ap-contact-form__submit">
					<?php assurance_the_icon( 'send', array( 'size' => 17 ) ); ?>
					<span><?php esc_html_e( 'বার্তা পাঠান', 'assurance' ); ?></span>
				</button>
			</form>

		</div>
	</section>

	<section class="ap-contact-map">
		<iframe
			src="https://www.google.com/maps?q=3,%20New%20Paltan%20Line,%20Azimpur,%20Dhaka%201000&output=embed"
			width="100%"
			height="380"
			style="border:0"
			allowfullscreen
			loading="lazy"
			referrerpolicy="no-referrer-when-downgrade"
			title="<?php esc_attr_e( 'অ্যাসিওরেন্স পাবলিকেশন্স — মানচিত্রে অবস্থান', 'assurance' ); ?>"
		></iframe>
	</section>

</main>

<?php
get_footer();
