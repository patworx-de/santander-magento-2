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
            defaultErrorMessage: 'Die von Ihnen gewählte Zahlungsart kann Ihnen leider nicht angeboten werden. Bitte wählen Sie eine andere Zahlungsart aus',
            defaults: {
                template: 'SantanderPaymentSolutions_SantanderPayments/payment/hire_form'
            },

            getCode: function () {
                return 'santander_hire';
            },

            getLogoUrl: function () {
                return window.checkoutConfig.payment.santander_hire.logo;
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

            popup: function (url) {
                var w = 600;
                var h = 600;
                var title = "";
                var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
                var dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;

                var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
                var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

                var systemZoom = width / window.screen.availWidth;
                var left = (width - w) / 2 / systemZoom + dualScreenLeft
                var top = (height - h) / 2 / systemZoom + dualScreenTop
                var newWindow = window.open(url, title, 'scrollbars=yes, width=' + w / systemZoom + ', height=' + h / systemZoom + ', top=' + top + ', left=' + left);
                if (window.focus) newWindow.focus();
                return newWindow;
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


            selectPaymentPlan: function () {
                var self = this;
                var genderVal = $('[name="santander_hire[gender]"]').val();
                if (!genderVal) {
                    alert('Bitte wählen Sie eine Anrede');
                    return false;
                }

                var birthdayDayVal = $('#santander_hire_birthday_d').val();
                var birthdayMonthVal = $('#santander_hire_birthday_m').val();
                var birthdayYearVal = $('#santander_hire_birthday_y').val();

                if (!birthdayDayVal || !birthdayMonthVal || !birthdayYearVal || self.getAge(birthdayYearVal + '-' + birthdayMonthVal + '-' + birthdayDayVal) < 18) {
                    alert('Bitte wählen Sie ein gültiges Geburtsdatum');
                    return false;
                }
                window.santanderPlanChoosingWindow = self.popup('');
                window.santanderPlanChoosingWindowFinished = false;
                window.santanderPlanChoosingWindow.onclose = window.santanderHireFinishedPaymentPlan;
                var santanderPlanCloseInterval = setInterval(function () {
                    try {
                        window.santanderPlanChoosingWindow.santanderHireFinishedPaymentPlan = window.santanderHireFinishedPaymentPlan;
                    }
                    catch (err) {
                    }
                    try {
                        if (!window.santanderPlanChoosingWindow.finished) {
                            if (window.santanderPlanChoosingWindow.closed) {
                                window.santanderHireFinishedPaymentPlan(false);
                            }
                        }
                    } catch (err) {
                    }
                }, 200);
                loader.startLoader();
                window.santanderHireFinishedPaymentPlan = function (isSuccess, pdfUrl) {
                    if (!window.santanderPlanChoosingWindowFinished) {
                        window.santanderPlanChoosingWindowFinished = true;
                        window.santanderPlanChoosingWindow.close();
                        clearInterval(santanderPlanCloseInterval);
                        loader.stopLoader();
                        if (typeof isSuccess !== 'undefined' && isSuccess) {
                            $('.santander-hire-step-1, .santander-hire-choose-payment-plan').hide();
                            $('.santander-hire-step-2, .santander-hire-place-order').show();
                            $('.santander-hire-payment-plan').html('Vielen Dank für die Auswahl Ihres Ratenplans.<br />' +
                                '                    Sie haben hier die Möglichkeit, die vorvertraglichen Informationen zu Ihrer Zahlung einzusehen und herunterzuladen:<br><br>' +
                                '                    <a href="' + pdfUrl + '" target="_blank">Vorvertragliche Informationen zum Ratenkauf mit Santander</a>');
                        }
                    }
                };
                try {
                    window.santanderPlanChoosingWindow.santanderHireFinishedPaymentPlan = window.santanderHireFinishedPaymentPlan;
                }
                catch (err) {
                }

                self.santanderBirthday = birthdayYearVal + '-' + birthdayMonthVal + '-' + birthdayDayVal;
                self.santanderGender = genderVal;


                $.post(window.checkoutConfig.payment.santander_hire.callback_url, {
                    action: 'initialize_hire',
                    'NAME_BIRTHDATE': birthdayYearVal + '-' + birthdayMonthVal + '-' + birthdayDayVal,
                    'NAME_SALUTATION': genderVal
                }, function (initializeResponse) {
                    if (typeof initializeResponse !== 'object') {
                        initializeResponse = JSON.parse(initializeResponse);
                    }
                    if (initializeResponse.redirect_url) {
                        window.santanderPlanChoosingWindow.location.href = initializeResponse.redirect_url;
                    } else {
                        window.santanderHireFinishedPaymentPlan(false);
                    }

                });
                return false;
            },

            getYears: function () {
                var cYear = new Date().getFullYear();
                var years = [''];
                for (var year = cYear; year >= cYear - 120; year--) {
                    years.push(year);
                }
                return years;
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
                loader.startLoader();

                self.isPlaceOrderActionAllowed(false);
                $.post(window.checkoutConfig.payment.santander_invoice.callback_url, {action: 'authorize_on_registration'}, function (authorizeOnRegistrationResponse) {
                    if (typeof authorizeOnRegistrationResponse !== 'object') {
                        authorizeOnRegistrationResponse = JSON.parse(authorizeOnRegistrationResponse);
                    }
                    if (authorizeOnRegistrationResponse.success) {
                        $.ajax({
                            type: "POST",
                            url: authorizeOnRegistrationResponse.redirect_url,
                            data: {
                                'NAME.BIRTHDATE': self.santanderBirthday,
                                'NAME.SALUTATION': self.santanderGender
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
                                        alert(self.defaultErrorMessage);
                                        getPaymentInformation();
                                        self.isPlaceOrderActionAllowed(true);
                                        loader.stopLoader();
                                    }


                                });
                            }
                        });
                    } else {
                        alert(self.defaultErrorMessage);
                        self.isPlaceOrderActionAllowed(true);
                        loader.stopLoader();
                    }
                });
                return false;
            }

        });
    }
);