<template>
    <div class="pckg-payment-provider-mollie-platform-config">

        <h3 class="__component-title">Mode and visibility</h3>

        <form-group label="Enabled"
                    type="toggle"
                    v-model="myPaymentMethod.enabled"
                    help="When checked payment method will be available for selection in purchase process"></form-group>

        <template v-if="myPaymentMethod.enabled">

            <h3 class="__component-title">Mollie Configuration</h3>

            <div class="form-group">
                <label>Api Key</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.apiKey" class="form-control"/>
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
        name: 'pckg-payment-provider-mollie-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.myPaymentMethod.enabled,
                    apiKey: this.myPaymentMethod.apiKey,
                };
            }
        }
    }
</script>