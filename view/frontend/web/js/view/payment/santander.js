/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'santander_invoice',
                component: 'SantanderPaymentSolutions_SantanderPayments/js/view/payment/method-renderer/santander_invoice'
            }
        );
        rendererList.push(
            {
                type: 'santander_hire',
                component: 'SantanderPaymentSolutions_SantanderPayments/js/view/payment/method-renderer/santander_hire'
            }
        );
        rendererList.push(
            {
                type: 'santander_instant',
                component: 'SantanderPaymentSolutions_SantanderPayments/js/view/payment/method-renderer/santander_instant'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
