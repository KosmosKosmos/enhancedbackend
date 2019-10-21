import Vue from 'vue$';
import VueTree from './VueTree';

//import 'sl-vue-tree/dist/sl-vue-tree-dark.css'
import './menueditor.scss';

setTimeout(function() {
    new Vue({
        el: '#vue-app',
        components: {
            'vue-tree': VueTree
        },
        data: {
            loading: true
        },
        methods: {
            onLoaded(loaded) {
                this.loading = !loaded;
            }
        }
    });
}, 2000);
