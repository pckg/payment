<template>
    <div class="pckg-payment-provider-axcess-platform-config">
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
                };
            }
        }
    }
</script>