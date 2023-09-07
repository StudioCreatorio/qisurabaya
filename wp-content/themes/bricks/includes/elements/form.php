<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Form extends Element {
	public $category = 'general';
	public $name     = 'form';
	public $icon     = 'ti-layout-cta-left';
	public $tag      = 'form';
	public $scripts  = [ 'bricksForm' ];

	public function get_label() {
		return esc_html__( 'Form', 'bricks' );
	}

	public function enqueue_scripts() {
		if ( isset( $this->settings['enableRecaptcha'] ) ) {
			wp_enqueue_script( 'bricks-google-recaptcha' );
		}

		$fields = ! empty( $this->settings['fields'] ) ? $this->settings['fields'] : false;

		/**
		 * Load Flatpickr library (form field type 'date' found)
		 *
		 * @since 1.8.6 - Load localization file if set (default: English)
		 */
		if ( is_array( $fields ) ) {
			foreach ( $this->settings['fields'] as $field ) {
				if ( $field['type'] === 'datepicker' ) {
					if ( ! bricks_is_builder() ) {
						wp_enqueue_script( 'bricks-flatpickr' );
						wp_enqueue_style( 'bricks-flatpickr' );
					}

					// Load datepicker localisation (@since 1.8.6)
					$l10n = ! empty( $field['l10n'] ) ? $field['l10n'] : '';

					if ( $l10n ) {
						wp_enqueue_script( 'bricks-flatpickr-l10n', "https://npmcdn.com/flatpickr@4.6.13/dist/l10n/$l10n.js", [ 'bricks-flatpickr' ] );
					}
				}
			}
		}
	}

	public function set_control_groups() {
		$this->control_groups['fields'] = [
			'title' => esc_html__( 'Fields', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['submitButton'] = [
			'title' => esc_html__( 'Submit button', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['actions'] = [
			'title' => esc_html__( 'Actions', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['email'] = [
			'title'    => esc_html__( 'Email', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'actions', '=', 'email' ],
		];

		$this->control_groups['confirmation'] = [
			'title'    => esc_html__( 'Confirmation email', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'actions', '=', 'email' ],
		];

		$this->control_groups['redirect'] = [
			'title'    => esc_html__( 'Redirect', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'actions', '=', 'redirect' ],
		];

		$this->control_groups['mailchimp'] = [
			'title'    => 'Mailchimp',
			'tab'      => 'content',
			'required' => [ 'actions', '=', 'mailchimp' ],
		];

		$this->control_groups['sendgrid'] = [
			'title'    => 'Sendgrid',
			'tab'      => 'content',
			'required' => [ 'actions', '=', 'sendgrid' ],
		];

		$this->control_groups['registration'] = [
			'title'    => esc_html__( 'User Registration', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'actions', '=', 'registration' ],
		];

		$this->control_groups['login'] = [
			'title'    => esc_html__( 'User Login', 'bricks' ),
			'tab'      => 'content',
			'required' => [ 'actions', '=', 'login' ],
		];

		$this->control_groups['spam'] = [
			'title' => esc_html__( 'Spam protection', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// Group: Fields
		$this->controls['fields'] = [
			'tab'           => 'content',
			'group'         => 'fields',
			'placeholder'   => esc_html__( 'Form Field', 'bricks' ),
			'type'          => 'repeater',
			'selector'      => '.form-group',
			'titleProperty' => 'label',
			'fields'        => [
				'type'                   => [
					'label'     => esc_html__( 'Type', 'bricks' ),
					'type'      => 'select',
					'options'   => [
						'email'      => esc_html__( 'Email', 'bricks' ),
						'text'       => esc_html__( 'Text', 'bricks' ),
						'textarea'   => esc_html__( 'Textarea', 'bricks' ),
						'tel'        => esc_html__( 'Tel', 'bricks' ),
						'number'     => esc_html__( 'Number', 'bricks' ),
						'url'        => esc_html__( 'URL', 'bricks' ),
						'checkbox'   => esc_html__( 'Checkbox', 'bricks' ),
						'select'     => esc_html__( 'Select', 'bricks' ),
						'radio'      => esc_html__( 'Radio', 'bricks' ),
						'file'       => esc_html__( 'File upload', 'bricks' ),
						'password'   => esc_html__( 'Password', 'bricks' ),
						'datepicker' => esc_html__( 'Datepicker', 'bricks' ),
						'hidden'     => esc_html__( 'Hidden', 'bricks' ),
					],
					'clearable' => false,
				],

				'min'                    => [
					'label'    => esc_html__( 'Min', 'bricks' ),
					'type'     => 'number',
					'min'      => 0,
					'max'      => 100,
					'required' => [ 'type', '=', [ 'number' ] ],
				],

				'max'                    => [
					'label'    => esc_html__( 'Max', 'bricks' ),
					'type'     => 'number',
					'min'      => 0,
					'max'      => 100,
					'required' => [ 'type', '=', [ 'number' ] ],
				],

				'label'                  => [
					'label' => esc_html__( 'Label', 'bricks' ),
					'type'  => 'text',
				],

				'placeholder'            => [
					'label'    => esc_html__( 'Placeholder', 'bricks' ),
					'type'     => 'text',
					'required' => [ 'type', '!=', [ 'file', 'hidden' ] ],
				],

				'value'                  => [
					'label'    => esc_html__( 'Value', 'bricks' ),
					'type'     => 'text',
					'required' => [ 'type', '=', [ 'hidden' ] ],
				],

				'fileUploadSeparator'    => [
					'label'    => esc_html__( 'File upload', 'bricks' ),
					'type'     => 'separator',
					'required' => [ 'type', '=', 'file' ],
				],

				'fileUploadButtonText'   => [
					'type'        => 'text',
					'placeholder' => esc_html__( 'Choose files', 'bricks' ),
					'default'     => esc_html__( 'Choose files', 'bricks' ),
					'required'    => [ 'type', '=', 'file' ],
				],

				'fileUploadLimit'        => [
					'label'    => esc_html__( 'max. files', 'bricks' ),
					'type'     => 'number',
					'min'      => 1,
					'max'      => 50,
					'required' => [ 'type', '=', 'file' ],
				],

				'fileUploadSize'         => [
					'label'    => esc_html__( 'Max. size', 'bricks' ) . ' (MB)',
					'type'     => 'number',
					'min'      => 1,
					'max'      => 50,
					'required' => [ 'type', '=', 'file' ],
				],

				'width'                  => [
					'label'       => esc_html__( 'Width', 'bricks' ) . ' (%)',
					'type'        => 'number',
					'unit'        => '%',
					'min'         => 0,
					'max'         => 100,
					'placeholder' => 100,
					'css'         => [
						[
							'property' => 'width',
						],
					],
					'required'    => [ 'type', '!=', [ 'hidden' ] ],
				],

				'height'                 => [
					'label'    => esc_html__( 'Height', 'bricks' ),
					'type'     => 'number',
					'units'    => true,
					'css'      => [
						[
							'property' => 'height',
						],
					],
					'required' => [ 'type', '=', [ 'textarea' ] ],
				],

				'fileUploadAllowedTypes' => [
					'label'       => esc_html__( 'Allowed file types', 'bricks' ),
					'placeholder' => 'pdf,jpg,...',
					'type'        => 'text',
					'required'    => [ 'type', '=', 'file' ],
				],

				// @since 1.4 (File upload button style here)
				'fileUploadTypography'   => [
					'tab'      => 'content',
					'label'    => esc_html__( 'Typography', 'bricks' ),
					'type'     => 'typography',
					'css'      => [
						[
							'property' => 'font',
							'selector' => '.choose-files',
						],
					],
					'required' => [ 'type', '=', 'file' ],
				],

				'fileUploadBackground'   => [
					'tab'      => 'content',
					'label'    => esc_html__( 'Background', 'bricks' ),
					'type'     => 'color',
					'css'      => [
						[
							'property' => 'background-color',
							'selector' => '.choose-files',
						],
					],
					'required' => [ 'type', '=', 'file' ],
				],

				'fileUploadBorder'       => [
					'tab'      => 'content',
					'label'    => esc_html__( 'Border', 'bricks' ),
					'type'     => 'border',
					'css'      => [
						[
							'property' => 'border',
							'selector' => '.choose-files',
						],
					],
					'required' => [ 'type', '=', 'file' ],
				],

				'time'                   => [
					'label'    => esc_html__( 'Enable time', 'bricks' ),
					'type'     => 'checkbox',
					'required' => [ 'type', '=', [ 'datepicker' ] ],
				],

				'l10n'                   => [
					'label'       => esc_html__( 'Language', 'bricks' ),
					'type'        => 'text',
					'inline'      => true,
					'description' => sprintf(
						'<a href="https://github.com/flatpickr/flatpickr/tree/master/src/l10n" target="_blank">%s</a> (de, es, fr, etc.)',
						esc_html__( 'Language codes', 'bricks' ),
					),
					'required' => [ 'type', '=', [ 'datepicker' ] ],
				],

				'minTime'                => [
					'label'       => esc_html__( 'Min. time', 'bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( '09:00', 'bricks' ),
					'required'    => [ 'time', '!=', '' ],
				],

				'maxTime'                => [
					'label'       => esc_html__( 'Max. time', 'bricks' ),
					'type'        => 'text',
					'placeholder' => esc_html__( '20:00', 'bricks' ),
					'required'    => [ 'time', '!=', '' ],
				],

				'required'               => [
					'label'    => esc_html__( 'Required', 'bricks' ),
					'type'     => 'checkbox',
					'inline'   => true,
					'required' => [ 'type', '!=', [ 'hidden' ] ],
				],

				'options'                => [
					'label'    => esc_html__( 'Options (one per line)', 'bricks' ),
					'type'     => 'textarea',
					'required' => [ 'type', '=', [ 'checkbox', 'select', 'radio' ] ],
				]
			],
			'default'       => [
				[
					'type'        => 'text',
					'label'       => esc_html__( 'Name', 'bricks' ),
					'placeholder' => esc_html__( 'Your Name', 'bricks' ),
					'id'          => Helpers::generate_random_id( false ),
				],
				[
					'type'        => 'email',
					'label'       => esc_html__( 'Email', 'bricks' ),
					'placeholder' => esc_html__( 'Your Email', 'bricks' ),
					'required'    => true,
					'id'          => Helpers::generate_random_id( false ),
				],
				[
					'type'        => 'textarea',
					'label'       => esc_html__( 'Message', 'bricks' ),
					'placeholder' => esc_html__( 'Your Message', 'bricks' ),
					'required'    => true,
					'id'          => Helpers::generate_random_id( false ),
				],
			],
		];

		$this->controls['requiredAsterisk'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Show required asterisk', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['showLabels'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Show labels', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['labelTypography'] = [
			'tab'      => 'content',
			'group'    => 'fields',
			'label'    => esc_html__( 'Label typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'label',
				],
			],
			'required' => [ 'showLabels' ],
		];

		$this->controls['placeholderTypography'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Placeholder typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '::placeholder',
				],
				[
					'property' => 'font',
					'selector' => 'select',
				],
			],
		];

		// Field

		$this->controls['fieldSeparator'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Field', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['fieldMargin'] = [
			'tab'         => 'content',
			'group'       => 'fields',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				// Use padding (as margin results in line-breaks)
				[
					'property' => 'padding',
					'selector' => '.form-group:not(.submit-button-wrapper)',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => '20px',
				'left'   => 0,
			],
		];

		$this->controls['fieldPadding'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.form-group input',
				],
				[
					'property' => 'padding',
					'selector' => '.flatpickr',
				],
				[
					'property' => 'padding',
					'selector' => 'select',
				],
				[
					'property' => 'padding',
					'selector' => 'textarea',
				],
			],
		];

		$this->controls['horizontalAlignFields'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Alignment', 'bricks' ),
			'type'  => 'justify-content',
			'css'   => [
				[
					'property' => 'justify-content',
				],
			],
		];

		$this->controls['fieldBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.form-group input',
				],
				[
					'property' => 'background-color',
					'selector' => '.flatpickr',
				],
				[
					'property' => 'background-color',
					'selector' => 'select',
				],
				[
					'property' => 'background-color',
					'selector' => 'textarea',
				],
			],
		];

		$this->controls['fieldBorder'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.form-group input',
				],
				[
					'property' => 'border',
					'selector' => '.flatpickr',
				],
				[
					'property' => 'border',
					'selector' => 'select',
				],
				[
					'property' => 'border',
					'selector' => 'textarea',
				],
				[
					'property' => 'border',
					'selector' => '.bricks-button',
				],
				[
					'property' => 'border',
					'selector' => '.choose-files',
				],
			],
		];

		$this->controls['fieldTypography'] = [
			'tab'   => 'content',
			'group' => 'fields',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.form-group input',
				],
				[
					'property' => 'font',
					'selector' => 'select',
				],
				[
					'property' => 'font',
					'selector' => 'textarea',
				],
			],
		];

		// Group: Submit Button

		$this->controls['submitButtonText'] = [
			'tab'         => 'content',
			'group'       => 'submitButton',
			'label'       => esc_html__( 'Text', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Send', 'bricks' ),
		];

		$this->controls['submitButtonSize'] = [
			'tab'     => 'content',
			'group'   => 'submitButton',
			'label'   => esc_html__( 'Size', 'bricks' ),
			'type'    => 'select',
			'inline'  => true,
			'options' => $this->control_options['buttonSizes'],
		];

		$this->controls['submitButtonStyle'] = [
			'tab'         => 'content',
			'group'       => 'submitButton',
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => $this->control_options['styles'],
			'default'     => 'primary',
			'placeholder' => esc_html__( 'Custom', 'bricks' ),
		];

		$this->controls['submitButtonWidth'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Width', 'bricks' ) . ' (%)',
			'type'  => 'number',
			'unit'  => '%',
			'css'   => [
				[
					'property' => 'width',
					'selector' => '.submit-button-wrapper',
				],
			],
		];

		$this->controls['submitButtonMargin'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.submit-button-wrapper',
				],
			],
		];

		$this->controls['submitButtonTypography'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-button',
				]
			],
		];

		$this->controls['submitButtonBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-button',
				]
			],
		];

		$this->controls['submitButtonBorder'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'button[type=submit].bricks-button',
				],
			],
		];

		$this->controls['submitButtonIcon'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['submitButtonIconPosition'] = [
			'tab'         => 'content',
			'group'       => 'submitButton',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'required'    => [ 'submitButtonIcon', '!=', '' ],
		];

		// Group: Actions

		$this->controls['actions'] = [
			'tab'         => 'content',
			'group'       => 'actions',
			'type'        => 'select',
			'label'       => esc_html__( 'Actions after successful form submit', 'bricks' ),
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'options'     => Integrations\Form\Init::get_available_actions(),
			'multiple'    => true,
			'description' => esc_html__( 'Select action(s) you want to perform after form has been successfully submitted.', 'bricks' ),
			'default'     => [ 'email' ],
		];

		$this->controls['info'] = [
			'tab'      => 'content',
			'group'    => 'actions',
			'content'  => esc_html__( 'You did not select any action(s). So when this form it submitted nothing happens.', 'bricks' ),
			'type'     => 'info',
			'required' => [ 'actions', '=', '' ],
		];

		$this->controls['successMessage'] = [
			'tab'     => 'content',
			'group'   => 'actions',
			'label'   => esc_html__( 'Success message', 'bricks' ),
			'type'    => 'text',
			'default' => esc_html__( 'Message successfully sent. We will get back to you as soon as possible.', 'bricks' ),
		];

		// Group: Email

		$this->controls['emailInfo'] = [
			'tab'     => 'content',
			'group'   => 'email',
			'type'    => 'info',
			'content' => esc_html__( 'Use any form field value via it\'s ID like this: {{form_field}}. Replace "form_field" with the actual field ID.', 'bricks' ),
		];

		$this->controls['emailSubject'] = [
			'tab'     => 'content',
			'group'   => 'email',
			'label'   => esc_html__( 'Subject', 'bricks' ),
			'type'    => 'text',
			'default' => 'Contact form request',
		];

		$this->controls['emailTo'] = [
			'tab'       => 'content',
			'group'     => 'email',
			'label'     => esc_html__( 'Send to email address', 'bricks' ),
			'type'      => 'select',
			'options'   => [
				'admin_email' => sprintf( '%s (' . get_option( 'admin_email' ) . ')', esc_html__( 'Admin email', 'bricks' ) ),
				'custom'      => esc_html__( 'Custom email address', 'bricks' ),
			],
			'default'   => 'admin_email',
			'clearable' => false,
		];

		$this->controls['emailToCustom'] = [
			'tab'         => 'content',
			'group'       => 'email',
			'label'       => esc_html__( 'Send to custom email address', 'bricks' ),
			'description' => esc_html__( 'Accepts multiple addresses separated by comma', 'bricks' ),
			'type'        => 'text',
			'required'    => [ 'emailTo', '=', 'custom' ],
		];

		$this->controls['emailBcc'] = [
			'tab'   => 'content',
			'group' => 'email',
			'label' => esc_html__( 'BCC email address', 'bricks' ),
			'type'  => 'text',
		];

		$this->controls['fromEmail'] = [
			'tab'   => 'content',
			'group' => 'email',
			'label' => esc_html__( 'From email address', 'bricks' ),
			'type'  => 'text',
		];

		$this->controls['fromName'] = [
			'tab'     => 'content',
			'group'   => 'email',
			'label'   => esc_html__( 'From name', 'bricks' ),
			'type'    => 'text',
			'default' => get_option( 'blogname' ),
		];

		$this->controls['replyToEmail'] = [
			'tab'         => 'content',
			'group'       => 'email',
			'label'       => esc_html__( 'Reply to email address', 'bricks' ),
			'type'        => 'text',
			'description' => esc_html__( 'Default: Email submitted via form.', 'bricks' ),
		];

		$this->controls['emailContent'] = [
			'tab'         => 'content',
			'group'       => 'email',
			'label'       => esc_html__( 'Email content', 'bricks' ),
			'type'        => 'textarea',
			'description' => esc_html__( 'Use field IDs to personalize your message.', 'bricks' ),
		];

		$this->controls['emailErrorMessage'] = [
			'tab'     => 'content',
			'group'   => 'email',
			'label'   => esc_html__( 'Error message', 'bricks' ),
			'type'    => 'text',
			'default' => esc_html__( 'Submission failed. Please reload the page and try to submit the form again.', 'bricks' ),
		];

		$this->controls['htmlEmail'] = [
			'tab'     => 'content',
			'group'   => 'email',
			'label'   => esc_html__( 'HTML email', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		// Group: Confirmation email (@since 1.7.2)

		$this->controls['confirmationEmailDescription'] = [
			'tab'     => 'content',
			'group'   => 'confirmation',
			'type'    => 'info',
			'content' => Helpers::article_link( 'form/#confirmation-email', esc_html__( 'Please ensure SMTP is set up on this site so all outgoing emails are delivered properly.', 'bricks' ) ),
		];

		$this->controls['confirmationEmailSubject'] = [
			'tab'   => 'content',
			'group' => 'confirmation',
			'label' => esc_html__( 'Subject', 'bricks' ),
			'type'  => 'text',
		];

		$this->controls['confirmationEmailTo'] = [
			'tab'         => 'content',
			'group'       => 'confirmation',
			'label'       => esc_html__( 'Send to email address', 'bricks' ),
			'type'        => 'text',
			'description' => esc_html__( 'Default', 'bricks' ) . ': ' . esc_html__( 'Email address in submitted form', 'bricks' ),
		];

		$this->controls['confirmationFromEmail'] = [
			'tab'         => 'content',
			'group'       => 'confirmation',
			'label'       => esc_html__( 'From email address', 'bricks' ),
			'type'        => 'text',
			'description' => esc_html__( 'Default', 'bricks' ) . ': ' . esc_html__( 'Admin email', 'bricks' ),
		];

		$this->controls['confirmationFromName'] = [
			'tab'   => 'content',
			'group' => 'confirmation',
			'label' => esc_html__( 'From name', 'bricks' ),
			'type'  => 'text',
		];

		$this->controls['confirmationEmailContent'] = [
			'tab'         => 'content',
			'group'       => 'confirmation',
			'label'       => esc_html__( 'Email content', 'bricks' ),
			'type'        => 'textarea',
			'description' => esc_html__( 'Use field IDs to personalize your message.', 'bricks' ),
		];

		$this->controls['confirmationEmailHTML'] = [
			'tab'   => 'content',
			'group' => 'confirmation',
			'label' => esc_html__( 'HTML email', 'bricks' ),
			'type'  => 'checkbox',
		];

		// Group: Redirect

		$this->controls['redirectInfo'] = [
			'tab'     => 'content',
			'group'   => 'redirect',
			'content' => esc_html__( 'Redirect is only triggered after successful form submit.', 'bricks' ),
			'type'    => 'info',
		];

		$this->controls['redirectAdminUrl'] = [
			'tab'         => 'content',
			'group'       => 'redirect',
			'label'       => esc_html__( 'Redirect to admin area', 'bricks' ),
			'type'        => 'checkbox',
			'placeholder' => admin_url(),
		];

		$this->controls['redirect'] = [
			'tab'         => 'content',
			'group'       => 'redirect',
			'label'       => esc_html__( 'Custom redirect URL', 'bricks' ),
			'type'        => 'text',
			'placeholder' => get_option( 'siteurl' ),
		];

		$this->controls['redirectTimeout'] = [
			'tab'   => 'content',
			'group' => 'redirect',
			'label' => esc_html__( 'Redirect after (ms)', 'bricks' ),
			'type'  => 'number',
		];

		// Group: Mailchimp (apiKeyMailchimp via global settings)

		$this->controls['mailchimpInfo'] = [
			'tab'      => 'content',
			'group'    => 'mailchimp',
			'content'  => sprintf(
				esc_html__( 'Mailchimp API key required! Add key in dashboard under: %s', 'bricks' ),
				'<a href="' . Helpers::settings_url( '#tab-api-keys' ) . '" target="_blank">' . esc_html__( 'Bricks > Settings > API Keys', 'bricks' ) . '</a>'
			),
			'type'     => 'info',
			'required' => [ 'apiKeyMailchimp', '=', '', 'globalSettings' ],
		];

		$this->controls['mailchimpDoubleOptIn'] = [
			'tab'      => 'content',
			'group'    => 'mailchimp',
			'label'    => esc_html__( 'Double opt-in', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'apiKeyMailchimp', '!=', '', 'globalSettings' ],
		];

		$mailchimp_list_options = [];

		foreach ( Integrations\Form\Actions\Mailchimp::get_list_options() as $list_id => $list ) {
			$mailchimp_list_options[ $list_id ] = $list['name'];
		}

		$this->controls['mailchimpList'] = [
			'tab'         => 'content',
			'group'       => 'mailchimp',
			'label'       => esc_html__( 'List', 'bricks' ),
			'placeholder' => esc_html__( 'Select list', 'bricks' ),
			'type'        => 'select',
			'options'     => $mailchimp_list_options,
			'required'    => [ 'actions', '=', 'mailchimp' ],
			'required'    => [ 'apiKeyMailchimp', '!=', '', 'globalSettings' ],
		];

		$this->controls['mailchimpGroups'] = [
			'tab'         => 'content',
			'group'       => 'mailchimp',
			'label'       => esc_html__( 'Groups', 'bricks' ),
			'placeholder' => esc_html__( 'Select group(s)', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Populate in builder via 'mailchimpList' (PanelControl.vue)
			'multiple'    => true,
			'required'    => [ 'apiKeyMailchimp', '!=', '', 'globalSettings' ],
		];

		$this->controls['mailchimpEmail'] = [
			'tab'         => 'content',
			'group'       => 'mailchimp',
			'label'       => esc_html__( 'Email field *', 'bricks' ),
			'placeholder' => esc_html__( 'Select email field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true, // NOTE: Undocumented
			'required'    => [ 'apiKeyMailchimp', '!=', '', 'globalSettings' ],
		];

		$this->controls['mailchimpFirstName'] = [
			'tab'         => 'content',
			'group'       => 'mailchimp',
			'label'       => esc_html__( 'First name', 'bricks' ),
			'placeholder' => esc_html__( 'Select first name field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true,
			'required'    => [ 'apiKeyMailchimp', '!=', '', 'globalSettings' ],
		];

		$this->controls['mailchimpLastName'] = [
			'tab'         => 'content',
			'group'       => 'mailchimp',
			'label'       => esc_html__( 'Last name', 'bricks' ),
			'placeholder' => esc_html__( 'Select last name field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true,
			'required'    => [ 'apiKeyMailchimp', '!=', '', 'globalSettings' ],
		];

		$this->controls['mailchimpPendingMessage'] = [
			'tab'      => 'content',
			'group'    => 'mailchimp',
			'label'    => esc_html__( 'Pending message', 'bricks' ),
			'type'     => 'text',
			'required' => [ 'apiKeyMailchimp', '!=', '', 'globalSettings' ],
			'default'  => esc_html__( 'Please check your email to confirm your subscription.', 'bricks' ),
		];

		$this->controls['mailchimpErrorMessage'] = [
			'tab'      => 'content',
			'group'    => 'mailchimp',
			'label'    => esc_html__( 'Error message', 'bricks' ),
			'type'     => 'text',
			'required' => [ 'apiKeyMailchimp', '!=', '', 'globalSettings' ],
			'default'  => esc_html__( 'Sorry, but we could not subscribe you.', 'bricks' ),
		];

		// Group: Sendgrid (apiKeySendgrid via global settings)

		$this->controls['sendgridInfo'] = [
			'tab'      => 'content',
			'group'    => 'sendgrid',
			'content'  => sprintf(
				esc_html__( 'Sendgrid API key required! Add key in dashboard under: %s', 'bricks' ),
				'<a href="' . Helpers::settings_url( '#tab-api-keys' ) . '" target="_blank">' . esc_html__( 'Bricks > Settings > API Keys', 'bricks' ) . '</a>'
			),
			'type'     => 'info',
			'required' => [ 'apiKeySendgrid', '=', '', 'globalSettings' ],
		];

		$this->controls['sendgridList'] = [
			'tab'         => 'content',
			'group'       => 'sendgrid',
			'label'       => esc_html__( 'List', 'bricks' ),
			'placeholder' => esc_html__( 'Select list', 'bricks' ),
			'type'        => 'select',
			'options'     => Integrations\Form\Actions\Sendgrid::get_list_options(),
			'required'    => [ 'apiKeySendgrid', '!=', '', 'globalSettings' ],
		];

		$this->controls['sendgridEmail'] = [
			'tab'         => 'content',
			'group'       => 'sendgrid',
			'label'       => esc_html__( 'Email field *', 'bricks' ),
			'placeholder' => esc_html__( 'Select email field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true, // NOTE: Undocumented
			'required'    => [ 'apiKeySendgrid', '!=', '', 'globalSettings' ],
		];

		$this->controls['sendgridFirstName'] = [
			'tab'         => 'content',
			'group'       => 'sendgrid',
			'label'       => esc_html__( 'First name field', 'bricks' ),
			'placeholder' => esc_html__( 'Select first name field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true,
			'required'    => [ 'apiKeySendgrid', '!=', '', 'globalSettings' ],
		];

		$this->controls['sendgridLastName'] = [
			'tab'         => 'content',
			'group'       => 'sendgrid',
			'label'       => esc_html__( 'Last name field', 'bricks' ),
			'placeholder' => esc_html__( 'Select last name field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true,
			'required'    => [ 'apiKeySendgrid', '!=', '', 'globalSettings' ],
		];

		// NOTE: Undocumented
		if ( defined( 'BRICKS_SENDGRID_DOUBLE_OPT_IN' ) && BRICKS_SENDGRID_DOUBLE_OPT_IN ) {
			$this->controls['sendgridPendingMessage'] = [
				'tab'      => 'content',
				'group'    => 'sendgrid',
				'label'    => esc_html__( 'Pending message', 'bricks' ),
				'type'     => 'text',
				'required' => [ 'apiKeySendgrid', '!=', '', 'globalSettings' ],
				'default'  => esc_html__( 'Please check your email to confirm your subscription.', 'bricks' ),
			];
		}

		$this->controls['sendgridErrorMessage'] = [
			'tab'      => 'content',
			'group'    => 'sendgrid',
			'label'    => esc_html__( 'Error message', 'bricks' ),
			'type'     => 'text',
			'required' => [ 'apiKeySendgrid', '!=', '', 'globalSettings' ],
			'default'  => esc_html__( 'Sorry, but we could not subscribe you.', 'bricks' ),
		];

		// Group: User Login

		$this->controls['loginName'] = [
			'tab'         => 'content',
			'group'       => 'login',
			'label'       => esc_html__( 'Login field *', 'bricks' ),
			'placeholder' => esc_html__( 'Select login field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true, // NOTE: Undocumented
		];

		$this->controls['loginPassword'] = [
			'tab'         => 'content',
			'group'       => 'login',
			'label'       => esc_html__( 'Password field', 'bricks' ),
			'placeholder' => esc_html__( 'Select password field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true, // NOTE: Undocumented
		];

		// Group: User Registration

		$this->controls['registrationEmail'] = [
			'tab'         => 'content',
			'group'       => 'registration',
			'label'       => esc_html__( 'Email field *', 'bricks' ),
			'placeholder' => esc_html__( 'Select email field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true, // NOTE: Undocumented
		];

		$this->controls['registrationPassword'] = [
			'tab'         => 'content',
			'group'       => 'registration',
			'label'       => esc_html__( 'Password field', 'bricks' ),
			'placeholder' => esc_html__( 'Select password field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true, // NOTE: Undocumented
			'description' => esc_html__( 'Autogenerated if no password is required/submitted.', 'bricks' ),
		];

		$this->controls['registrationPasswordMinLength'] = [
			'tab'         => 'content',
			'group'       => 'registration',
			'label'       => esc_html__( 'Password min. length', 'bricks' ),
			'type'        => 'number',
			'placeholder' => 6,
		];

		$this->controls['registrationUserName'] = [
			'tab'         => 'content',
			'group'       => 'registration',
			'label'       => esc_html__( 'User name field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true, // NOTE: Undocumented
			'placeholder' => esc_html__( 'Select user name field', 'bricks' ),
			'description' => esc_html__( 'Auto-generated if form only requires email address for registration.', 'bricks' ),
		];

		$this->controls['registrationFirstName'] = [
			'tab'         => 'content',
			'group'       => 'registration',
			'label'       => esc_html__( 'First name field', 'bricks' ),
			'placeholder' => esc_html__( 'Select first name field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true,
		];

		$this->controls['registrationLastName'] = [
			'tab'         => 'content',
			'group'       => 'registration',
			'label'       => esc_html__( 'Last name field', 'bricks' ),
			'placeholder' => esc_html__( 'Select last name field', 'bricks' ),
			'type'        => 'select',
			'options'     => [], // Auto-populate with form fields
			'map_fields'  => true,
		];

		$this->controls['registrationAutoLogin'] = [
			'tab'         => 'content',
			'group'       => 'registration',
			'label'       => esc_html__( 'Auto log in user', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Log in user after successful registration. Tip: Set action "Redirect" to redirect user to the account/admin area.', 'bricks' ),
		];

		// Group: Spam Protection

		$this->controls['recaptchaInfo'] = [
			'tab'      => 'content',
			'group'    => 'spam',
			'content'  => sprintf(
				esc_html__( 'Google reCAPTCHA API key required! Add key in dashboard under: %s', 'bricks' ),
				'<a href="' . Helpers::settings_url( '#tab-api-keys' ) . '" target="_blank">' . esc_html__( 'Bricks > Settings > API Keys', 'bricks' ) . '</a>'
			),
			'type'     => 'info',
			'required' => [ 'apiKeyGoogleRecaptcha', '=', '', 'globalSettings' ],
		];

		$this->controls['enableRecaptcha'] = [
			'tab'      => 'content',
			'group'    => 'spam',
			'label'    => esc_html__( 'Enable reCAPTCHA', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'apiKeyGoogleRecaptcha', '!=', '', 'globalSettings' ],
		];

		// Upload Button (remove "Text" control group)

		// @since: 1.4 = deprecated (moved within repeater field. see line 225)
		$this->controls['uploadButtonTypography'] = [
			'tab'        => 'content',
			'label'      => esc_html__( 'File upload', 'bricks' ) . ' - ' . esc_html__( 'Typography', 'bricks' ),
			'type'       => 'typography',
			'css'        => [
				[
					'property' => 'font',
					'selector' => '.choose-files',
				],
			],
			'deprecated' => true,
		];

		$this->controls['uploadButtonBackgroundColor'] = [
			'tab'        => 'content',
			'label'      => esc_html__( 'File upload', 'bricks' ) . ' - ' . esc_html__( 'Background', 'bricks' ),
			'type'       => 'color',
			'css'        => [
				[
					'property' => 'background-color',
					'selector' => '.choose-files',
				],
			],
			'deprecated' => true,
		];

		$this->controls['uploadButtonBorder'] = [
			'tab'        => 'content',
			'label'      => esc_html__( 'File upload', 'bricks' ) . ' - ' . esc_html__( 'Border', 'bricks' ),
			'type'       => 'border',
			'css'        => [
				[
					'property' => 'border',
					'selector' => '.choose-files',
				],
			],
			'deprecated' => true,
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( empty( $settings['fields'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No form field added.', 'bricks' ),
				]
			);
		}

		// Fields using <input type="X" />
		$input_types = [
			'email',
			'number',
			'text',
			'tel',
			'url',
			'datepicker',
			'password',
			'file',
			'hidden',
		];

		$this->set_attribute( '_root', 'method', 'post' );

		// We need the form element ID to recover the element settings on form submit
		$this->set_attribute( '_root', 'data-element-id', $this->id );

		$this->set_attribute( 'enctype', 'method', 'multipart/form-data' );

		// Append suffix for unique label HTML attributes inside a loop (@since 1.8)
		$field_suffix = Query::is_any_looping() ? '-' . Query::is_any_looping() . '-' . Query::get_loop_index() : '';

		foreach ( $settings['fields'] as $index => $field ) {
			// Field ID generated when rendering form repeater in builder panel
			$field_id = isset( $field['id'] ) ? $field['id'] : '';

			// Get a unique field ID to avoid conflicts when the form is inside a query loop or it was duplicated
			$input_unique_id = Helpers::generate_random_id( false ) . $field_suffix;

			// Field wrapper
			if ( $field['type'] !== 'hidden' ) {
				$this->set_attribute( "field-wrapper-$index", 'class', [ 'form-group', $field['type'] === 'file' ? 'file' : '' ] );
			}

			// Field label
			if ( $field['type'] !== 'checkbox' && $field['type'] !== 'radio' ) {
				$this->set_attribute( "label-$index", 'for', "form-field-{$input_unique_id}" );
			}

			if ( $field['type'] === 'file' ) {
				if ( ! isset( $field['fileUploadLimit'] ) || $field['fileUploadLimit'] > 1 ) {
					$this->set_attribute( "field-$index", 'multiple' );
				}

				if ( ! empty( $field['fileUploadLimit'] ) ) {
					$this->set_attribute( "field-$index", 'data-limit', $field['fileUploadLimit'] );
				}

				if ( isset( $field['fileUploadAllowedTypes'] ) ) {
					$types = str_replace( '.', '', strtolower( $field['fileUploadAllowedTypes'] ) );
					$types = array_map( 'trim', explode( ',', $types ) );

					if ( in_array( 'jpg', $types ) && ! in_array( 'jpeg', $types ) ) {
						$types[] = 'jpeg';
					}

					array_walk(
						$types,
						function( &$value ) {
							$value = '.' . $value;
						}
					);

					$this->set_attribute( "field-$index", 'accept', implode( ',', $types ) );
				}

				if ( ! empty( $field['fileUploadSize'] ) ) {
					$this->set_attribute( "field-$index", 'data-maxsize', $field['fileUploadSize'] );
				}

				// Link the input file to the file preview using a unique ID (the field ID could be duplicated)
				$this->set_attribute( "field-$index", 'data-files-ref', $input_unique_id );

				$this->set_attribute( "file-preview-$index", 'data-files-ref', $input_unique_id );
			}

			if ( isset( $settings['requiredAsterisk'] ) && isset( $field['required'] ) ) {
				$this->set_attribute( "label-$index", 'class', 'required' );
			}

			// Datepicker
			if ( $field['type'] === 'datepicker' ) {
				$this->set_attribute( "field-$index", 'class', 'flatpickr' );

				$time_24h = get_option( 'time_format' );
				$time_24h = strpos( $time_24h, 'H' ) !== false || strpos( $time_24h, 'G' ) !== false;

				$date_format = isset( $field['time'] ) ? get_option( 'date_format' ) . ' H:i' : get_option( 'date_format' );

				$datepicker_options = [
					// 'allowInput' => true,
					'enableTime' => isset( $field['time'] ),
					'minTime'    => isset( $field['minTime'] ) ? $field['minTime'] : '',
					'maxTime'    => isset( $field['maxTime'] ) ? $field['maxTime'] : '',
					'altInput'   => true,
					'altFormat'  => $date_format,
					'dateFormat' => $date_format,
					'time_24hr'  => $time_24h,
					// 'today' => date( get_option('date_format') ),
					// 'minDate' => 'today',
					// 'maxDate' => 'January 01, 2020',
				];

				// Localization: https://flatpickr.js.org/localization/ (@since 1.8.6)
				if ( ! empty( $field['l10n'] ) ) {
					$datepicker_options['locale'] = $field['l10n'];
				}

				// @see: https://academy.bricksbuilder.io/article/form-element/#datepicker
				$datepicker_options = apply_filters( 'bricks/element/form/datepicker_options', $datepicker_options, $this );

				$this->set_attribute( "field-$index", 'data-bricks-datepicker-options', wp_json_encode( $datepicker_options ) );
			}

			// Number min/max
			if ( $field['type'] === 'number' ) {
				if ( isset( $field['min'] ) ) {
					$this->set_attribute( "field-$index", 'min', $field['min'] );
				}

				if ( isset( $field['max'] ) ) {
					$this->set_attribute( "field-$index", 'max', $field['max'] );
				}
			}

			$this->set_attribute( "field-$index", 'id', "form-field-{$input_unique_id}" );
			$this->set_attribute( "field-$index", 'name', "form-field-{$field_id}" );

			if ( ! empty( $field['label'] ) && $field['type'] != 'hidden' ) {
				$this->set_attribute( "field-$index", 'aria-label', $field['label'] );
			}

			// Input types type & value
			if ( in_array( $field['type'], $input_types ) ) {
				$field_type = $field['type'] == 'datepicker' ? 'text' : $field['type'];

				$this->set_attribute( "field-$index", 'type', $field_type );

				// Hidden field value
				if ( $field['type'] === 'hidden' && isset( $field['value'] ) ) {
					$this->set_attribute( "field-$index", 'value', $field['value'] );
				} elseif ( $field['type'] !== 'file' ) {
					// The type=file do not support value
					$this->set_attribute( "field-$index", 'value', '' );
				}
			}

			$placeholder_support = [
				'email',
				'number',
				'text',
				'tel',
				'url',
				'datepicker',
				'password',
				'textarea'
			];

			// Placeholder
			if ( in_array( $field['type'], $placeholder_support ) ) {
				if ( isset( $field['placeholder'] ) ) {
					if ( isset( $settings['requiredAsterisk'] ) && isset( $field['required'] ) ) {
						$field['placeholder'] = $field['placeholder'] . ' *';
					}

					$this->set_attribute( "field-$index", 'placeholder', $field['placeholder'] );
				}
			}

			// Turn off spell check for input and textarea
			if ( $field['type'] === 'text' || $field['type'] === 'textarea' ) {
				$this->set_attribute( "field-$index", 'spellcheck', 'false' );
			}

			if ( isset( $field['required'] ) ) {
				$this->set_attribute( "field-$index", 'required' );
			}

		}

		// Submit button
		$submit_button_icon_position = ! empty( $settings['submitButtonIconPosition'] ) ? $settings['submitButtonIconPosition'] : 'right';

		$this->set_attribute( 'submit-wrapper', 'class', [ 'form-group', 'submit-button-wrapper' ] );

		$submit_button_classes[] = 'bricks-button';

		if ( ! empty( $settings['submitButtonStyle'] ) ) {
			$submit_button_classes[] = "bricks-background-{$settings['submitButtonStyle']}";
		}

		if ( ! empty( $settings['submitButtonSize'] ) ) {
			$submit_button_classes[] = $settings['submitButtonSize'];
		}

		if ( isset( $settings['submitButtonCircle'] ) ) {
			$submit_button_classes[] = 'circle';
		}

		if ( ! empty( $settings['submitButtonIcon'] ) ) {
			$submit_button_classes[] = "icon-$submit_button_icon_position";
		}

		$this->set_attribute( 'submit-button', 'class', $submit_button_classes );

		/**
		 * Render
		 */
		?>
		<form <?php echo $this->render_attributes( '_root' ); ?>>
			<?php foreach ( $settings['fields'] as $index => $field ) { ?>
			<div <?php echo $this->render_attributes( "field-wrapper-$index" ); ?>>
				<?php
				if ( isset( $settings['showLabels'] ) && isset( $field['label'] ) && $field['type'] !== 'hidden' ) {
					echo "<label {$this->render_attributes( "label-$index" )}>{$field['label']}</label>";
				} elseif ( in_array( $field['type'], [ 'checkbox', 'radio' ] ) && ! empty( $field['placeholder'] ) ) {
					echo "<label {$this->render_attributes( "label-$index" )}>{$field['placeholder']}</label>";
				}
				?>

				<?php if ( in_array( $field['type'], $input_types ) ) { ?>
				<input <?php echo $this->render_attributes( "field-$index" ); ?>>
				<?php } ?>

				<?php
				if ( $field['type'] == 'file' ) {
					$label = isset( $field['fileUploadButtonText'] ) ? $field['fileUploadButtonText'] : esc_html__( 'Choose files', 'bricks' );

					$this->set_attribute( "file-preview-$index", 'class', 'file-result' );
					$this->set_attribute( "file-preview-$index", 'data-error-limit', esc_html__( 'File %s not accepted. File limit exceeded.', 'bricks' ) );
					$this->set_attribute( "file-preview-$index", 'data-error-size', esc_html__( 'File %s not accepted. Size limit exceeded.', 'bricks' ) );

					$this->set_attribute( "label-$index", 'class', 'choose-files' );
					?>
					<div <?php echo $this->render_attributes( "file-preview-$index" ); ?>>
						<span class="text"></span>
						<button type="button" class="bricks-button remove"><?php esc_html_e( 'Remove', 'bricks' ); ?></button>
					</div>

					<label <?php echo $this->render_attributes( "label-$index" ); ?>><?php echo $label; ?></label>
				<?php } ?>

				<?php if ( $field['type'] === 'textarea' ) { ?>
				<textarea <?php echo $this->render_attributes( "field-$index" ); ?>></textarea>
				<?php } ?>

				<?php if ( $field['type'] === 'select' && ! empty( $field['options'] ) ) { ?>
				<select <?php echo $this->render_attributes( "field-$index" ); ?>>
					<?php
					$select_options     = Helpers::parse_textarea_options( $field['options'] );
					$select_placeholder = false;

					if ( isset( $field['placeholder'] ) ) {
						$select_placeholder = $field['placeholder'];

						if ( isset( $settings['requiredAsterisk'] ) && isset( $field['required'] ) ) {
							$select_placeholder .= ' *';
						}

						echo '<option value="" class="placeholder">' . $select_placeholder . '</option>';
					}
					?>
					<?php foreach ( $select_options as $select_option ) { ?>
					<option value="<?php echo esc_attr( $select_option ); ?>"><?php echo esc_html( $select_option ); ?></option>
					<?php } ?>
				</select>
				<?php } ?>

				<?php if ( ! empty( $field['options'] ) && ( $field['type'] === 'checkbox' || $field['type'] === 'radio' ) ) { ?>
				<ul class="options-wrapper">
					<?php $options = Helpers::parse_textarea_options( $field['options'] ); ?>
					<?php foreach ( $options as $key => $value ) { ?>
				  <li>
						<input
							type="<?php echo esc_attr( $field['type'] ); ?>"
							id="<?php echo esc_attr( "form-field-{$field['id']}" ) . '-' . $key . $field_suffix; ?>"
							name="<?php echo esc_attr( "form-field-{$field['id']}" ); ?>[]"
							<?php
							if ( isset( $field['required'] ) ) {
								echo esc_attr( 'required' ); }
							?>
							value="<?php echo esc_attr( $value ); ?>">
							<label for="<?php echo esc_attr( "form-field-{$field['id']}" ) . '-' . $key . $field_suffix; ?>"><?php echo $value; ?></label>
					</li>
					<?php } ?>
					</ul>
				<?php } ?>
			</div>
			<?php
			}

			// Submit button icon
			$submit_button_icon = isset( $settings['submitButtonIcon'] ) ? self::render_icon( $settings['submitButtonIcon'] ) : false;

			// Reload SVG
			$loading_svg = Helpers::file_get_contents( BRICKS_PATH_ASSETS . 'svg/frontend/reload.svg' );
			?>

		  <div <?php echo $this->render_attributes( 'submit-wrapper' ); ?>>
				<button type="submit" <?php echo $this->render_attributes( 'submit-button' ); ?>>
					<?php
					if ( $submit_button_icon_position === 'left' && $submit_button_icon ) {
						echo $submit_button_icon;
					}

					if ( ! isset( $settings['submitButtonIcon'] ) || ( isset( $settings['submitButtonIcon'] ) && isset( $settings['submitButtonText'] ) ) ) {
						$this->set_attribute( 'submitButtonText', 'class', 'text' );

						$submit_button_text = isset( $settings['submitButtonText'] ) ? esc_html( $settings['submitButtonText'] ) : esc_html__( 'Send', 'bricks' );

						echo "<span {$this->render_attributes( 'submitButtonText' )}>$submit_button_text</span>";
					}

					echo '<span class="loading">' . $loading_svg . '</span>';

					if ( $submit_button_icon_position === 'right' && $submit_button_icon ) {
						echo $submit_button_icon;
					}
					?>
				</button>
			</div>

			<?php $this->render_recaptcha(); ?>
		</form>
		<?php
	}

	/**
	 * Render recaptcha attributes and error message
	 *
	 * @since 1.5
	 */
	public function render_recaptcha() {
		$settings = $this->settings;

		if ( ! isset( $settings['enableRecaptcha'] ) ) {
			return;
		}

		$recaptcha_key = ! empty( Database::$global_settings['apiKeyGoogleRecaptcha'] ) ? Database::$global_settings['apiKeyGoogleRecaptcha'] : false;

		if ( ! $recaptcha_key ) {
			return;
		}

		$this->set_attribute( 'recaptcha', 'id', 'recaptcha-' . esc_attr( $this->id ) );
		$this->set_attribute( 'recaptcha', 'data-key', $recaptcha_key );
		$this->set_attribute( 'recaptcha', 'class', 'recaptcha-hidden' );

		echo '<div class="form-group recaptcha-error">';
		echo '<div class="brxe-alert danger">';
		echo '<p>' . esc_html__( 'Google reCaptcha: Invalid site key.', 'bricks' ) . '</p>';
		echo '</div>';
		echo '</div>';

		echo "<div {$this->render_attributes( 'recaptcha' )}></div>";
	}
}
