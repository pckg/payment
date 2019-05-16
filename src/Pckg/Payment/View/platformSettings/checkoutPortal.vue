<template>
    <div class="pckg-payment-provider-checkout-portal-platform-config">
        <p>{{ myPaymentMethod.description }}</p>

        <div class="form-group">
            <label>Enabled</label>
            <div>
                <d-input-checkbox v-model="myPaymentMethod.enabled"></d-input-checkbox>
            </div>
        </div>

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
                    enabled: this.myPaymentMethod.enabled
                };
            }
        }
    }
</script>