<template>
    <div class="pckg-payment-provider-mollie-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        disabled
                        :class="paymentMethod.apiKey && paymentMethod.apiKey.indexOf('live_') === 0 ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent>
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        disabled
                        :class="paymentMethod.apiKey && paymentMethod.apiKey.indexOf('test_') === 0 ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent>
                    Sandbox
                </button>
            </div>
        </form-group>

        <div class="form-group">
            <label>Api Key</label>
            <div>
                <input type="text" v-model="paymentMethod.apiKey" class="form-control"/>
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
        name: 'pckg-payment-provider-mollie-platform-config',
        data: function () {
            return Object.assign(pckgPaymentConfig.data.call(this), {
                paymentMethodOptions: {
                    options: {
                        'ideal': 'iDEAL',
                        'bancontact': 'Bancontact',
                        'giropay': 'GiroPay',
                        'creditcard': 'Visa & Mastercard',
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
                    apiKey: this.paymentMethod.apiKey,
                    methods: this.paymentMethod.methods,
                };
            }
        }
    }
</script>