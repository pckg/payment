<template>
    <div class="pckg-payment-provider-stripe-platform-config">

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
                            :class="myPaymentMethod.publishable.indexOf('prod') >= 0 ? 'btn-success' : 'btn-default'"
                            title="Production / live mode"
                            @click.prevent>
                        Live
                    </button>
                    <button class="btn"
                            :class="myPaymentMethod.publishable.indexOf('prod') == -1 ? 'btn-info' : 'btn-default'"
                            title="Test / sandbox / dev mode"
                            @click.prevent>
                        Sandbox
                    </button>
                </div>
            </form-group>

            <!--<div class="form-group">
                <label>Endpoint</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.endpoint" class="form-control"/>
                </div>
            </div>-->

            <h3 class="__component-title">Stripe Configuration</h3>

            <div class="form-group">
                <label>Publishable</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.publishable" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Secret key</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.secret" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Signing key</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.signingSecret" class="form-control"/>
                </div>
            </div>

        </template>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-stripe-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.myPaymentMethod.enabled,
                    publishable: this.myPaymentMethod.publishable,
                    secret: this.myPaymentMethod.secret,
                    signingSecret: this.myPaymentMethod.signingSecret,
                };
            }
        }
    }
</script>