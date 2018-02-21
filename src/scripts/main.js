let $ = window.jQuery;
let ajax_url = window.ajax_url;

let App = {

    init() {
        if ($('body').hasClass('woocommerce-checkout')) {
            this.initShippingSelector();
        }
    },

    initShippingSelector() {
        let $form = $('#woomsa-shipping-addresses');
        let $selector = $form.find('select');

        $selector.on('change', function () {
            let selected_option = $(this).val();

            $.post(ajax_url, {
                    action: 'woomsa_get_shipping_address_details',
                    address: selected_option,
                },
                function (response) {
                    if (response) {
                        // Populate shipping fields with address
                        for (let [key, value] of Object.entries(response)) {
                            if (key === 'country') {
                                $('[name="shipping_' + key + '"]').select2('trigger', 'select', {
                                    data: {
                                        id: value,
                                    },
                                });
                            } else {
                                $('[name="shipping_' + key + '"]').val(value);
                            }
                        }
                        $(document.body).trigger('update_checkout');
                    }
                },
                'json');
        });
    }
};

$(document).ready(App.init());