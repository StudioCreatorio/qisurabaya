<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Text_Link extends Element {
	public $block    = 'core/paragraph';
	public $category = 'basic';
	public $name     = 'text-link';
	public $icon     = 'ti-link';

	public function get_label() {
		return esc_html__( 'Text link', 'bricks' );
	}

	public function get_keywords() {
		return [ 'menu' ];
	}

	public function set_control_groups() {
		$this->control_groups['icon'] = [
			'title' => esc_html__( 'Icon', 'bricks' ),
		];
	}

	public function set_controls() {
		$this->controls['text'] = [
			'type'    => 'text',
			'default' => esc_html__( 'Text link', 'bricks' ),
		];

		$this->controls['link'] = [
			'label' => esc_html__( 'Link to', 'bricks' ),
			'type'  => 'link',
		];

		// ICON

		$this->controls['icon'] = [
			'group' => 'icon',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];

		$this->controls['iconSize'] = [
			'group'    => 'icon',
			'label'    => esc_html__( 'Size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'selector' => '.icon > i',
					'property' => 'font-size',
				],
				[
					'selector' => '.icon > svg',
					'property' => 'width',
				],
				[
					'selector' => '.icon > svg',
					'property' => 'height',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconWidth'] = [
			'group'    => 'icon',
			'label'    => esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'selector' => '.icon',
					'property' => 'width',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconHeight'] = [
			'group'    => 'icon',
			'label'    => esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'selector' => '.icon',
					'property' => 'height',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconColor'] = [
			'group'    => 'icon',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'selector' => '.icon',
					'property' => 'color',
				],
				[
					'selector' => '.icon',
					'property' => 'fill',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconBackground'] = [
			'group'    => 'icon',
			'label'    => esc_html__( 'Background color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'selector' => '.icon',
					'property' => 'background-color',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconBorder'] = [
			'group'    => 'icon',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'selector' => '.icon',
					'property' => 'border',
				],
				[
					'selector' => '.icon',
					'property' => 'overflow',
					'value'    => 'hidden',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconPosition'] = [
			'group'       => 'icon',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Left', 'bricks' ),
			'required'    => [ 'icon', '!=', '' ],
			'css'         => [
				[
					'selector' => '',
					'property' => 'flex-direction',
					'value'    => 'row-reverse',
					'required' => 'right',
				],
			],
		];

		$this->controls['gap'] = [
			'group'    => 'icon',
			'label'    => esc_html__( 'Gap', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'large'    => true,
			'required' => ['icon', '!=', ''],
			'css' => [
				[
					'selector' => '',
					'property' => 'gap',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;
		$text     = ! empty( $settings['text'] ) ? $settings['text'] : '';
		$link     = ! empty( $settings['link'] ) ? $settings['link'] : '';
		$icon     = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : '';

		if ( $link ) {
			$this->set_link_attributes( '_root', $link );
		}

		echo "<a {$this->render_attributes( '_root' )}>";

		if ( $icon ) {
			echo '<span class="icon">' . $icon . '</span>';

			if ( $text ) {
				echo '<span class="text">' . $text . '</span>';
			}
		} else if ( $text ) {
			echo $text;
		}

		echo '</a>';
	}

	public static function _render_builder() { ?>
		<script type="text/x-template" id="tmpl-bricks-element-text-link">
			<contenteditable
				tag="a"
				:name="name"
				controlKey="text"
				toolbar="style align link"
				lineBreak="br"
				:settings="settings"/>
		</script>
		<?php
	}

	// TODO
	// public function convert_element_settings_to_block( $settings ) {
	// if ( empty( $settings['text'] ) ) {
	// return;
	// }

	// $block = [
	// 'blockName'    => $this->block,
	// 'attrs'        => [],
	// 'innerContent' => [ trim( $settings['text'] ) ],
	// ];

	// return $block;
	// }

	// NOTE: Convert block to element settings: Use Bricks "Rich Text" element instead
	// public function convert_block_to_element_settings( $block, $attributes ) {}
}
