<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Assets_Global_Elements {
	public function __construct() {
		add_action( 'add_option_bricks_global_elements', [ $this, 'updated' ], 10, 2 );
		add_action( 'update_option_bricks_global_elements', [ $this, 'updated' ], 10, 2 );
	}

	public function updated( $mix, $value ) {
		self::generate_css_file( $value );
	}

	public static function generate_css_file( $global_elements ) {
		$inline_css = '';

		foreach ( $global_elements as $global_element ) {
			// @since 1.2.1 Global element has 'global' instead of 'id' property
			if ( ! empty( $global_element['global'] ) ) {
				$global_element['id'] = $global_element['global'];
			}

			$element_controls = Elements::get_element( $global_element, 'controls' );

			$inline_css .= Assets::generate_inline_css_from_element( $global_element, $element_controls, 'global_elements' );
		}

		$inline_css = Assets::minify_css( $inline_css );

		$file_name     = 'global-elements.min.css';
		$css_file_path = Assets::$css_dir . "/$file_name";

		if ( $inline_css ) {
			$file = fopen( $css_file_path, 'w' );
			fwrite( $file, $inline_css );
			fclose( $file );

			return $file_name;
		} else {
			if ( file_exists( $css_file_path ) ) {
				unlink( $css_file_path );
			}
		}
	}
}
