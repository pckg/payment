<template>
    <div class="pckg-payment-provider-paypal-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.endpoint === 'https://merchant.revolut.com/api/1.0' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.endpoint = 'https://merchant.revolut.com/api/1.0'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.endpoint !== 'https://merchant.revolut.com/api/1.0' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.endpoint = 'https://sandbox-merchant.revolut.com/api/1.0'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <div class="form-group">
            <label>Access token / API key</label>
            <div>
                <input type="text" v-model="paymentMethod.accessToken" class="form-control"/>
            </div>
            <htmlbuilder-validator-error :bag="errors" name="accessToken"></htmlbuilder-validator-error>
        </div>

        <div class="form-group">
            <label>Account ID</label>
            <div>
                <input type="text" v-model="paymentMethod.accountId" class="form-control"/>
            </div>
            <htmlbuilder-validator-error :bag="errors" name="accountId"></htmlbuilder-validator-error>
        </div>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings
        </button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-revolut-platform-config',
        computed: {
            isValid: function () {
                return true;
                //return (new PckgPaymentProviderFactory()).createByKey(this.paymentMethod.key).isValid();
            }
        },
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    endpoint: this.paymentMethod.endpoint,
                    accountId: this.paymentMethod.accountId,
                    accessToken: this.paymentMethod.accessToken,
                };
            }
        }
    }
</script>