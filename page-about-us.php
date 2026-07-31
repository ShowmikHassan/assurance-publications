<?php
/**
 * Template Name: আমাদের সম্পর্কে (About Us)
 *
 * Auto-picked up by WordPress for the page with slug "about-us" — no
 * template assignment needed in the editor. Bypasses Blocksy's default
 * hero + sidebar canvas entirely, same pattern as woocommerce/archive-product.php.
 *
 * @package Assurance
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="ap-about">

	<section class="ap-band ap-about-hero">
		<div class="ct-container">
			<div class="ap-about-hero__inner">
				<span class="ap-eyebrow"><?php esc_html_e( 'আমাদের সম্পর্কে', 'assurance' ); ?></span>
				<h1 class="ap-about-hero__title">
					<?php esc_html_e( 'We Help You... You Help The Nation', 'assurance' ); ?>
				</h1>
				<p class="ap-about-hero__sub">
					<?php esc_html_e( 'অ্যাসিওরেন্স পাবলিকেশন্স — বিসিএস, এনএসআই, প্রাথমিক শিক্ষক নিয়োগ ও পুলিশ নিয়োগসহ দেশের গুরুত্বপূর্ণ সরকারি চাকরির পরীক্ষার জন্য সরাসরি প্রকাশক থেকে প্রকাশিত, শতভাগ নির্ভরযোগ্য প্রস্তুতিমূলক বই।', 'assurance' ); ?>
				</p>
				<a class="ap-btn ap-btn--primary ap-btn--lg" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
					<?php esc_html_e( 'আমাদের বইসমূহ দেখুন', 'assurance' ); ?>
					<?php assurance_the_icon( 'arrow-right', array( 'size' => 16 ) ); ?>
				</a>
			</div>
		</div>
	</section>

	<section class="ap-about-stats">
		<div class="ct-container">
			<div class="ap-about-stats__grid">
				<div class="ap-stat">
					<span class="ap-stat__num">৩০+</span>
					<span class="ap-stat__label"><?php esc_html_e( 'বছরের অভিজ্ঞতা', 'assurance' ); ?></span>
				</div>
				<div class="ap-stat">
					<span class="ap-stat__num">৫০+</span>
					<span class="ap-stat__label"><?php esc_html_e( 'প্রকাশিত শিরোনাম', 'assurance' ); ?></span>
				</div>
				<div class="ap-stat">
					<span class="ap-stat__num">১০,০০০+</span>
					<span class="ap-stat__label"><?php esc_html_e( 'সন্তুষ্ট পাঠক', 'assurance' ); ?></span>
				</div>
				<div class="ap-stat">
					<span class="ap-stat__num">৬৪</span>
					<span class="ap-stat__label"><?php esc_html_e( 'জেলায় ডেলিভারি', 'assurance' ); ?></span>
				</div>
			</div>
		</div>
	</section>

	<section class="ap-band ap-about-story">
		<div class="ct-container">
			<div class="ap-about-story__grid">
				<div class="ap-about-story__text">
					<span class="ap-eyebrow"><?php esc_html_e( 'আমাদের যাত্রা', 'assurance' ); ?></span>
					<h2 class="ap-section-title"><?php esc_html_e( 'একটি বই, একটি স্বপ্ন পূরণের গল্প', 'assurance' ); ?></h2>
					<p><?php esc_html_e( 'প্রতি বছর লক্ষ লক্ষ তরুণ-তরুণী বিসিএস, এনএসআই, প্রাথমিক শিক্ষক নিয়োগ ও পুলিশ নিয়োগসহ বিভিন্ন সরকারি চাকরির পরীক্ষায় অংশ নেন। তাদের প্রস্তুতির পথ সহজ করার লক্ষ্য নিয়েই অ্যাসিওরেন্স পাবলিকেশন্স-এর যাত্রা শুরু। বাজারে প্রচলিত ভুলে ভরা, অগোছালো নোট আর অপ্রাসঙ্গিক তথ্যের বদলে আমরা তৈরি করেছি হালনাগাদ সিলেবাস অনুযায়ী নির্ভুল, সংক্ষিপ্ত ও পরীক্ষামুখী বই।', 'assurance' ); ?></p>
					<p><?php esc_html_e( 'প্রতিটি বই তৈরির পেছনে থাকে অভিজ্ঞ শিক্ষক ও সংশ্লিষ্ট বিষয়ের বিশেষজ্ঞদের দীর্ঘ গবেষণা, প্রশ্নব্যাংক বিশ্লেষণ এবং একাধিক ধাপের প্রুফরিডিং। ফলাফল — এমন বই যা শুধু তথ্য দেয় না, পরীক্ষার্থীকে সঠিক পথে প্রস্তুতি নিতে সাহায্য করে।', 'assurance' ); ?></p>
				</div>
				<div class="ap-about-story__media" aria-hidden="true">
					<div class="ap-about-story__badge">
						<?php assurance_the_icon( 'book', array( 'size' => 30 ) ); ?>
						<strong><?php esc_html_e( 'সরাসরি প্রকাশক থেকে', 'assurance' ); ?></strong>
						<span><?php esc_html_e( 'কোনো মধ্যস্বত্বভোগী নেই', 'assurance' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="ap-band ap-band--sunk ap-about-mv">
		<div class="ct-container">
			<div class="ap-about-mv__grid">
				<div class="ap-about-mv__card">
					<span class="ap-about-mv__icon" aria-hidden="true"><?php assurance_the_icon( 'target', array( 'size' => 26 ) ); ?></span>
					<h3><?php esc_html_e( 'আমাদের লক্ষ্য', 'assurance' ); ?></h3>
					<p><?php esc_html_e( 'প্রতিটি চাকরিপ্রত্যাশীর হাতে সঠিক, হালনাগাদ ও সাশ্রয়ী মূল্যের প্রস্তুতিমূলক বই পৌঁছে দেওয়া, যেন প্রস্তুতি হয় সহজ, গোছানো ও ফলপ্রসূ।', 'assurance' ); ?></p>
				</div>
				<div class="ap-about-mv__card">
					<span class="ap-about-mv__icon" aria-hidden="true"><?php assurance_the_icon( 'award', array( 'size' => 26 ) ); ?></span>
					<h3><?php esc_html_e( 'আমাদের ভিশন', 'assurance' ); ?></h3>
					<p><?php esc_html_e( 'দেশের সবচেয়ে বিশ্বস্ত প্রতিযোগিতামূলক পরীক্ষা প্রস্তুতি প্রকাশনা প্রতিষ্ঠান হিসেবে প্রতিষ্ঠিত হওয়া — যাদের বই মানেই নির্ভরযোগ্যতার নিশ্চয়তা।', 'assurance' ); ?></p>
				</div>
				<div class="ap-about-mv__card">
					<span class="ap-about-mv__icon" aria-hidden="true"><?php assurance_the_icon( 'heart', array( 'size' => 26 ) ); ?></span>
					<h3><?php esc_html_e( 'আমাদের মূল্যবোধ', 'assurance' ); ?></h3>
					<p><?php esc_html_e( 'সততা, নির্ভুলতা এবং পাঠকের সাফল্যের প্রতি অঙ্গীকার — এই তিনটি মূল্যবোধের ভিত্তিতে আমরা প্রতিটি সিদ্ধান্ত নিই।', 'assurance' ); ?></p>
				</div>
			</div>
		</div>
	</section>

	<section class="ap-band ap-about-why">
		<div class="ct-container">
			<div class="ap-section-head">
				<div class="ap-section-head__text">
					<span class="ap-eyebrow"><?php esc_html_e( 'কেন অ্যাসিওরেন্স', 'assurance' ); ?></span>
					<h2 class="ap-section-title"><?php esc_html_e( 'যে কারণে পাঠকরা আমাদের বেছে নেন', 'assurance' ); ?></h2>
				</div>
			</div>

			<ul class="ap-about-why__grid">
				<li class="ap-about-why__item">
					<span class="ap-about-why__icon" aria-hidden="true"><?php assurance_the_icon( 'book', array( 'size' => 22 ) ); ?></span>
					<strong><?php esc_html_e( 'শতভাগ আসল বই', 'assurance' ); ?></strong>
					<span><?php esc_html_e( 'সরাসরি প্রকাশক থেকে, কোনো নকল বা কপি নয়', 'assurance' ); ?></span>
				</li>
				<li class="ap-about-why__item">
					<span class="ap-about-why__icon" aria-hidden="true"><?php assurance_the_icon( 'file-text', array( 'size' => 22 ) ); ?></span>
					<strong><?php esc_html_e( 'হালনাগাদ সিলেবাস', 'assurance' ); ?></strong>
					<span><?php esc_html_e( 'সাম্প্রতিক প্রশ্নপত্র বিশ্লেষণ করে প্রস্তুত', 'assurance' ); ?></span>
				</li>
				<li class="ap-about-why__item">
					<span class="ap-about-why__icon" aria-hidden="true"><?php assurance_the_icon( 'truck', array( 'size' => 22 ) ); ?></span>
					<strong><?php esc_html_e( 'দ্রুত ডেলিভারি', 'assurance' ); ?></strong>
					<span><?php esc_html_e( 'সারা দেশে ৬৪ জেলায় ২–৪ দিনে পৌঁছে যাবে', 'assurance' ); ?></span>
				</li>
				<li class="ap-about-why__item">
					<span class="ap-about-why__icon" aria-hidden="true"><?php assurance_the_icon( 'shield', array( 'size' => 22 ) ); ?></span>
					<strong><?php esc_html_e( 'নিরাপদ পেমেন্ট', 'assurance' ); ?></strong>
					<span><?php esc_html_e( 'ক্যাশ অন ডেলিভারি ও বিকাশে নিরাপদে কিনুন', 'assurance' ); ?></span>
				</li>
				<li class="ap-about-why__item">
					<span class="ap-about-why__icon" aria-hidden="true"><?php assurance_the_icon( 'rotate', array( 'size' => 22 ) ); ?></span>
					<strong><?php esc_html_e( 'সহজ রিটার্ন', 'assurance' ); ?></strong>
					<span><?php esc_html_e( 'ভুল বা ক্ষতিগ্রস্ত বই সহজেই বদলে নিন', 'assurance' ); ?></span>
				</li>
				<li class="ap-about-why__item">
					<span class="ap-about-why__icon" aria-hidden="true"><?php assurance_the_icon( 'users', array( 'size' => 22 ) ); ?></span>
					<strong><?php esc_html_e( 'বিশেষজ্ঞ পরামর্শ', 'assurance' ); ?></strong>
					<span><?php esc_html_e( 'অভিজ্ঞ শিক্ষকদের তত্ত্বাবধানে রচিত ও যাচাইকৃত', 'assurance' ); ?></span>
				</li>
			</ul>
		</div>
	</section>

	<section class="ap-about-cta">
		<div class="ct-container ap-about-cta__inner">
			<div class="ap-about-cta__text">
				<h2><?php esc_html_e( 'প্রস্তুতি শুরু করুন আজই', 'assurance' ); ?></h2>
				<p><?php esc_html_e( 'সঠিক বই বেছে নেওয়াই সফল প্রস্তুতির প্রথম ধাপ। রেজিস্ট্রেশন/লগইন ছাড়াই অর্ডার করুন — ঢাকার ভিতরে ৭০ টাকা ও ঢাকার বাইরে মাত্র ১০০ টাকা ডেলিভারি চার্জে।', 'assurance' ); ?></p>
			</div>
			<div class="ap-about-cta__actions">
				<a class="ap-btn ap-btn--primary ap-btn--lg" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
					<?php esc_html_e( 'সকল বই দেখুন', 'assurance' ); ?>
				</a>
				<a class="ap-btn ap-btn--outline ap-btn--lg" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
					<?php esc_html_e( 'যোগাযোগ করুন', 'assurance' ); ?>
				</a>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
