<template>
    <div class="pckg-payment-provider-stripe-platform-config">

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