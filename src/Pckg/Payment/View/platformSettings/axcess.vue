<template>
    <div class="pckg-payment-provider-axcess-platform-config">

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

        <form-group label="Payment methods"
                    type="select:multiple"
                    :options="paymentMethodOptions"
                    v-model="myComputedBrands"
                    help="Select payment methods your store accepts"></form-group>

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
                    enabled: this.myPaymentMethod.enabled,
                    endpoint: this.myPaymentMethod.endpoint,
                    userId: this.myPaymentMethod.userId,
                    password: this.myPaymentMethod.password,
                    entityId: this.myPaymentMethod.entityId,
                    brands: this.myPaymentMethod.brands
                };
            }
        },
        computed: {
            myComputedBrands: {
                get: function () {
                    return this.myPaymentMethod.brands.split(' ');
                },
                set: function (value) {
                    return this.myPaymentMethod.brands = value.join(' ');
                }
            }
        }
    }
</script>