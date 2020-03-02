/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'SantanderPaymentSolutions_SantanderPayments/payment/instant_form'
            },
            getCode: function () {
                return 'santander_instant';
            },

            getLogoUrl: function () {
                return window.checkoutConfig.payment.santander_instant.logo;
            },

            getData: function () {
                return {
                    'method': this.item.method
                };
            },
            redirectAfterPlaceOrder:false,
            afterPlaceOrder: function(){
                console.log('APO');
                window.location.href = window.checkoutConfig.payment.santander_instant.redirectUrl;
            }
        });
    }
);

