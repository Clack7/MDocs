<template>
    <div>
        <div class="alert alert-danger m-0 rounded-0" v-if="error != ''">{{ error }}</div>
        <div class="card border-0">
            <path-header-component mode="show"></path-header-component>
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
                path_cur: this.$route.params.path,
                content: '',
                contentMarked: 'Loading...',
                createAction: false,
                error: '',
                create: 0,
                columnHeight: 100,
            };
        },
        mounted() {
            axios.get('/api/file/' + this.path_cur)
                .then((response) => {
                    this.content = response.data.content;
                    this.update();
                }).catch((error) => {
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
            edit() {
                this.$router.push('/update/' + this.path_cur);
            }
        }
    }
</script>
