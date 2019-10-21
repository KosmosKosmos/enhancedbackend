<template>
    <li v-if="result">
        <a class="uk-navbar-toggle" uk-toggle="target: #help-page" href="#"><i uk-icon="question"></i></a>
        <div class="uk-navbar-dropdown uk-width-xlarge " uk-drop="mode: click; offset: 0" id="help-page">
            <div class="uk-width-expand uk-overflow-auto" v-html="result" style="max-height: 70vh"></div>
            <div class="uk-width-auto uk-position-top-right uk-position-small">
                <a class="uk-navbar-dropdown-close" href="#" uk-close></a>
            </div>
        </div>
    </li>
</template>

<script>
    export default {
        props: ['page'],
        data() {
            return {
                result: ''
            };
        },
        mounted() {
            setTimeout(() => {
                $.request('helpWidget::onGetHelpPage', {
                    method: 'POST',
                    data: {page: this.page},
                    success: (data) => {
                        this.result = data.result;
                    },
                });
            });
        },
        methods: {
        }
    }
</script>


