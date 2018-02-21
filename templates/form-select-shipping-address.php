<div id="woomsa-shipping-addresses">
    <div class="field">
        <label for="woomsa-shipping-option"><?= __( 'Select saved shipping address', 'woomsa' ); ?></label>
        <select name="woomsa-shipping-option"
                id="woomsa-shipping-option">
            <option value=""><?= __( 'Default shipping address', 'woomsa' ); ?></option>
			<?php foreach ( $shipping_addresses as $value => $label ) : ?>
                <option value="<?= esc_attr( $value ); ?>"><?= esc_html( $label ); ?></option>
			<?php endforeach; ?>
        </select>
    </div>
</div>