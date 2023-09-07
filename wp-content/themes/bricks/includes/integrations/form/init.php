<?php
namespace Bricks\Integrations\Form;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Init {
	protected $uploaded_files;
	protected $form_settings;
	protected $form_fields;

	protected $results;

	public function __construct() {
		add_action( 'wp_ajax_bricks_form_submit', [ $this, 'form_submit' ] );
		add_action( 'wp_ajax_nopriv_bricks_form_submit', [ $this, 'form_submit' ] );
	}

	/**
	 * Element Form: Submit
	 *
	 * @since 1.0
	 */
	public function form_submit() {
		$this->form_settings = \Bricks\Helpers::get_element_settings( $_POST['postId'], $_POST['formId'] );

		if ( ! isset( $this->form_settings['actions'] ) || empty( $this->form_settings['actions'] ) ) {
			wp_send_json_error(
				[
					'code'    => 400,
					'action'  => '',
					'type'    => 'error',
					'message' => esc_html__( 'No action has been set for this form.', 'bricks' ),
				]
			);
		}

		// Google ReCAPTCHA v3 (invisible)
		if ( isset( $this->form_settings['enableRecaptcha'] ) ) {
			$recaptcha_verified   = false;
			$recaptcha_secret_key = \Bricks\Database::get_setting( 'apiSecretKeyGoogleRecaptcha', false );

			if ( ! empty( $_POST['recaptchaToken'] ) && $recaptcha_secret_key ) {
				// Verify token @see https://developers.google.com/recaptcha/docs/verify
				$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptcha_secret_key . '&response=' . $_POST['recaptchaToken'];

				// Use \Bricks\Helpers::remote_get() directly (@since 1.8.1)
				$recaptcha_res = \Bricks\Helpers::remote_get( $url );

				if ( ! is_wp_error( $recaptcha_res ) && wp_remote_retrieve_response_code( $recaptcha_res ) === 200 ) {
					$recaptcha = json_decode( wp_remote_retrieve_body( $recaptcha_res ) );

					/*
					 * Google reCAPTCHA v3 returns a score
					 *
					 * 1.0 is very likely a good interaction. 0.0 is very likely a bot.
					 *
					 * https://academy.bricksbuilder.io/article/form-element/#spam
					 */
					$score = apply_filters( 'bricks/form/recaptcha_score_threshold', 0.5 );

					// Action was set on the grecaptcha.execute (@see frontend.js)
					if ( $recaptcha->success && $recaptcha->score >= $score && $recaptcha->action == 'bricks_form_submit' ) {
						$recaptcha_verified = true;
					}
				}
			}

			if ( ! $recaptcha_verified ) {
				$error = esc_html__( 'Invalid Google reCaptcha.', 'bricks' );

				if ( ! empty( $recaptcha->{'error-codes'} ) ) {
					$error .= ' [' . implode( ',', $recaptcha->{'error-codes'} ) . ']';
				}

				wp_send_json_error(
					[
						'code'    => 400,
						'action'  => '',
						'type'    => 'error',
						'message' => $error,
					]
				);
			}
		}

		$this->form_fields = stripslashes_deep( $_POST );

		$this->uploaded_files = $this->handle_files();

		// STEP: Validate form submission via filter (@since 1.7.1)
		$validation_errors = [];

		$validation_errors = apply_filters( 'bricks/form/validate', $validation_errors, $this );

		// STEP: Validate required fields (@since 1.7.1)
		$validation_errors = $this->validate_required_fields( $validation_errors );

		// STEP: Validate submitted form (@since 1.7.1)
		if ( is_array( $validation_errors ) && count( $validation_errors ) ) {
			// Set validation error messages
			$this->set_error_messages( $validation_errors );

			// Halts execution if an action reported an error (@since 1.7.1 to run validator before running the form action)
			$this->maybe_stop_processing();
		}

		// STEP: Run selected form submit 'actions'
		$available_actions = self::get_available_actions();

		foreach ( $this->form_settings['actions'] as $form_action ) {
			if ( ! array_key_exists( $form_action, $available_actions ) ) {
				continue;
			}

			$action_class = 'Bricks\Integrations\Form\Actions\\' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $form_action ) ) );

			$action = new $action_class( $form_action );

			if ( ! method_exists( $action_class, 'run' ) ) {
				continue;
			}

			$action->run( $this );

			// Halts execution if an action reported an error
			$this->maybe_stop_processing();
		}

		// All fine, success
		$this->finish();
	}

	/**
	 * If there are any errors, stop execution
	 *
	 * @return void
	 */
	private function maybe_stop_processing() {
		$errors = ! empty( $this->results['error'] ) && is_array( $this->results['error'] ) ? $this->results['error'] : [];

		// type 'danger' used before 1.7.1
		if ( ! count( $errors ) && ! empty( $this->results['danger'] ) && is_array( $this->results['danger'] ) ) {
			$errors = $this->results['danger'];
		}

		if ( ! count( $errors ) ) {
			return;
		}

		// Get last error
		$error = array_pop( $errors );

		// Remove uploaded files, if exist
		$this->remove_files();

		// Leave
		wp_send_json_error( $error );
	}

	private function finish() {
		$form_settings = $this->form_settings;

		// Remove uploaded files, if exist
		$this->remove_files();

		// Basic response
		$response = [
			'type'    => 'success',
			'message' => isset( $form_settings['successMessage'] ) ? $this->render_data( $form_settings['successMessage'] ) : esc_html__( 'Success', 'bricks' )
		];

		if ( empty( $this->results ) ) {
			wp_send_json_success( $response );
		}

		// Check for redirects
		if ( ! empty( $this->results['redirect'] ) ) {
			$redirect                    = array_pop( $this->results['redirect'] );
			$post_id                     = ! empty( $_POST['postId'] ) ? $_POST['postId'] : get_the_ID();
			$response['redirectTo']      = ! empty( $redirect['redirectTo'] ) ? bricks_render_dynamic_data( $redirect['redirectTo'], $post_id ) : '';
			$response['redirectTimeout'] = isset( $redirect['redirectTimeout'] ) ? $redirect['redirectTimeout'] : 0;
		}

		// Check for 'info' messages (e.g. Mailchimp pending message)
		if ( ! empty( $this->results['info'] ) ) {
			foreach ( $this->results['info'] as $info ) {
				if ( ! empty( $info['message'] ) ) {
					$response['info'][] = $info['message'];
				}
			}
		}

		// Check for 'success' messages (e.g. custom bricks/form/validate) (@since 1.7.1)
		if ( ! empty( $this->results['success'] ) ) {
			foreach ( $this->results['success'] as $success ) {
				if ( ! empty( $success['message'] ) ) {
					$response['message'] = $success['message'];
				}
			}
		}

		// NOTE: Undocumented
		$response = apply_filters( 'bricks/form/response', $response, $this );

		// Evaluate results
		wp_send_json_success( $response );
	}

	/**
	 * Set action result
	 *
	 * type: success OR danger
	 *
	 * @param array $result
	 * @return void
	 */
	public function set_result( $result ) {
		$type                     = isset( $result['type'] ) ? $result['type'] : 'success';
		$this->results[ $type ][] = $result;
	}

	/**
	 * Getters
	 */
	public function get_settings() {
		return $this->form_settings;
	}

	public function get_fields() {
		return $this->form_fields;
	}

	public function get_uploaded_files() {
		return $this->uploaded_files;
	}

	public function get_results() {
		return $this->results;
	}

	/**
	 * Handle with any files uploaded with form
	 *
	 * @param string $action
	 * @return void
	 */
	public function handle_files() {
		if ( empty( $_FILES ) ) {
			return [];
		}

		// https://developer.wordpress.org/reference/functions/wp_handle_upload/
		$overrides = [ 'action' => 'bricks_form_submit' ];

		$uploaded_files = [];

		// Each form may have more than one input file type, each may have multiple files
		foreach ( $_FILES as $input_name => $files ) {
			if ( empty( $files['name'] ) ) {
				continue;
			}
			foreach ( $files['name'] as $key => $value ) {

				if ( empty( $files['name'][ $key ] ) || $files['error'][ $key ] !== UPLOAD_ERR_OK ) {
					continue;
				}

				$file = [
					'name'     => $files['name'][ $key ],
					'type'     => $files['type'][ $key ],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error'    => $files['error'][ $key ],
					'size'     => $files['size'][ $key ]
				];

				$uploaded = wp_handle_upload( $file, $overrides );

				// Upload success (uploaded to 'wp-content/uploads' folder)
				if ( $uploaded && ! isset( $uploaded['error'] ) ) {
					$uploaded_files[ $input_name ][] = $uploaded;
				}
			}
		}

		return $uploaded_files;
	}

	/**
	 * Eventually remove uploaded files
	 *
	 * @return void
	 */
	public function remove_files() {
		if ( empty( $this->uploaded_files ) ) {
			return;
		}

		// Remove uploaded files
		foreach ( $this->uploaded_files as $input_name => $files ) {
			foreach ( $files as $file ) {
				@unlink( $file['file'] );
			}
		}
	}

	/**
	 * Replace any {{field_id}} by the submitted form field content and after renders dynamic data
	 *
	 * @param string $content
	 * @return void
	 */
	public function render_data( $content ) {
		// \w: Matches any word character (alphanumeric & underscore).
		// Only matches low-ascii characters (no accented or non-roman characters).
		// Equivalent to [A-Za-z0-9_]
		// https://regexr.com/
		preg_match_all( '/{{(\w+)}}/', $content, $matches );

		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[1] as $key => $field_id ) {
				// Format: '{{zjkcdw}}' // Dynamic email data format
				$tag = $matches[0][ $key ];

				$value = $this->get_field_value( $field_id );

				$value = ! empty( $value ) && is_array( $value ) ? implode( ', ', $value ) : $value;

				$content = str_replace( $tag, $value, $content );
			}
		}

		$fields  = $this->get_fields();
		$post_id = isset( $fields['postId'] ) ? $fields['postId'] : 0;

		// Render dynamic data
		$content = bricks_render_dynamic_data( $content, $post_id );

		return $content;
	}

	/**
	 * Get value of individual form field by field ID
	 *
	 * @param string $field_id
	 * @return void
	 */
	public function get_field_value( $field_id = '' ) {
		$form_fields = $this->get_fields();

		// NOTE: Undocumented {{referrer_url}}
		if ( $field_id === 'referrer_url' && isset( $_POST['referrer'] ) ) {
			return esc_url( $_POST['referrer'] );
		}

		if ( empty( $field_id ) || ! array_key_exists( "form-field-{$field_id}", $form_fields ) ) {
			return '';
		}

		return $form_fields[ "form-field-{$field_id}" ];
	}


	/**
	 * Available actions after form submission
	 *
	 * @return void
	 */
	public static function get_available_actions() {
		return [
			'custom'       => esc_html__( 'Custom', 'bricks' ),
			'email'        => esc_html__( 'Email', 'bricks' ),
			'redirect'     => esc_html__( 'Redirect', 'bricks' ),
			'mailchimp'    => 'Mailchimp',
			'sendgrid'     => 'SendGrid',
			'login'        => esc_html__( 'User Login', 'bricks' ),
			'registration' => esc_html__( 'User Registration', 'bricks' ),
		];
	}

	/**
	 * Set form submit error messages
	 *
	 * @param array $error_messages
	 *
	 * @since 1.7.1
	 */
	public function set_error_messages( $error_messages ) {
		if ( empty( $error_messages ) ) {
			return;
		}

		if ( is_string( $error_messages ) ) {
			$error_messages = [ $error_messages ];
		}

		// One error: Return error message as string
		if ( count( $error_messages ) === 1 ) {
			$this->set_result(
				[
					'type'    => 'error',
					'message' => $error_messages,
				]
			);

			return;
		}

		// More than one error: Return error messages as unordered list
		$message = '<ul>';

		// Combine $error_messages into a single string
		foreach ( $error_messages as $error_message ) {
			$message .= "<li>{$error_message}</li>";
		}

		$message .= '</ul>';

		$this->set_result(
			[
				'type'    => 'error',
				'message' => $message,
			]
		);
	}

	/**
	 * Validate required fields
	 *
	 * @param array|string $custom_validation_errors Custom validation errors adding via filter 'bricks_form_validation_errors'.
	 *
	 * @return array
	 *
	 * @since 1.7.1
	 */
	public function validate_required_fields( $custom_validation_errors = [] ) {
		$submitted_fields     = $this->get_fields();
		$uploaded_files       = $this->get_uploaded_files();
		$form_settings        = $this->get_settings();
		$form_settings_fields = ! empty( $form_settings['fields'] ) ? $form_settings['fields'] : [];

		$errors = [];

		foreach ( $form_settings_fields as $form_settings_field ) {
			// Skip if field is not required
			if ( empty( $form_settings_field['required'] ) ) {
				continue;
			}

			$error = false;

			// File field: file
			if ( $form_settings_field['type'] === 'file' ) {
				if ( empty( $uploaded_files[ "form-field-{$form_settings_field['id']}" ] ) ) {
					$error = true;
				}
			}

			// All other field types
			else {
				if (
					! isset( $submitted_fields[ "form-field-{$form_settings_field['id']}" ] ) ||
					$submitted_fields[ "form-field-{$form_settings_field['id']}" ] === ''
				) {
					$error = true;
				}
			}

			if ( $error ) {
				// Field is required & empty: Add error message
				$field_label = ! empty( $form_settings_field['label'] ) ? $form_settings_field['label'] : $form_settings_field['type'];

				$errors[] = esc_html__( 'Required', 'bricks' ) . ": $field_label";
			}
		}

		// Custom validation error is a string: Convert to array
		if ( $custom_validation_errors && is_string( $custom_validation_errors ) ) {
			$custom_validation_errors = [ $custom_validation_errors ];
		}

		// Filter out empty error strings
		if ( is_array( $custom_validation_errors ) && count( $custom_validation_errors ) ) {
			$custom_validation_errors = array_filter( $custom_validation_errors );

			$errors = array_merge( $errors, $custom_validation_errors );
		}

		// Return: Array of validation errors (each error as a string, representing a single error message)
		return $errors;
	}
}
