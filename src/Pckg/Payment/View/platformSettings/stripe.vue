<template>
    <div class="pckg-payment-provider-stripe-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.publishable.indexOf('prod') >= 0 ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent>
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.publishable.indexOf('prod') == -1 ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent>
                    Sandbox
                </button>
            </div>
        </form-group>

        <!--<div class="form-group">
            <label>Endpoint</label>
            <div>
                <input type="text" v-model="paymentMethod.endpoint" class="form-control"/>
            </div>
        </div>-->

        <div class="form-group">
            <label>Publishable</label>
            <div>
                <input type="text" v-model="paymentMethod.publishable" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Secret key</label>
            <div>
                <input type="text" v-model="paymentMethod.secret" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Signing key</label>
            <div>
                <input type="text" v-model="paymentMethod.signingSecret" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Stripe Webhook</label>
            <div>
                /payment/stripe/notification
            </div>
            <div class="help">Webhook is automatically added to your Stripe account.</div>
        </div>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>

    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-stripe-platform-config',
        data: function () {
            return Object.assign(pckgPaymentConfig.data.call(this), {})
        },
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    publishable: this.paymentMethod.publishable,
                    secret: this.paymentMethod.secret,
                    signingSecret: this.paymentMethod.signingSecret,
                };
            }
        }
    }
</script>