<template>
    <div class="pckg-payment-provider-vivawallet-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.url === 'https://www.vivapayments.com/' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.url = 'https://www.vivapayments.com/'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.url !== 'https://www.vivapayments.com/' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.url = 'https://demo.vivapayments.com/'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <div class="form-group">
            <label>Merchant ID</label>
            <div>
                <input type="text" v-model="paymentMethod.merchantId" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>API key</label>
            <div>
                <input type="text" v-model="paymentMethod.apiKey" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>API code</label>
            <div>
                <input type="text" v-model="paymentMethod.apiCode" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Client ID</label>
            <div>
                <input type="text" v-model="paymentMethod.clientId" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Client secret</label>
            <div>
                <input type="text" v-model="paymentMethod.clientSecret" class="form-control"/>
            </div>
        </div>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-vivawallet-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    url: this.paymentMethod.url,
                    merchantId: this.paymentMethod.merchantId,
                    apiKey: this.paymentMethod.apiKey,
                    apiCode: this.paymentMethod.apiCode,
                    clientId: this.paymentMethod.clientId,
                    clientSecret: this.paymentMethod.clientSecret,
                };
            }
        }
    }
</script>