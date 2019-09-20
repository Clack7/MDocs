<template>
    <div class="card-header d-flex rounded-0">
        <div class="path-header-input">
            <input @focus="mode == 'show' ? $event.target.select() : null" type="text" v-model="$parent.path_new" @change="pathChange" @input="pathClean" v-shortkey.native.focus="['alt', 'n']" />
            <span v-bind:class="{ visible: $parent.path_new == '' }">{{ $parent.path_new == '' ? 'File path...' : $parent.path_new|spaceNbsp }}</span>
        </div>
        <div class="path-header-extension flex-grow-1">{{ $parent.path_new == '' ? '' : '.md ' }}</div>
        <div v-if="mode == 'show'">
            <router-link class="btn btn-sm" :class="{ 'btn-success' : !$parent.createAction, 'btn-primary': $parent.createAction }"  style="margin: -3px" :to="'/update/' + $parent.path_cur" v-shortkey="['alt', 'e']" @shortkey.native="$parent.edit()">{{ !$parent.createAction ? 'Edit' : 'Create' }}</router-link>
        </div>
        <div v-if="mode == 'editor'">
            <button v-if="!$parent.createAction" tabindex="-1" class="btn btn-sm btn-outline-danger" @click="$parent.destroyConfirm()" style="margin: -3px">Delete</button>
            &nbsp;&nbsp;&nbsp;
            <button tabindex="-1" class="btn btn-sm btn-outline-info" @click="$parent.editorImagesToggle()" style="margin: -3px"  v-shortkey="['alt', 'i']" @shortkey="$parent.editorImagesToggle()">Toggle IMG</button>
            &nbsp;&nbsp;&nbsp;
            <button tabindex="-1" class="btn btn-sm btn-outline-secondary" @click="$parent.cancel()" style="margin: -3px"  v-shortkey="['alt', 'c']" @shortkey="$parent.cancel()">Cancel</button>
            &nbsp;&nbsp;&nbsp;
            <button tabindex="-1" class="btn btn-sm" :class="{ 'btn-success' : !$parent.createAction, 'btn-primary': $parent.createAction }" style="margin: -3px" @click.prevent="$parent.save" v-shortkey="['ctrl', 's']" @shortkey="$parent.save()">{{ $parent.saving ? 'Saving...' : 'Save file' }}</button>
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
