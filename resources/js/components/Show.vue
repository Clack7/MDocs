<template>
    <div>
        <div class="alert alert-danger m-0 rounded-0" v-if="error != ''">{{ error }}</div>
        <div class="card border-0">
            <path-header-component mode="show"></path-header-component>
            <div class="toc-open" v-if="tocItems.length > 0"><div><div>
                <h5>Table of contents</h5>
                <ul>
                    <li v-for="item in tocItems">
                        <a :style="{ 'margin-left': (item.level * 12) + 'px' }" href="#" @click.prevent="scrollMeTo(item.to)"><span>&bullet;</span> {{ item.name }}</a>
                    </li>
                </ul>
            </div></div></div>
            <div ref="previewColumn" :style="{ height: columnHeight + 'px', overflow: 'auto' }">
                <transition name="slide-fade">
                    <div v-html="contentMarked" class="markdown-body" v-if="content != ''"></div>
                    <div v-if="createAction" class="markdown-body"><em>File not found.</em>  &nbsp;<a href="#" @click="edit()">Create?</a></div>
                </transition>
            </div>
        </div>
        <gallery-component refContainer="previewColumn"></gallery-component>
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
                inputProgress: false,
                tocItems: [],
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

            var that = this, $previewColumn = $(this.$refs.previewColumn);
            $previewColumn.on('dblclick', '.task-list-item', function(e) {
                if (that.inputProgress) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                var input = $(this).find('input:first');
                $previewColumn.find('.task-list-item > input').each(function(idx) {
                    if (this === input[0]) {
                        input.addClass('task-list-progress');
                        that.inputProgress = true;
                        axios.post('/api/file/toggle', { path: that.path_cur, index: idx })
                            .then(({ data }) => {
                                input.removeClass('task-list-progress');
                                that.inputProgress = false;
                                that.content = data.content;
                                that.update();
                            }).catch((error) => {
                                input.removeClass('task-list-progress');
                                that.inputProgress = false;
                                Vue.handleAxiosError(error);
                            });
                    }
                });
            });
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

                var that = this;
                setTimeout(function() {
                    var headers = $(that.$refs.previewColumn).find('h1, h2, h3, h4, h5, h6');
                    that.tocItems = [];
                    headers.each(function() {
                        that.tocItems.push({
                            to: '#' + $(this).attr('id'),
                            level: this.tagName.charAt(1) - 1,
                            name: $(this).text()
                        });
                    })
                }, 200);
            },
            scrollMeTo(to) {
                $(this.$refs.previewColumn).scrollTop($(this.$refs.previewColumn).scrollTop() + $(to).offset().top - 110);
            },
            edit() {
                this.$router.push('/update/' + this.path_cur);
            }
        }
    }
</script>
