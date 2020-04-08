<template>
    <div class="pckg-payment-provider-bankart-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.url === 'https://gateway.bankart.si/' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.url = 'https://gateway.bankart.si/'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.url !== 'https://gateway.bankart.si/' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.url = 'https://bankart.paymentsandbox.cloud/'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <div class="form-group">
            <label>API username</label>
            <div>
                <input type="text" v-model="paymentMethod.apiUsername" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>API password</label>
            <div>
                <input type="text" v-model="paymentMethod.apiPassword" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>API key</label>
            <div>
                <input type="text" v-model="paymentMethod.apiKey" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Shared secret</label>
            <div>
                <input type="text" v-model="paymentMethod.sharedSecret" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Public integration key</label>
            <div>
                <input type="text" v-model="paymentMethod.publicIntegrationKey" class="form-control"/>
            </div>
        </div>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-bankart-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    apiUsername: this.paymentMethod.apiUsername,
                    apiPassword: this.paymentMethod.apiPassword,
                    apiKey: this.paymentMethod.apiKey,
                    sharedSecret: this.paymentMethod.sharedSecret,
                    publicIntegrationKey: this.paymentMethod.publicIntegrationKey,
                    url: this.paymentMethod.url,
                };
            }
        }
    }
</script>