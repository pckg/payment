<template>
    <div class="pckg-payment-provider-icepay-platform-config">

        <div class="form-group">
            <label>Merchant</label>
            <div>
                <input type="text" v-model="paymentMethod.merchant" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Secret</label>
            <div>
                <input type="text" v-model="paymentMethod.secret" class="form-control"/>
            </div>
        </div>

        <form-group label="Payment methods"
                    type="select:multiple"
                    :options="paymentMethodOptions"
                    v-model="paymentMethod.methods"
                    help="Select payment methods your store accepts"></form-group>

        <!--<div class="form-group">
            <label>Title</label>
            <div>
                <input type="text" v-model="paymentMethod.title" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Picture</label>
            <div v-if="!paymentMethod.icon">
                <img src="/img/payment/paypal.png" class="img-responsive">
                <div class="help">You're currently using defaut Comms payment method icon.</div>
                <pckg-htmlbuilder-dropzone :current="paymentMethod.icon" :url="paymentMethod.icon"
                                           id="dynamic-dropzone-paypal"></pckg-htmlbuilder-dropzone>
            </div>
        </div>-->

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-icepay-platform-config',
        data: function () {
            return Object.assign(pckgPaymentConfig.data.call(this), {
                paymentMethodOptions: {
                    options: {
                        'ideal': 'iDEAL',
                        'bancontact': 'Bancontact',
                        'giropay': 'GiroPay',
                        'visa': 'Visa & Mastercard',
                        'eps': 'EPS',
                        'sofort': 'Sofort'
                    }
                }
            });
        },
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    merchant: this.paymentMethod.merchant,
                    secret: this.paymentMethod.secret,
                    methods: this.paymentMethod.methods,
                };
            }
        }
    }
</script>