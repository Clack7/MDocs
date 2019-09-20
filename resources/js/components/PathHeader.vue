<template>
    <div class="card-header d-flex rounded-0">
        <div class="path-header-input">
            <input @focus="mode == 'show' ? $event.target.select() : null" type="text" v-model="$parent.path_new" @change="pathChange" @input="pathClean" v-shortkey.native.focus="['alt', 'n']" />
            <span v-bind:class="{ visible: $parent.path_new == '' }">{{ $parent.path_new == '' ? 'File path...' : $parent.path_new|spaceNbsp }}</span>
        </div>
        <div class="path-header-extension flex-grow-1">{{ $parent.path_new == '' ? '' : '.md ' }}</div>
        <div v-if="mode == 'show'" class="path-header-actions" :class="{ 'path-header-actions-show' : $parent.createAction != null }">
            <router-link class="btn btn-sm" :class="{ 'btn-success' : !$parent.createAction, 'btn-primary': $parent.createAction }" :to="'/update/' + $parent.path_cur" v-shortkey="['alt', 'e']" @shortkey.native="$parent.edit()">{{ !$parent.createAction ? 'Edit' : 'Create' }}</router-link>
        </div>
        <div v-if="mode == 'editor'" class="path-header-actions" :class="{ 'path-header-actions-show' : $parent.createAction != null }">
            <button v-if="!$parent.createAction" tabindex="-1" class="btn btn-sm btn-outline-danger" @click="$parent.destroyConfirm()">Delete</button>
            &nbsp;
            <button tabindex="-1" class="btn btn-sm btn-outline-info" @click="$parent.editorImagesToggle()" v-shortkey="['alt', 'i']" @shortkey="$parent.editorImagesToggle()">Toggle IMG</button>
            &nbsp;
            <button tabindex="-1" class="btn btn-sm btn-outline-secondary" @click="$parent.cancel()" v-shortkey="['alt', 'c']" @shortkey="$parent.cancel()">{{ $parent.createAction ? 'Cancel' : 'Close' }}</button>
            &nbsp;
            <div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                <button tabindex="-1" class="btn" :class="{ 'btn-success' : !$parent.createAction, 'btn-primary': $parent.createAction }" @click.prevent="$parent.save" v-shortkey="['ctrl', 's']" @shortkey="$parent.save()">{{ $parent.saving ? 'Saving...' : 'Save file' }}</button>
                <button tabindex="-1" class="btn" :class="{ 'btn-success' : !$parent.createAction, 'btn-primary': $parent.createAction }" @click.prevent="$parent.apply" v-shortkey="['alt', 's']" @shortkey="$parent.apply()"><strong>&#x2714;</strong></button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['mode'],
        data() {
            return {};
        },
        mounted() {
            this.pathClean();
            this.pathTitle();
        },
        beforeDestroy() {
            document.title = MDocs.name;
        },
        methods: {
            pathClean() {
                this.$parent.path_new = this.$parent.path_new.replace(new RegExp(MDocs.char_regex, 'g'), '');
            },
            pathChange() {
                this.pathTitle();
                if (this.mode != 'show') {
                    return;
                }
                if (this.$parent.path_new == '') {
                    this.pathTitle();
                    this.$parent.path_new = this.$parent.path_cur;
                    return;
                }
                this.$parent.$router.push('/' + this.$parent.path_new);
            },
            pathTitle() {
                document.title = (this.mode == 'editor' ? '@' : '') + (this.$parent.path_new == '' ? '' : this.$parent.path_new + ' - ') + MDocs.name;
            }
        },
        filters: {
            spaceNbsp(text) {
                return text.replace(/ /g, '\xA0');
            }
        }
    }
</script>
