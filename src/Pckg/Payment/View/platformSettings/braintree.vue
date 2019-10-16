<template>
    <div class="pckg-payment-provider-braintree-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.environment === 'production' ? 'btn-primary' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.environment = 'production'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.environment !== 'production' ? 'btn-secondary' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.environment = 'sandbox'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <!--<div class="form-group">
            <label>Environment</label>
            <div>
                <input type="text" v-model="paymentMethod.environment" class="form-control"/>
            </div>
        </div>-->

        <div class="form-group">
            <label>Merchant</label>
            <div>
                <input type="text" v-model="paymentMethod.merchant" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Public key</label>
            <div>
                <input type="text" v-model="paymentMethod.public" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Private key</label>
            <div>
                <input type="text" v-model="paymentMethod.private" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>CSE</label>
            <div>
                <input type="text" v-model="paymentMethod.cse" class="form-control"/>
            </div>
        </div>

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
        name: 'pckg-payment-provider-braintree-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    environment: this.paymentMethod.environment,
                    merchant: this.paymentMethod.merchant,
                    public: this.paymentMethod.public,
                    private: this.paymentMethod.private,
                    cse: this.paymentMethod.cse,
                };
            }
        }
    }
</script>