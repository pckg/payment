<template>
    <div class="pckg-payment-provider-corvusPay-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.url === 'https://wallet.corvuspay.com/' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.url = 'https://wallet.corvuspay.com/'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.url !== 'https://wallet.corvuspay.com/' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.url = 'https://test-wallet.corvuspay.com/'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <div class="form-group">
            <label>Store ID</label>
            <div>
                <input type="text" v-model="paymentMethod.storeId" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>API key</label>
            <div>
                <input type="text" v-model="paymentMethod.apiKey" class="form-control"/>
            </div>
        </div>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
export default {
    mixins: [pckgPaymentConfig],
    name: 'pckg-payment-provider-corvus-pay-platform-config',
    methods: {
        collectSettings: function () {
            return {
                enabled: this.paymentMethod.enabled,
                storeId: this.paymentMethod.storeId,
                apiKey: this.paymentMethod.apiKey,
                url: this.paymentMethod.url,
            };
        }
    }
}
</script>