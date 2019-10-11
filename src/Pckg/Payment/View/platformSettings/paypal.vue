<template>
    <div class="pckg-payment-provider-paypal-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="myPaymentMethod.endpoint === 'api.paypal.com' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="myPaymentMethod.endpoint = 'api.paypal.com'">
                    Live
                </button>
                <button class="btn"
                        :class="myPaymentMethod.endpoint !== 'api.paypal.com' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="myPaymentMethod.endpoint = 'api.sandbox.paypal.com'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <!--<div class="form-group">
            <label>Endpoint</label>
            <div>
                <input type="text" v-model="myPaymentMethod.endpoint" class="form-control"/>
            </div>
            <htmlbuilder-validator-error :bag="errors" name="endpoint"></htmlbuilder-validator-error>
        </div>-->

        <div class="form-group">
            <label>Client</label>
            <div>
                <input type="text" v-model="myPaymentMethod.client" class="form-control"/>
            </div>
            <htmlbuilder-validator-error :bag="errors" name="client"></htmlbuilder-validator-error>
        </div>

        <div class="form-group">
            <label>Secret</label>
            <div>
                <input type="text" v-model="myPaymentMethod.secret" class="form-control"/>
            </div>
            <htmlbuilder-validator-error :bag="errors" name="secret"></htmlbuilder-validator-error>
        </div>

        <!--<div class="form-group">
            <label>Title</label>
            <div>
                <input type="text" v-model="myPaymentMethod.title" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Picture</label>
            <div v-if="!myPaymentMethod.icon">
                <img src="/img/payment/paypal.png" class="img-responsive">
                <div class="help">You're currently using defaut Comms payment method icon.</div>
                <pckg-htmlbuilder-dropzone :current="myPaymentMethod.icon" :url="myPaymentMethod.icon"
                                           id="dynamic-dropzone-paypal"></pckg-htmlbuilder-dropzone>
            </div>
        </div>-->

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-paypal-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.myPaymentMethod.enabled,
                    endpoint: this.myPaymentMethod.endpoint,
                    client: this.myPaymentMethod.client,
                    secret: this.myPaymentMethod.secret,
                };
            }
        }
    }
</script>