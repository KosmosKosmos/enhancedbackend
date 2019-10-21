<template>
    <li>
        <a class="uk-navbar-toggle" uk-toggle="target: #search-page" href="#" uk-search-icon></a>
        <div>
            <div class="uk-navbar-dropdown uk-width-large" uk-drop="mode: click; offset: 0" id="search-page">
                <div class="uk-grid-small uk-flex-middle" uk-grid>
                    <div class="uk-width-expand">
                        <form class="uk-search uk-search-navbar uk-width-1-1">
                            <input @input="debounceInput" class="uk-search-input" type="search" placeholder="Seite finden">
                        </form>
                    </div>
                    <div class="uk-width-auto">
                        <a class="uk-navbar-dropdown-close" href="#" uk-close></a>
                    </div>
                </div>
                <div class="result uk-margin">
                    <dl class="uk-description-list">
                        <template v-for="item in result">
                            <dt><a :href="item.url">{{item.label}}</a></dt>
                            <dd v-html="item.description"></dd>
                        </template>
                    </dl>
                </div>


            </div>
        </div>
    </li>
</template>

<script>
    import { debounce } from "debounce";
    export default {
        props: [],
        data() {
            return {
                result: []
            };
        },
        mounted() {
        },
        methods: {
            debounceInput: debounce(function (e) {
                $.request('helpWidget::onSearchPage', {
                    method: 'POST',
                    data: {search: e.target.value},
                    success: (data) => {
                        this.result = JSON.parse(data.result);
                    }
                });
            }, 500)
        }
    }
</script>


