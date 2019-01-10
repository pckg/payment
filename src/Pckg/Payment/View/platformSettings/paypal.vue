<template>
    <div class="pckg-payment-provider-paypal-platform-config">
        <p>{{ myPaymentMethod.description }}</p>

        <div class="form-group">
            <label>Enabled</label>
            <div>
                <d-input-checkbox v-model="myPaymentMethod.enabled"></d-input-checkbox>
            </div>
        </div>

        <template v-if="myPaymentMethod.enabled">
            <div class="form-group">
                <label>Endpoint</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.endpoint" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Client</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.client" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Secret</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.secret" class="form-control"/>
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

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settigns</button>
    </div>
</template>

<script>
    export default {
        name: 'pckg-payment-provider-paypal-platform-config',
        props: {
            paymentMethod: {
                type: Object,
                required: true
            },
            company: {
                type: Object,
                required: true
            }
        },
        data: function () {
            return {
                myPaymentMethod: this.paymentMethod,
                myCompany: this.company,
            };
        },
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.myPaymentMethod.enabled,
                    endpoint: this.myPaymentMethod.endpoint,
                    client: this.myPaymentMethod.client,
                    secret: this.myPaymentMethod.secret,
                };
            },
            saveSettings: function () {
                http.post('/api/payment-methods/' + this.myPaymentMethod.key + '/companies/' + this.myCompany.id + '/settings', this.collectSettings(), function () {
                    $dispatcher.$emit('notification:success', 'Settings saved');
                }, function () {
                    $dispatcher.$emit('notification:error', 'Error saving settings');
                });
            },
            initialFetch: function () {
                http.getJSON('/api/payment-methods/' + this.myPaymentMethod.key + '/companies/' + this.myCompany.id + '/settings', function (data) {
                    this.myPaymentMethod = data.paymentMethod;
                }.bind(this));
            }
        },
        watch: {
            paymentMethod: function (paymentMethod) {
                this.myPaymentMethod = paymentMethod;
            },
            company: function (company) {
                this.myCompany = company;
            },
            myCompany: function () {
                this.initialFetch();
            }
        },
        created: function () {
            this.initialFetch();
        }
    }
</script>