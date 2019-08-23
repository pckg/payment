<template>
    <div class="pckg-payment-provider-braintree-platform-config">

        <h3 class="__component-title">Mode and visibility</h3>

        <form-group label="Enabled"
                    type="toggle"
                    v-model="myPaymentMethod.enabled"
                    help="When checked payment method will be available for selection in purchase process"></form-group>

        <template v-if="myPaymentMethod.enabled">

            <form-group label="Mode"
                        :help="help.mode">
                <div slot="element">
                    <button class="btn"
                            :class="myPaymentMethod.environment === 'live' ? 'btn-success' : 'btn-default'"
                            title="Production / live mode"
                            @click.prevent="myPaymentMethod.environment = 'live'">
                        Live
                    </button>
                    <button class="btn"
                            :class="myPaymentMethod.environment !== 'live' ? 'btn-info' : 'btn-default'"
                            title="Test / sandbox / dev mode"
                            @click.prevent="myPaymentMethod.environment = 'sandbox'">
                        Sandbox
                    </button>
                </div>
            </form-group>

            <!--<div class="form-group">
                <label>Environment</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.environment" class="form-control"/>
                </div>
            </div>-->

            <h3 class="__component-title">Braintree Configuration</h3>

            <div class="form-group">
                <label>Merchant</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.merchant" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Public key</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.public" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Private key</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.private" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>CSE</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.cse" class="form-control"/>
                </div>
            </div>

            <!--<div class="form-group">
                <label>Title</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.title" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Picture</label>
                <div v-if="!myPaymentMethod.icon">
                    <img src="/img/payment/paypal.png" class="img-responsive">
                    <div class="help">You're currently using defaut Comms payment method icon.</div>
                    <pckg-htmlbuilder-dropzone :current="myPaymentMethod.icon" :url="myPaymentMethod.icon"
                                               id="dynamic-dropzone-paypal"></pckg-htmlbuilder-dropzone>
                </div>
            </div>-->
        </template>

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
                    enabled: this.myPaymentMethod.enabled,
                    environment: this.myPaymentMethod.environment,
                    merchant: this.myPaymentMethod.merchant,
                    public: this.myPaymentMethod.public,
                    private: this.myPaymentMethod.private,
                    cse: this.myPaymentMethod.cse,
                };
            }
        }
    }
</script>