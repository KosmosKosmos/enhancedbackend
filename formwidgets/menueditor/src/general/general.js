import Vue from 'vue$';
import VueSearch from './VueSearch';
import VueHelp from './VueHelp';

setTimeout(function() {
    new Vue({
        el: '#vue-search-help',
        components: {
            'vue-search': VueSearch,
            'vue-help': VueHelp
        }
    });
}, 2000);

