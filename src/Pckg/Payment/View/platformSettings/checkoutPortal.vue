<template>
    <div class="pckg-payment-provider-checkout-portal-platform-config">

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
                            :class="myPaymentMethod.endpoint === 'https://wpp.wirecard.com/api/payment/register' ? 'btn-success' : 'btn-default'"
                            title="Production / live mode"
                            @click.prevent="myPaymentMethod.endpoint = 'https://wpp.wirecard.com/api/payment/register'">
                        Live
                    </button>
                    <button class="btn"
                            :class="myPaymentMethod.endpoint !== 'https://wpp.wirecard.com/api/payment/register' ? 'btn-info' : 'btn-default'"
                            title="Test / sandbox / dev mode"
                            @click.prevent="myPaymentMethod.endpoint = 'https://wpp-test.wirecard.com/api/payment/register'">
                        Sandbox
                    </button>
                </div>
            </form-group>

            <h3 class="__component-title">Checkout Portal Configuration</h3>

            <p>Please provide your <a href="#">Checkout Portal</a> credentials. See <a href="#">Comms Knowledge Base</a>
                for more info about Checkout Portal integration.</p>

            <div class="form-group">
                <label>MAID - Merchant ID</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.maid" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>HTTP username</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.username" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>HTTP password</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.password" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Secret</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.secret" class="form-control"/>
                </div>
            </div>

            <!--<div class="form-group">
                <label>URL / Endpoint</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.endpoint" class="form-control"/>
                </div>
            </div>-->

            <form-group label="Mode"
                        v-model="myPaymentMethod.mode"
                        :options="{options: {embedded: 'Embedded'}}"
                        type="select:single"></form-group>

        </template>

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
                    enabled: this.myPaymentMethod.enabled,
                    maid: this.myPaymentMethod.maid,
                    username: this.myPaymentMethod.username,
                    password: this.myPaymentMethod.password,
                    secret: this.myPaymentMethod.secret,
                    endpoint: this.myPaymentMethod.endpoint,
                    mode: this.myPaymentMethod.mode,
                };
            }
        }
    }
</script>