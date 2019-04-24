<template>
    <div>
        <div class="alert alert-danger m-0 rounded-0" v-if="error != ''">{{ error }}</div>
        <div class="card border-0">
            <div class="card-header d-flex">
                <div class="path-header-input">
                    <input @focus="$event.target.select()" type="text" v-model="path_new" @change="pathChange" @input="pathClean" v-shortkey.native.focus="['alt', 'n']" />
                    <span v-bind:class="{ visible: path_new == '' }">{{ path_new == '' ? 'File path...' : path_new|spaceNbsp }}</span>
                </div>
                <div class="path-header-extension flex-grow-1">{{ path_new == '' ? '' : '.md ' }}</div>
                <div><router-link class="btn btn-sm" :class="{ 'btn-success' : !createAction, 'btn-primary': createAction }"  style="margin: -3px" :to="'/update/' + this.path" v-shortkey="['alt', 'e']" @shortkey.native="edit()">{{ !createAction ? 'Edit' : 'Create' }}</router-link></div>
            </div>
            <div ref="previewColumn" :style="{ height: columnHeight + 'px', overflow: 'auto' }">
                <transition name="slide-fade">
                    <div v-html="contentMarked" class="markdown-body" v-if="content != ''"></div>
                    <div v-if="createAction" class="markdown-body"><em>File not found.</em>  &nbsp;<a href="#" @click="edit()">Create?</a></div>
                </transition>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                path_new: this.$route.params.path,
                path: this.$route.params.path,
                content: '',
                contentMarked: 'Loading...',
                createAction: false,
                error: '',
                create: 0,
                columnHeight: 100,
            };
        },
        mounted() {
            axios.get('/api/file/' + this.path)
                .then((response) => {
                    this.content = response.data.content;
                    this.update();
                }).catch((error) => {
                    Vue.handleAxiosError(error);
                    if (error.response.status == 404 && error.response.data.message == 'File not found.') {
                        this.createAction = true;
                    } else {
                        this.error = 'Server error: ' + error.response.data.message;
                    }
                    Vue.handleAxiosError(error);
                });

            window.addEventListener('resize', this.adjustWindowHeight);
            this.adjustWindowHeight();
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.adjustWindowHeight);
        },
        methods: {
            adjustWindowHeight(e) {
                this.columnHeight = document.documentElement.clientHeight - (this.$refs.previewColumn.getBoundingClientRect().top + document.documentElement.scrollTop + 1);
            },
            update() {
                this.contentMarked = this.$options.filters['marked'](this.content);
            },
            pathClean() {
                this.path_new = this.path_new.replace(/[^a-zA-Z0-9-_ \/]/g, '');
            },
            pathChange() {
                if (this.path_new == '') {
                    this.path_new = this.path;
                    return;
                }
                this.$router.push('/' + this.path_new);
            },
            edit() {
                this.$router.push('/update/' + this.path);
            }
        },
        filters: {
            spaceNbsp(text) {
                return text.replace(/ /g, '\xA0');
            }
        }
    }
</script>
