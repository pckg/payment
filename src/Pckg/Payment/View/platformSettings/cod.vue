<template>
    <div class="pckg-payment-provider-cod-platform-config">

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

        <form-group label="Order confirmation"
                    type="select:single"
                    :options="{options:{manual: 'Manual, no stock management', automatic: 'Automatic, with stock management'}}"
                    v-model="paymentMethod.confirmation"></form-group>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
export default {
    mixins: [pckgPaymentConfig],
    name: 'pckg-payment-provider-cod-platform-config',
    methods: {
        collectSettings: function () {
            return {
                enabled: this.paymentMethod.enabled,
                confirmation: this.paymentMethod.confirmation,
            };
        }
    }
}
</script>