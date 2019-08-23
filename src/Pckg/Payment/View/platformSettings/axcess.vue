<template>
    <div class="pckg-payment-provider-axcess-platform-config">

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
                            :class="myPaymentMethod.endpoint === 'https://oppwa.com/' ? 'btn-success' : 'btn-default'"
                            title="Production / live mode"
                            @click.prevent="myPaymentMethod.endpoint = 'https://oppwa.com/'">
                        Live
                    </button>
                    <button class="btn"
                            :class="myPaymentMethod.endpoint !== 'https://oppwa.com/' ? 'btn-info' : 'btn-default'"
                            title="Test / sandbox / dev mode"
                            @click.prevent="myPaymentMethod.endpoint = 'https://test.oppwa.com/'">
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

            <h3 class="__component-title">Axcess Configuration</h3>

            <div class="form-group">
                <label>User ID</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.userId" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.password" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Entity ID</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.entityId" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Brands</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.brands" class="form-control"/>
                </div>
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
        </template>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-axcess-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.myPaymentMethod.enabled,
                    endpoint: this.myPaymentMethod.endpoint,
                    userId: this.myPaymentMethod.userId,
                    password: this.myPaymentMethod.password,
                    entityId: this.myPaymentMethod.entityId,
                    brands: this.myPaymentMethod.brands
                };
            }
        }
    }
</script>