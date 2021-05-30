<template>
    <div class="pckg-payment-provider-leanpay-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.url === 'https://app.leanpay.si' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.url = 'https://app.leanpay.si'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.url !== 'https://app.leanpay.si' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.url = 'https://lapp.leanpay.si'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <div class="form-group">
            <label>API key</label>
            <div>
                <input type="text" v-model="paymentMethod.apiKey" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>API secret</label>
            <div>
                <input type="text" v-model="paymentMethod.apiSecret" class="form-control"/>
            </div>
        </div>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
export default {
    mixins: [pckgPaymentConfig],
    name: 'pckg-payment-provider-leanpay-platform-config',
    methods: {
        collectSettings: function () {
            return {
                enabled: this.paymentMethod.enabled,
                apiKey: this.paymentMethod.apiKey,
                apiSecret: this.paymentMethod.apiSecret,
                url: this.paymentMethod.url,
            };
        }
    }
}
</script>
