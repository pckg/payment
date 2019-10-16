<template>
    <div class="pckg-payment-provider-checkout-portal-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.endpoint === 'https://wpp.wirecard.com/api/payment/register' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.endpoint = 'https://wpp.wirecard.com/api/payment/register'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.endpoint !== 'https://wpp.wirecard.com/api/payment/register' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.endpoint = 'https://wpp-test.wirecard.com/api/payment/register'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <p>Please provide your <a href="#">Checkout Portal</a> credentials. See <a href="#">Comms Knowledge Base</a>
            for more info about Checkout Portal integration.</p>

        <div class="form-group">
            <label>MAID - Merchant ID</label>
            <div>
                <input type="text" v-model="paymentMethod.maid" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>HTTP username</label>
            <div>
                <input type="text" v-model="paymentMethod.username" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>HTTP password</label>
            <div>
                <input type="text" v-model="paymentMethod.password" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Secret</label>
            <div>
                <input type="text" v-model="paymentMethod.secret" class="form-control"/>
            </div>
        </div>

        <!--<div class="form-group">
            <label>URL / Endpoint</label>
            <div>
                <input type="text" v-model="paymentMethod.endpoint" class="form-control"/>
            </div>
        </div>-->

        <form-group label="Mode"
                    v-model="paymentMethod.mode"
                    :options="{options: {embedded: 'Embedded'}}"
                    type="select:single"></form-group>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-checkout-portal-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    maid: this.paymentMethod.maid,
                    username: this.paymentMethod.username,
                    password: this.paymentMethod.password,
                    secret: this.paymentMethod.secret,
                    endpoint: this.paymentMethod.endpoint,
                    mode: this.paymentMethod.mode,
                };
            }
        }
    }
</script>