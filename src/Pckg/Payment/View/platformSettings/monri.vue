<template>
    <div class="pckg-payment-provider-monri-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.url === 'https://ipg.monri.com' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.url = 'https://ipg.monri.com'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.url !== 'https://ipg.monri.com' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.url = 'https://ipgtest.monri.com'">
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
            <label>Authenticity token</label>
            <div>
                <input type="text" v-model="paymentMethod.authenticityToken" class="form-control"/>
            </div>
        </div>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-monri-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    authenticityToken: this.paymentMethod.authenticityToken,
                    apiKey: this.paymentMethod.apiKey,
                    url: this.paymentMethod.url,
                };
            }
        }
    }
</script>