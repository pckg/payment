<template>
    <div class="pckg-payment-provider-paypal-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.endpoint === 'api.paypal.com' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.endpoint = 'api.paypal.com'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.endpoint !== 'api.paypal.com' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.endpoint = 'api.sandbox.paypal.com'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <!--<div class="form-group">
            <label>Endpoint</label>
            <div>
                <input type="text" v-model="paymentMethod.endpoint" class="form-control"/>
            </div>
            <htmlbuilder-validator-error :bag="errors" name="endpoint"></htmlbuilder-validator-error>
        </div>-->

        <div class="form-group">
            <label>Client</label>
            <div>
                <input type="text" v-model="paymentMethod.client" class="form-control"/>
            </div>
            <htmlbuilder-validator-error :bag="errors" name="client"></htmlbuilder-validator-error>
        </div>

        <div class="form-group">
            <label>Secret</label>
            <div>
                <input type="text" v-model="paymentMethod.secret" class="form-control"/>
            </div>
            <htmlbuilder-validator-error :bag="errors" name="secret"></htmlbuilder-validator-error>
        </div>

        <!--<div class="form-group">
            <label>Title</label>
            <div>
                <input type="text" v-model="paymentMethod.title" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Picture</label>
            <div v-if="!paymentMethod.icon">
                <img src="/img/payment/paypal.png" class="img-responsive">
                <div class="help">You're currently using defaut Comms payment method icon.</div>
                <pckg-htmlbuilder-dropzone :current="paymentMethod.icon" :url="paymentMethod.icon"
                                           id="dynamic-dropzone-paypal"></pckg-htmlbuilder-dropzone>
            </div>
        </div>-->

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings
        </button>
    </div>
</template>

<script>
//    import {PckgPaymentProviderFactory} from "../../../../../../../../app/derive/src/Derive/Platform/public/js/classes";

    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-paypal-platform-config',
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
                    client: this.paymentMethod.client,
                    secret: this.paymentMethod.secret,
                };
            }
        }
    }
</script>