<template>
    <div class="pckg-payment-provider-checkout-portal-platform-config">
        <p>{{ myPaymentMethod.description }}</p>

        <div class="form-group">
            <label>Enabled</label>
            <div>
                <d-input-checkbox v-model="myPaymentMethod.enabled"></d-input-checkbox>
            </div>
        </div>

        <template v-if="myPaymentMethod.enabled">
            <div class="form-group">
                <label>MAID - Merchant ID</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.maid" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>HTTP username</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.username" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>HTTP password</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.password" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Secret</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.secret" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>URL / Endpoint</label>
                <div>
                    <input type="text" v-model="myPaymentMethod.endpoint" class="form-control"/>
                </div>
            </div>

            <div class="form-group">
                <label>Mode</label>
                <div>
                    <pckg-select :initial-options="initialOptions.mode" :initial-multiple="false"></pckg-select>
                </div>
            </div>
        </template>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-checkout-portal-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.myPaymentMethod.enabled,
                    maid: this.myPaymentMethod.enabled,
                    username: this.myPaymentMethod.enabled,
                    password: this.myPaymentMethod.enabled,
                    secret: this.myPaymentMethod.enabled,
                    endpoint: this.myPaymentMethod.enabled,
                };
            }
        }
    }
</script>