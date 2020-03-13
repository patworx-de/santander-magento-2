/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/action/get-payment-information',
        'Magento_Checkout/js/model/full-screen-loader',
        'jquery',
    ],
    function (Component, additionalValidators, redirectOnSuccessAction, getPaymentInformation, loader, $) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'SantanderPaymentSolutions_SantanderPayments/payment/invoice_form'
            },

            getCode: function () {
                return 'santander_invoice';
            },

            getLogoUrl: function () {
                return window.checkoutConfig.payment.santander_invoice.logo;
            },

            getPrivacyOptinText: function () {
                return window.checkoutConfig.payment.santander_invoice.privacy_optin;
            },

            getAdditionalOptinText: function () {
                return window.checkoutConfig.payment.santander_invoice.additional_optin;
            },

            getDays: function () {
                var days = [''];
                for (var day = 1; day <= 31; day++) {
                    days.push(String("0" + String(day)).slice(-2));
                }
                return days;
            },

            getMonths: function () {
                var months = [''];
                for (var month = 1; month <= 12; month++) {
                    months.push(String("0" + String(month)).slice(-2));
                }
                return months;
            },

            getYears: function () {
                var cYear = new Date().getFullYear();
                var years = [''];
                for (var year = cYear; year >= cYear - 120; year--) {
                    years.push(year);
                }
                return years;
            },

            getAge: function(birthDateString) {
                var today = new Date();
                var birthDate = new Date(birthDateString);
                var age = today.getFullYear() - birthDate.getFullYear();
                var m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                return age;
            },

            getData: function () {
                return {
                    'method': this.item.method
                };
            },
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (!$('[name="santander_invoice[optin]"]').is(':checked')) {
                    alert('Bitte akzeptieren Sie die Datenschutzerklärung von Santander');
                    return false;
                }

                var genderVal = $('[name="santander_invoice[gender]"]').val();
                if (!genderVal) {
                    alert('Bitte wählen Sie eine Anrede');
                    return false;
                }

                var birthdayDayVal = $('#santander_invoice_birthday_d').val();
                var birthdayMonthVal = $('#santander_invoice_birthday_m').val();
                var birthdayYearVal = $('#santander_invoice_birthday_y').val();

                if (!birthdayDayVal || !birthdayMonthVal || !birthdayYearVal || self.getAge(birthdayYearVal + '-' + birthdayMonthVal + '-' + birthdayDayVal) < 18) {
                    alert('Bitte wählen Sie ein gültiges Geburtsdatum');
                    return false;
                }



                var defaultErrorMessage = 'Die von Ihnen gewählte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte wählen Sie eine andere Zahlungsart aus';


                loader.startLoader();
                if (self.validate() && additionalValidators.validate()) {
                    self.isPlaceOrderActionAllowed(false);
                    $.post(window.checkoutConfig.payment.santander_invoice.callback_url, {action: 'reauthorize_invoice'}, function (reauthorizeResponse) {
                        if (typeof reauthorizeResponse !== 'object') {
                            reauthorizeResponse = JSON.parse(reauthorizeResponse);
                        }
                        if (reauthorizeResponse.success) {
                            $.ajax({
                                type: "POST",
                                url: reauthorizeResponse.redirect_url,
                                data: {
                                    'NAME.BIRTHDATE': birthdayYearVal + '-' + birthdayMonthVal + '-' + birthdayDayVal,
                                    'NAME.SALUTATION': genderVal,
                                    'CUSTOMER.ACCEPT_PRIVACY_POLICY':'TRUE'
                                },
                                complete: function () {
                                    $.get(window.checkoutConfig.payment.santander_invoice.callback_url, function (data) {
                                        if (typeof data !== 'object') {
                                            data = JSON.parse(data);
                                        }
                                        if (data.success) {

                                            self.getPlaceOrderDeferredObject()
                                                .fail(
                                                    function () {
                                                        location.reload();
                                                    }
                                                ).done(
                                                function () {
                                                    self.afterPlaceOrder();
                                                    if (self.redirectAfterPlaceOrder) {
                                                        redirectOnSuccessAction.execute();
                                                    }
                                                }
                                            );
                                        } else {
                                            alert(defaultErrorMessage);
                                            getPaymentInformation();
                                            self.isPlaceOrderActionAllowed(true);
                                            loader.stopLoader();
                                        }


                                    });
                                }
                            });
                        } else {
                            alert(defaultErrorMessage);
                            self.isPlaceOrderActionAllowed(true);
                            loader.stopLoader();
                        }
                    })


                }
                return false;
            }

        });
    }
);

