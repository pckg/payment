(function ($, http) {

    $(document).ready(function () {
        $('#paymill-sepa').paymentPaymillSepa();
        $('#paymill-paypal').paymentPaymillPaypal();
        $('#paymill').paymentPaymill();
        $('#payment-methods').paymentSelector();
    });

    $.fn.paymentSelector = function () {

        var $this = $(this);

        $this.init = function () {
            $this.find('div.method > label').on('click', $this.selectPaymentMethod);
        };

        $this.selectPaymentMethod = function () {
            var $label = $(this);
            $label.closest('.methods').find('> .method').removeClass('active');
            $label.closest('.method').addClass('active');
        };

        if ($this.length > 0) {
            $this.init();
        }

    };

    $.fn.paymentShared = function ($this) {

        $this.disableSubmit = function () {
            $this.find('input[type=button][name=paymill]').attr('disabled', 'disabled');
        };

        $this.enableSubmit = function () {
            $this.find('input[type=button][name=paymill]').attr('disabled', false);
        };

        $this.addErrors = function (errors) {
            $.each(errors, function (name, error) {
                $this.find('[name=' + name + ']').after('<strong class="error">' + error + '</strong>');
            });
        };

        $this.removeErrors = function () {
            $this.find('strong.error').detach();
            $this.find('#paymill-error').addClass('hidden');
        };

        return $this;

    };

    $.fn.paymentPromo = function () {

        var $this = $.fn.paymentShared($(this));

        $this.init = function () {
            $this.find('input[type=button][name=apply_promo]').on('click', $this.promoHandler);

            if ($this.find('#paymill-promo-code').val()) {
                $this.find('input[type=button][name=apply_promo]').click();
            }
        };

        $this.promoHandler = function () {
            $this.removeErrors();
            $this.find('.promo-notice').html('');

            $.post($(this).attr('data-url'), {
                promo_code: $this.find('#paymill-promo-code').val(),
                _token: $this.find('[name=_token]').val()
            }).success(function (data) {
                $this.find('input[type=button][name=paymill]').val(data.new_button);
                $this.find('#paymill-amount').val(data.new_handler_price);
                $this.find('.promo-notice').html(data.promo_notice);
                $this.enableSubmit();

            }).error(function (data) {
                $this.addErrors(data.responseJSON);
                $this.disableSubmit();
            });
        };

        $this.init();

    };

    $.fn.paymentPaymillSepa = function () {

        var $this = $.fn.paymentShared($(this));

        $this.paymentPromo();

        $this.init = function () {
            $this.find('input[type=button][name=paymill]').on('click', $this.validationHandler);
        };

        $this.validationHandler = function () {
            if ($this.find('input[type=button][name=paymill]').attr('disabled') == 'disabled') {
                return false;
            }

            $this.removeErrors();

            $.post($this.find('form').attr('action'), $.extend($this.collectData(), {
                _token: $this.find('[name=_token]').val()
            })).success(function (data) {
                http.autoHandle(data, function (data) {
                    $this.submitHandler();
                });
            }).error(function (data) {
                $this.addErrors(data.responseJSON);
                $this.disableSubmit();
            });
        };

        $this.submitHandler = function () {
            paymill.createToken($this.collectData(), $this.createTokenCallback);
        };

        $this.createTokenCallback = function (error, result) {
            if (error !== null) {
                $this.find('#paymill-error').removeClass('hidden');

            } else {
                $this.find('#paymill-error').addClass('hidden');
                $.post($this.find('#paymill-url').val(), $.extend($this.collectData(), {
                    token: result.token,
                    _token: $this.find('[name=_token]').val()
                }), function (data) {
                    http.autoHandle(data);
                });
            }

        };

        $this.collectData = function () {
            return {
                accountholder: $this.find('#paymill-card-holder').val(),
                iban: $this.find('#paymill-card-iban').val(),
                bic: $this.find('#paymill-card-bic').val()
            };
        };

        $this.init();

    };

    $.fn.paymentPaymillPaypal = function () {

        var $this = $.fn.paymentShared($(this));

        $this.paymentPromo();

        $this.init = function () {
            $this.find('input[type=button][name=paymill]').on('click', $this.validationHandler);
        };

        $this.validationHandler = function () {
            if ($this.find('input[type=button][name=paymill]').attr('disabled') == 'disabled') {
                return false;
            }

            $this.removeErrors();

            $.post($this.find('form').attr('action'), $.extend($this.collectData(), {
                _token: $this.find('[name=_token]').val()
            })).success(function (data) {
                http.autoHandle(data, function (data) {
                    $this.find('#paymill-checksum').val(data.checksum);
                    $this.submitHandler();
                });
            }).error(function (data) {
                $this.addErrors(data.responseJSON);
                $this.disableSubmit();
            });
        };

        $this.submitHandler = function () {
            paymill.createTransaction($this.collectData(), $this.createTokenCallback)
        };

        $this.createTokenCallback = function (error, result) {
            if (error !== null) {
                $this.find('#paymill-error').removeClass('hidden');

            } else {
                $this.find('#paymill-error').addClass('hidden');
                $.post($this.find('#paymill-url').val(), $.extend($this.collectData(), {
                    token: result.token,
                    _token: $this.find('[name=_token]').val()
                }), function (data) {
                    http.autoHandle(data);
                });
            }

        };

        $this.collectData = function () {
            return {
                checksum: $this.find('#paymill-checksum').val()
            };
        };

        $this.init();

    };

    $.fn.paymentPaymill = function () {

        var $this = $.fn.paymentShared($(this));

        $this.paymentPromo();

        $this.init = function () {
            $this.find('input[type=button][name=paymill]').on('click', $this.validationHandler);
        };

        $this.validationHandler = function () {
            if ($this.find('input[type=button][name=paymill]').attr('disabled') == 'disabled') {
                return false;
            }

            $this.removeErrors();

            $.post($this.find('form').attr('action'), $.extend($this.collectData(), {
                _token: $this.find('[name=_token]').val()
            })).success(function (data) {
                http.autoHandle(data, function (data) {
                    $this.submitHandler();
                });
            }).error(function (data) {
                $this.addErrors(data.responseJSON);
                $this.disableSubmit();
            });
        };

        $this.submitHandler = function () {
            paymill.createToken($this.collectData(), $this.createTokenCallback);
        };

        $this.createTokenCallback = function (error, result) {
            if (error !== null) {
                $this.find('#paymill-error').removeClass('hidden');

            } else {
                $this.find('#paymill-error').addClass('hidden');
                $.post($this.find('#paymill-url').val(), $.extend($this.collectData(), {
                    token: result.token,
                    _token: $this.find('[name=_token]').val()
                }), function (data) {
                    http.autoHandle(data);
                });
            }

        };

        $this.collectData = function () {
            return {
                holder: $this.find('#paymill-card-holder').val(),
                number: $this.find('#paymill-card-number').val(),
                exp_month: $this.find('#paymill-expiration-month').val(),
                exp_year: $this.find('#paymill-expiration-year').val(),
                cvc: $this.find('#paymill-cvc').val(),
                amount_int: $this.find('#paymill-amount').val(),
                currency: $this.find('#paymill-currency').val(),
                cardholder: $this.find('#paymill-cardholder').val()
            };
        };

        $this.init();

    };

})(jQuery, http);