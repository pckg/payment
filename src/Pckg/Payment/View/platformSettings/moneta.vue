<template>
    <div class="pckg-payment-provider-moneta-platform-config">

        <form-group label="Mode"
                    :help="help.mode">
            <div slot="element">
                <button class="btn"
                        :class="myPaymentMethod.url === 'https://moneta.mobitel.si/placevanje/ssl/TarifficationE.dll' ? 'btn-success' : 'btn-default'"
                        title="Production / live mode"
                        @click.prevent="myPaymentMethod.url = 'https://moneta.mobitel.si/placevanje/ssl/TarifficationE.dll'">
                    Live
                </button>
                <button class="btn"
                        :class="myPaymentMethod.url !== 'https://moneta.mobitel.si/placevanje/ssl/TarifficationE.dll' ? 'btn-info' : 'btn-default'"
                        title="Test / sandbox / dev mode"
                        @click.prevent="myPaymentMethod.url = 'https://test.moneta.mobitel.si/placevanje/ssl/TarifficationE.dll'">
                    Sandbox
                </button>
            </div>
        </form-group>

        <div class="form-group">
            <label>Tarrification ID</label>
            <div>
                <input type="text" v-model="myPaymentMethod.tarrificationId" class="form-control"/>
            </div>
        </div>

        <!--<div class="form-group">
            <label>URL</label>
            <div>
                <input type="text" v-model="myPaymentMethod.url" class="form-control"/>
            </div>
        </div>-->

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
        name: 'pckg-payment-provider-moneta-platform-config',
        methods: {
            collectSettings: function () {
                return {
                    enabled: this.myPaymentMethod.enabled,
                    tarrificationId: this.myPaymentMethod.tarrificationId,
                    url: this.myPaymentMethod.url,
                };
            }
        }
    }
</script>