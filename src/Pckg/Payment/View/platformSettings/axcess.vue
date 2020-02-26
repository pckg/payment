<template>
    <div class="pckg-payment-provider-axcess-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.endpoint === 'https://oppwa.com/' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.endpoint = 'https://oppwa.com/'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.endpoint !== 'https://oppwa.com/' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.endpoint = 'https://test.oppwa.com/'">
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
            <label>User ID</label>
            <div>
                <input type="text" v-model="paymentMethod.userId" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div>
                <input type="text" v-model="paymentMethod.password" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Entity ID</label>
            <div>
                <input type="text" v-model="paymentMethod.entityId" class="form-control"/>
            </div>
        </div>

        <div class="form-group">
            <label>Authorization bearer</label>
            <div>
                <input type="text" v-model="paymentMethod.authorizationBearer" class="form-control"/>
            </div>
        </div>

        <form-group label="Payment methods"
                    type="select:multiple"
                    :options="paymentMethodOptions"
                    v-model="myComputedBrands"
                    help="Select payment methods your store accepts"></form-group>

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

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-axcess-platform-config',
        data: function () {
            return Object.assign(pckgPaymentConfig.data.call(this), {
                paymentMethodOptions: {
                    options: {
                        'VISA': 'Visa',
                        'MASTER': 'Mastercard',
                        'AMEX': 'American Express',
                    }
                }
            });
        },
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    endpoint: this.paymentMethod.endpoint,
                    userId: this.paymentMethod.userId,
                    password: this.paymentMethod.password,
                    entityId: this.paymentMethod.entityId,
                    brands: this.paymentMethod.brands,
                    authorizationBearer: this.paymentMethod.authorizationBearer
                };
            }
        },
        computed: {
            myComputedBrands: {
                get: function () {
                    return this.paymentMethod.brands.split(' ');
                },
                set: function (value) {
                    return this.paymentMethod.brands = value.join(' ');
                }
            }
        }
    }
</script>