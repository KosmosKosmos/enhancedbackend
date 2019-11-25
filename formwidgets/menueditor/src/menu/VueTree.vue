<template>
    <div>
        <ul class="uk-iconnav">
            <li><a href="#" @click="addFolder()" uk-icon="icon: plus"></a></li>
        </ul>
        <sl-vue-tree
                ref="slVueTree"
                :allow-multiselect="false"
                @drop="nodeDropped"
                @toggle="nodeToggled"
                v-model="nodes">
            <template slot="title" slot-scope="{ node }">{{getTitle(node)}}</template>
            <template slot="toggle" slot-scope="{ node }">
              <span v-if="!node.isLeaf">
                <i v-if="node.isExpanded" uk-icon="album"></i>
                <i v-if="!node.isExpanded" uk-icon="folder"></i>
              </span>
            </template>
            <template slot="sidebar" slot-scope="{ node }">
                <ul class="uk-iconnav">
                    <li><a href="#" @click="editNode(node)" uk-icon="icon: file-edit"></a></li>
                    <li v-if="isDeletable(node)"><a href="#" @click="removeNode(node)" uk-icon="icon: trash"></a></li>
                    <li>
                        <span class="visible-icon" @click="event => toggleVisibility(event, node)">
                            <i v-if="!node.data || node.data.visible !== false" uk-icon="laptop"></i>
                            <i v-if="node.data && node.data.visible === false" uk-icon="ban"></i>
                        </span>
                    </li>
                </ul>
            </template>
        </sl-vue-tree>

        <div id="modal-close-outside" class="uk-flex-top" uk-modal ref="myid">
            <div class="uk-modal-dialog">
                <button class="uk-modal-close-default" type="button" uk-close></button>
                <div class="uk-modal-header">
                    <h2 class="uk-modal-title">{{getTitle(selectedNode)}}</h2>
                </div>
                <div class="uk-modal-body">
                    <ul data-uk-tab="{connect:'#my-id'}">
                        <li class="uk-active">
                            <a href="">Allgemein</a>
                        </li>
                        <li><a href="">Hilfe</a></li>
                    </ul>
                    <ul id="my-id" class="uk-switcher uk-margin">
                        <li>
                            <div class="uk-margin" v-for="field in ['titles', 'description', 'tagList']">
                                <label class="uk-form-label" for="'form-'.field">{{labels[field]}}</label>
                                <div class="uk-form-controls">
                                    <div class="uk-inline" :class="{'uk-width-1-1': field !== 'titles'}">
                                        <a class="uk-form-icon uk-form-icon-flip uk-text-uppercase" href="">
                                            {{translations[field].current}}
                                        </a>
                                        <div uk-dropdown="mode: click">
                                            <ul class="uk-list">
                                                <li v-for="locale, code in locales"
                                                    class="uk-text-uppercase"
                                                    @click="setLocale(field, code)">
                                                    {{code}}
                                                </li>
                                            </ul>
                                        </div>
                                        <input class="uk-input"
                                               :id="'form-'.field"
                                               type="text"
                                               v-model="translations[field].value[translations[field].current]"
                                               :placeholder="labels[field]">
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="uk-margin">
                                <label class="uk-form-label uk-width-1-1">
                                    {{labels.help}}
                                    <div class="uk-inline uk-float-right">
                                <span class="uk-text-uppercase" href="">
                                    {{translations.help.current}}
                                </span>
                                        <div uk-dropdown="mode: click">
                                            <ul class="uk-list">
                                                <li v-for="locale, code in locales"
                                                    class="uk-text-uppercase"
                                                    @click="setLocale('help', code)">
                                                    {{code}}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </label>
                                <div class="uk-form-controls uk-width-1-1">
                                    <div data-use-media-manager="true"
                                         data-editor-lang="de"
                                         data-links-handler="formMenuItems::onLoadPageLinksForm" data-ace-vendor-path="/modules/backend/formwidgets/codeeditor/assets/vendor/ace"
                                         data-control="richeditor"
                                         class="field-richeditor size-large ">
                                        <textarea :placeholder="labels.help"></textarea>
                                        <div class="height-indicator"></div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="uk-modal-footer uk-text-right">
                    <button class="uk-button uk-button-default uk-modal-close" type="button">
                        {{labels.cancel}}
                    </button>
                    <button class="uk-button uk-button-primary" type="button" @click="closeModal()">
                        {{labels.save}}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import SlVueTree from 'sl-vue-tree';
    export default {
        components: {
            SlVueTree
        },
        props: ['structure', 'locales', 'currentlang', 'loading'],
        data() {
            return {
                labels: {
                    titles: 'Titel',
                    tagList: 'Schlagworte',
                    description: 'Erläuterung',
                    help: 'Hilfe',
                    cancel: 'Abbrechen',
                    save: 'Speichern'
                },
                currentNode: {},
                currentOriginalNode: {},
                contextMenuIsVisible: false,
                nodes: [],
                selectedNode: {title: ''},
                translations: {
                    titles: {value: {}, current: ''},
                    tagList: {value: {}, current: ''},
                    description: {value: {}, current: ''},
                    help: {value: {}, current: ''},
                },
                newTranslations: {}
            };
        },
        mounted() {
            for (const type in this.translations) {
                this.translations[type].current = this.currentlang;
            }
            this.richEditor = $('#modal-close-outside [data-control=richeditor]');
            this.nodes = this.structure;
            window.slVueTree = this.$refs.slVueTree;
/*
            slVueTree.$on('input', () => {
                console.log('jetze');
            });
*/
            this.$emit('loaded', true);
        },
        methods: {
            getTitle(node) {
                return node.data && node.data.directory ? node.data.titles.de : node.title;
            },
            isDeletable(node) {
                return node.data && node.data.directory && !node.children.length;
            },
            editNode(node) {
                this.showModal(node);
                this.currentNode = node.path.join(',');
                this.currentOriginalNode = node;
            },
            removeNode(node) {
                UIkit.modal.confirm(this.getTitle(node) + ' wirklich löschen?').then(() => {
                    this.$refs.slVueTree.remove([node.path]);
                    this.save();
                });
            },
            nodeDropped(nodes, position, event) {
                this.save();
            },
            toggleVisibility: function (event, node) {
                const slVueTree = this.$refs.slVueTree;
                event.stopPropagation();
                const visible = !node.data || node.data.visible !== false;
                slVueTree.updateNode(node.path, {data: { visible: !visible}});
                this.currentNode = null;
            },
            nodeToggled(node, event) {
                this.currentNode = null;
            },
            addFolder() {
                const slVueTree = this.$refs.slVueTree;
                const title = 'item' + slVueTree.getRoot().currentValue.length;
                let node = {
                    title: title,
                    isLeaf: false,
                    children: [],
                    key: title,
                    path: [slVueTree.getRoot().currentValue.length],
                    data: {
                        directory: true,
                        titles: {de: 'Neuer Eintrag', en: 'New item'}
                    }
                };
                this.nodes.push(node);
                slVueTree.emitInput(this.nodes);
                this.showModal(node);
            },
            setTranslations(node) {
                for (const type in this.translations) {
                    if (node.data[type]) {
                        this.translations[type].value = node.data[type];
                    } else {
                        this.translations[type].value = [];
                    }
                }
            },
            setLocale(type, code) {
                if (type === 'help') {
                    this.translations.help.value[this.translations[type].current] = $('.field-richeditor textarea').val();
                    setTimeout(() => {
                        this.translations[type].current = code;
                        this.richEditor.richEditor('setContent', this.translations.help.value[code]);
                    });
                } else {
                    this.translations[type].current = code;
                }
            },
            showModal(node) {
                this.selectedNode = node;
                this.richEditor.richEditor('setContent', '');
                UIkit.modal('#modal-close-outside').show();
                setTimeout(() => {
                    this.setTranslations(node);
                    this.richEditor.richEditor('setContent', this.translations.help.value[this.translations.help.current]);
                }, 500);
            },
            closeModal() {
                const data = this.selectedNode.data ? this.selectedNode.data : {};
                for (const type in this.translations) {
                    if (type === 'help') {
                        this.translations.help.value[this.translations[type].current] = $('.field-richeditor textarea').val();
                    }
                    if (this.translations[type].value) {
                        data[type] = Object.assign({}, this.translations[type].value);
                        this.translations[type].value = [];
                    }
                }
                setTimeout(() => {
                    slVueTree.updateNode(this.selectedNode.path, {data: Object.assign({}, data)});
                    this.save();
//                    this.updateNode(this.selectedNode.path, this.nodes, data);
                    this.currentNode = null;
                });
                UIkit.modal('#modal-close-outside').hide();
            },
/*            updateNode(path, nodes, data) {
                if (path.length > 1) {
                    const item = path.shift();
                    const childNodes = nodes.children ? nodes.children[item] : nodes[item];
                    this.updateNode(path, childNodes, data);
                } else {
                    const updatedNode = nodes.children[path[0]];
                    $.request('onUpdateNode', {
                        data: {node: updatedNode},
                        success: function (data) {
                            console.log(data);
                        }
                    });
                }
            },*/
            save() {
                $('#menueditor-value').val(JSON.stringify(this.nodes));
            }
        }
    }
</script>
<style>
    .field-multilingual.field-multilingual-text .ml-btn {
        top: 2px;
        height: auto;
        margin-top: inherit;
    }
</style>

