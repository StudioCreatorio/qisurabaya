<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Counter extends Element {
	public $category = 'general';
	public $name     = 'counter';
	public $icon     = 'ti-dashboard';
	public $scripts  = [ 'bricksCounter' ];

	public function get_label() {
		return esc_html__( 'Counter', 'bricks' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'bricks-counter' );
	}

	public function set_controls() {
		$this->controls['countFrom'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Count from', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => 0,
		];

		$this->controls['countTo'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Count to', 'bricks' ),
			'type'    => 'text',
			'inline'  => true,
			'default' => 1000,
		];

		$this->controls['duration'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Animation in ms', 'bricks' ),
			'type'        => 'number',
			'small'       => false,
			'placeholder' => 1000,
			'rerender'    => true,
		];

		$this->controls['prefix'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Prefix', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'rerender'       => true,
		];

		$this->controls['suffix'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Suffix', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'rerender'       => true,
		];

		// Auto-set via JS: toLocaleString()
		$this->controls['thousandSeparator'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Thousand separator', 'bricks' ),
			'type'     => 'checkbox',
			'rerender' => true,
		];
	}

	public function render() {
		$settings   = $this->settings;
		$count_from = isset( $settings['countFrom'] ) ? $settings['countFrom'] : 0;
		$count_to   = isset( $settings['countTo'] ) ? $settings['countTo'] : 100;
		$prefix     = ! empty( $settings['prefix'] ) ? $settings['prefix'] : false;
		$suffix     = ! empty( $settings['suffix'] ) ? $settings['suffix'] : false;

		$this->set_attribute(
			'_root',
			'data-bricks-counter-options',
			wp_json_encode(
				[
					'countFrom' => $this->render_dynamic_data( $count_from ),
					'countTo'   => $this->render_dynamic_data( $count_to ),
					'duration'  => ! empty( $settings['duration'] ) ? intval( $settings['duration'] ) : 1000,
					'thousands' => ! empty( $settings['thousandSeparator'] ) ? $settings['thousandSeparator'] : '',
				]
			)
		);

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( $prefix ) {
			echo "<span class=\"prefix\">$prefix</span>";
		}

		echo "<span class=\"count\">$count_from</span>";

		if ( $suffix ) {
			echo "<span class=\"suffix\">$suffix</span>";
		}

		echo '</div>';
	}

	/**
	 * No longer in use since @1.5.4 as it can't render DD (from countFrom, countTo setting)
	 */
	public static function __render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-counter">
			<div
				:data-bricks-counter-options="JSON.stringify({
				countFrom: settings.hasOwnProperty('countFrom') ? parseInt(settings.countFrom) : 0,
				countTo: settings.hasOwnProperty('countTo') ? parseInt(settings.countTo) : 100,
				duration: settings.hasOwnProperty('duration') ? parseInt(settings.duration) : 1000,
				prefix: settings.hasOwnProperty('prefix') ? settings.prefix : '',
				suffix: settings.hasOwnProperty('suffix') ? settings.suffix : '',
				thousands: settings.hasOwnProperty('thousandSeparator') ? settings.thousandSeparator : ''
			})">
				<span class="prefix" v-if="settings.prefix" v-text="settings.prefix"></span>
				<span class="count">{{settings.hasOwnProperty('countFrom') ? parseInt(settings.countFrom) : 0}}</span>
				<span class="suffix" v-if="settings.suffix" v-text="settings.suffix"></span>
			</div>
		</script>
		<?php
	}
}
