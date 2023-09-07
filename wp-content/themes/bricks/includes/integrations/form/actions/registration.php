<?php
namespace Bricks\Integrations\Form\Actions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Registration extends Base {

	/**
	 * User registration
	 *
	 * @since 1.0
	 */
	public function run( $form ) {

		$form_settings = $form->get_settings();
		$form_fields   = $form->get_fields();

		$user_email           = isset( $form_fields[ "form-field-{$form_settings['registrationEmail']}" ] ) ? $form_fields[ "form-field-{$form_settings['registrationEmail']}" ] : false;
		$user_login           = isset( $form_fields[ "form-field-{$form_settings['registrationUserName']}" ] ) ? $form_fields[ "form-field-{$form_settings['registrationUserName']}" ] : false;
		$user_pass            = isset( $form_fields[ "form-field-{$form_settings['registrationPassword']}" ] ) ? $form_fields[ "form-field-{$form_settings['registrationPassword']}" ] : false;
		$user_pass_min_length = isset( $form_settings['registrationPasswordMinLength'] ) ? intval( $form_settings['registrationPasswordMinLength'] ) : false;

		// Form validation
		$registration_errors = self::validate_registration(
			[
				'user_login'           => $user_login,
				'user_email'           => $user_email,
				'user_pass'            => $user_pass,
				'user_pass_min_length' => $user_pass_min_length,
			]
		);

		if ( is_wp_error( $registration_errors ) ) {
			$form->set_result(
				[
					'action'  => $this->name,
					'type'    => 'error',
					'message' => $registration_errors,
				]
			);

			return;
		}

		// 'wp_insert_user' returns user ID on success and WP_Error object on failure
		$user_id = wp_insert_user(
			[
				'user_login' => $user_login,
				'user_email' => $user_email,
				'user_pass'  => $user_pass,
			]
		);

		// Error: User Registration
		if ( is_wp_error( $user_id ) ) {
			$form->set_result(
				[
					'action'  => $this->name,
					'type'    => 'error',
					'message' => $user_id->get_error_message(),
				]
			);

			return;
		}

		// Success: Set response defaults

		// Update first name
		if ( isset( $form_fields[ "form-field-{$form_settings['registrationFirstName']}" ] ) ) {
			update_user_meta( $user_id, 'first_name', $form_fields[ "form-field-{$form_settings['registrationFirstName']}" ] );
		}

		// Update last name
		if ( isset( $form_fields[ "form-field-{$form_settings['registrationLastName']}" ] ) ) {
			update_user_meta( $user_id, 'last_name', $form_fields[ "form-field-{$form_settings['registrationLastName']}" ] );
		}

		// Auto log in user
		if ( isset( $form_settings['registrationAutoLogin'] ) ) {
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, false, is_ssl() );
		}

		// wp_new_user_notification( $user_id, $user_pass );

		$form->set_result(
			[
				'action' => $this->name,
				'type'   => 'success',
			]
		);
	}

	/**
	 * Validate user registration
	 *
	 * @param array $user_data Array with user data (user_login, user_email, user_pass etc.)
	 *
	 * @return array|null Returns WP_Error object if validation errors found or null if validation passed.
	 *
	 * @since 1.0
	 */
	public static function validate_registration( $user_data ) {
		$errors = [];

		// Validate: user_login
		if ( ! $user_data['user_login'] ) {
			$errors['user_login'] = esc_html__( 'Username required.', 'bricks' );
		} elseif ( username_exists( $user_data['user_login'] ) ) {
			$errors['user_login'] = esc_html__( 'Username already exists.', 'bricks' );
		} elseif ( ! validate_username( $user_data['user_login'] ) ) {
			$errors['user_login'] = esc_html__( 'Username is not valid.', 'bricks' );
		}

		// Validate: user_email
		if ( ! $user_data['user_email'] ) {
			$errors['user_email'] = esc_html__( 'Email address required.', 'bricks' );
		} elseif ( ! is_email( $user_data['user_email'] ) ) {
			$errors['user_email'] = esc_html__( 'Email address is not valid.', 'bricks' );
		} elseif ( email_exists( $user_data['user_email'] ) ) {
			$errors['user_email'] = esc_html__( 'Email address already exists.', 'bricks' );
		}

		// Validate: user_pass
		if ( ! $user_data['user_pass'] ) {
			$errors['user_pass'] = esc_html__( 'Password required.', 'bricks' );
		}

		// Validate: user_pass_min_length
		elseif ( $user_data['user_pass_min_length'] && ( strlen( $user_data['user_pass'] ) < intval( $user_data['user_pass_min_length'] ) ) ) {
			$errors['user_pass'] = sprintf( esc_html__( 'Please enter a password of at least %s characters.', 'bricks' ), $user_data['user_pass_min_length'] );
		}

		if ( ! empty( $errors ) ) {
			$wp_error = new \WP_Error();

			foreach ( $errors as $code => $message ) {
				$wp_error->add( $code, $message );
			}

			return $wp_error;
		}
	}

}
