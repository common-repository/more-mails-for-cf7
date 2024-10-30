<?php
/*
Plugin Name: More Mails for Contact Form 7
Description: Add a third, fourth, etc. mail message to any CF7 form.
Version: 1.2.1
Author: Roy Orbitson
Author URI: https://profiles.wordpress.org/lev0/
Text Domain: more-mails-for-contact-form-7
Licence: GPLv2 or later
*/

namespace MoreMailsForCF7;

const MIN_FORMS = 3;
define(__NAMESPACE__ . '\BASE', basename(__FILE__, '.php'));

if (is_admin()) {
	add_action(
		'admin_menu'
		, function() {
			$title = __('Contact Form 7 More Mails', 'more-mails-for-contact-form-7');
			$slug_admin = BASE . '-admin';
			$slug_settings = BASE . '-settings';

			add_filter(
				'plugin_action_links_' . plugin_basename(__FILE__)
				, function($links) use ($slug_admin) {
					array_unshift(
						$links
						, '<a href="' . esc_attr(admin_url('admin.php?page=' . urlencode($slug_admin))) . '">Settings</a>'
					);
					return $links;
				}
			);

			add_submenu_page(
				'wpcf7'
				, esc_html($title)
				, esc_html__('More mails', 'more-mails-for-contact-form-7')
				, 'administrator'
				, $slug_admin
				, function() use ($title, $slug_admin, $slug_settings) {
					?>
					<div class=wrap>
						<h1><?= esc_html($title); ?></h1>
						<form action=options.php method=post>
							<?php
							settings_fields($slug_settings);
							do_settings_sections($slug_admin);
							submit_button();
							?>
						</form>
					</div>
					<?php
				}
			);

			add_action(
				'admin_init'
				, function() use ($slug_admin, $slug_settings) {
					register_setting(
						$slug_settings
						, BASE
						, [
							'sanitize_callback' => function($inputs) {
								foreach ($inputs as $setting => $val) {
									switch ($setting) {
										case 'max':
											$inputs[$setting] = max(MIN_FORMS, (int) $val);
									}
								}
								return $inputs;
							},
						]
					);

					$options = get_option(BASE);

					$slug_sect = BASE . '-general';
					add_settings_section(
						$slug_sect
						, __('Settings', 'more-mails-for-contact-form-7')
						, '__return_empty_string'
						, $slug_admin
					);
					$name = 'max';
					add_settings_field(
						$name
						, __('Mail sections per form', 'more-mails-for-contact-form-7')
						, function() use (&$options, $name) {
							printf(
								'<input type=number required class=small-text id=%1$s-%2$s name="%1$s[%2$s]" value="%3$s" min=%4$d placeholder=%4$d>'
								, BASE
								, $name
								, $options && isset($options[$name]) ? esc_attr($options[$name]) : ''
								, MIN_FORMS
							);
						}
						, $slug_admin
						, $slug_sect
						, [
							'label_for' => BASE . "-$name",
						]
					);
				}
			);
		}
		, 9999
	);

	add_filter(
		'wpcf7_editor_panels'
		, function($panels) {
			if ($max = moma4cf7_max()) {
				$callback = $panels['mail-panel']['callback'];
				$panels['mail-panel']['callback'] = function(...$args) use ($max, $callback) {
					$original = call_user_func($callback, ...$args);
					$post = reset($args);
					for ($i = MIN_FORMS; $i <= $max; $i++) {
						echo '<br class="clear" />';
						wpcf7_editor_box_mail(
							$post
							, [
								'id' => "wpcf7-mail-$i",
								'name' => "mail_$i",
								/* translators: %d: nth mail section */
								'title' => sprintf(__('Mail (%d)', 'more-mails-for-contact-form-7'), $i),
								/* translators: %d: nth mail section */
								'use' => sprintf(__('Use Mail (%d)', 'more-mails-for-contact-form-7'), $i),
							]
						);
					}
					return $original;
				};
			}
			return $panels;
		}
		, 8
	);

	add_action(
		'wpcf7_save_contact_form'
		, function($contact_form, $args, $context) {
			if ($max = moma4cf7_max()) {
				$properties_moma = [];
				for ($i = MIN_FORMS; $i <= $max; $i++) {
					$property = "mail_$i";
					if (isset($_POST["wpcf7-mail-$i"])) {
						$properties_moma[$property] = wpcf7_sanitize_mail(
							wp_unslash($_POST["wpcf7-mail-$i"])
						);
					}
				}
				if ($properties_moma) {
					$contact_form->set_properties($properties_moma);
				}
			}
		}
		, 8
		, 3
	);
}

register_activation_hook(
	__FILE__
	, function() {
		if (false === get_option(BASE)) {
			add_option(BASE, [
				'max' => MIN_FORMS,
			]);
		}
	}
);

call_user_func(function() {
	# Attach to both new and old hooks, but add properties to only the first.
	$add_to = null;
	$adder = function($properties, $contact_form) use (&$add_to) {
		$current_filter = current_filter();
		if ($add_to === null) {
			$add_to = $current_filter;
		}
		if (
			$add_to === $current_filter
			&& ($max = moma4cf7_max())
		) {
			for ($i = MIN_FORMS; $i <= $max; $i++) {
				$property = "mail_$i";
				if (!array_key_exists($property, $properties)) {
					$properties[$property] = [];
				}
			}
		}
		return $properties;
	};

	foreach (
		[
			'wpcf7_pre_construct_contact_form_properties',
			'wpcf7_contact_form_properties',
		]
		as $filter
	) {
		add_filter($filter, $adder, 8, 2);
	}
});

add_filter(
	'wpcf7_additional_mail'
	, function($additional_mail, $contact_form) {
		if ($max = moma4cf7_max()) {
			for ($i = MIN_FORMS; $i <= $max; $i++) {
				$property = "mail_$i";
				if (
					$mail_n = $contact_form->prop($property)
					and $mail_n['active']
				) {
					$additional_mail[$property] = $mail_n;
				}
			}
		}
		return $additional_mail;
	}
	, 8
	, 2
);

function moma4cf7_max() {
	static $max = null;
	if (null === $max) {
		$max = ($options = get_option(BASE))
				&& isset($options['max'])
				&& ($options['max'] = (int) $options['max']) >= MIN_FORMS ?
			$options['max'] :
			false;
	}
	return $max;
}
