<template>
    <div class="pckg-payment-provider-valu-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="paymentMethod.url === 'https://placilo.valu.si/te/TarifficationE.dll' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="paymentMethod.url = 'https://placilo.valu.si/te/TarifficationE.dll'">
                    Live
                </button>
                &nbsp;
                <button class="btn"
                        :class="paymentMethod.url !== 'https://placilo.valu.si/te/TarifficationE.dll' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="paymentMethod.url = 'https://test-placilo.valu.si/placevanje/TarifficationE.dll'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <div class="form-group">
            <label>Tariffication ID</label>
            <div>
                <input type="text" v-model="paymentMethod.tarifficationId" class="form-control"/>
            </div>
        </div>

        <button type="button" class="btn btn-primary" @click.prevent="saveSettings">Save settings</button>
    </div>
</template>

<script>
    export default {
        mixins: [pckgPaymentConfig],
        name: 'pckg-payment-provider-valu-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.paymentMethod.enabled,
                    tarifficationId: this.paymentMethod.tarifficationId,
                    url: this.paymentMethod.url,
                };
            }
        }
    }
</script>