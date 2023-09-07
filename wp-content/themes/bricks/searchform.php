<?php
$search_text = ! empty( $settings['placeholder'] ) ? $settings['placeholder'] : esc_html__( 'Search ...', 'bricks' );
$button_text = ! empty( $settings['buttonText'] ) ? $settings['buttonText'] : '';
$aria_label  = ! empty( $settings['buttonAriaLabel'] ) ? $settings['buttonAriaLabel'] : '';
$icon        = ! empty( $settings['icon'] ) ? Bricks\Element::render_icon( $settings['icon'], [ 'overlay-trigger' ] ) : false;
$for         = isset( $element_id ) ? "search-input-{$element_id}" : 'search-input';
$input_name  = 's'; // NOTE: Allow setting custom name avlue (needed for SearchWP)?
?>

<form role="search" method="get" class="bricks-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $for ); ?>" class="screen-reader-text"><span><?php esc_html_e( 'Search ...', 'bricks' ); ?></span></label>
	<input type="search" placeholder="<?php esc_attr_e( $search_text ); ?>" value="<?php echo get_search_query(); ?>" name="s" id="<?php echo esc_attr( $for ); ?>" />

	<?php
	if ( $icon || $button_text ) {
		if ( $aria_label ) {
			echo '<button type="submit" aria-label="' . esc_attr( $aria_label ) . '">' . $icon . $button_text . '</button>';
		} else {
			echo '<button type="submit">' . $icon . $button_text . '</button>';
		}
	}
	?>
</form>
