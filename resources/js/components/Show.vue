<template>
    <div>
        <div class="d-flex">
            <div class="show-content" ref="showContent">
                <div class="error-alert alert alert-danger m-0 rounded-0" v-if="error != ''" @click.prevent="error = ''">{{ error }}</div>
                <div class="card border-0">
                    <path-header-component mode="show"></path-header-component>
                    <div class="toc-open" v-if="tocItems.length > 1 && !tocHide" :class="{ 'active': tocShow || tocExpand }" v-on:mouseenter="tocShow = true" v-on:mouseleave="tocShow = false"><div :class="{ 'active': tocExpand }" v-on:mouseenter="tocToggle(true)" v-on:mouseleave="tocToggle(false)"><span></span><div><div>
                        <!-- <h5>Table of contents</h5> -->
                        <ul>
                            <li v-for="item in tocItems">
                                <a :style="{ 'font-size': (16 - item.level * 0.5) + 'px'  }" :href="item.to" @click.prevent="scrollMeTo(item.to)" v-html="('<span class=\'toc-level-marker\'>&middot;</span>').repeat(item.level) + item.name"></a>
                            </li>
                        </ul>
                    </div></div></div></div>
                    <div ref="previewColumn" :style="{ height: columnHeight + 'px', overflow: 'auto' }" @scroll="previewScroll">
                        <transition name="slide-fade">
                            <div v-html="contentMarked" class="markdown-body" v-if="content != ''"></div>
                            <div v-if="createAction == true" class="markdown-body"><em>File not found.</em>  &nbsp;<a href="#" @click.prevent="edit()">Create?</a></div>
                        </transition>
                    </div>
                </div>
            </div>
            <div class="show-minimap">
                <div class="show-minimap-content" :style="{ height: minimapHeight + 'px', width: minimapWidth + 'px' }" :class="{ 'active' : content != '' && minimapHeight > 20}">
                    <div ref="minimapContent" v-html="contentMarked" class="markdown-body"></div>
                    <div class="show-minimap-scroller" @mousedown="minimapScroll($event.x, $event.y)" @mousemove="$event.buttons == 1 && minimapScroll($event.x, $event.y)">
                        <div class="show-minimap-scroller-top" :style="{ height: minimapScrollerTop + 'px' }"></div>
                        <div class="show-minimap-scroller-bottom" :style="{ height: minimapScrollerBottom + 'px' }"></div>
                    </div>
                </div>
            </div>
        </div>
        <gallery-component refContainer="showContent"></gallery-component>
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
                contentImages: [],
                createAction: null,
                error: '',
                create: 0,
                columnHeight: 100,
                minimapHeight: 1,
                minimapWidth: 1,
                minimapScrollerTop: 0,
                minimapScrollerBottom: 0,
                inputProgress: false,
                tocItems: [],
                tocExpand: false,
                tocExpandTO: null,
                tocShow: false,
                tocHide: false,
            };
        },
        mounted() {
            if (this.$route.params.path == '') {
                this.$route.params.path =
                this.path_new =
                this.path_cur = 'index';
            }
            // Remove the md extension from the url
            if (this.path_cur.match(/\.md$/i) != null) {
                this.$router.replace('/' + this.path_cur.slice(0, -3));
                return;
            }

            axios.get('/api/file/' + this.path_cur)
                .then((response) => {
                    this.createAction = false;
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
            $previewColumn.on('click', 'a', function(e) {
                let href = $(this).attr('href');
                // Matching permalink
                if (href.match(/^(#.*)$/) !== null) {
                    e.preventDefault();
                    that.scrollMeTo(href);

                // Matching md url
                } else if (href.match(/^\.\/(.*)\.md$/) !== null) {
                    e.preventDefault();
                    that.$router.push($(this).attr('href'));
                }
            });
        },
        beforeDestroy() {
            window.removeEventListener('resize', this.adjustWindowHeight);
        },
        methods: {
            adjustWindowHeight(e) {
                this.columnHeight = document.documentElement.clientHeight - (this.$refs.previewColumn.getBoundingClientRect().top + document.documentElement.scrollTop + 1);
                this.adjustMinimapSize();
            },
            adjustMinimapSize() {
                this.minimapHeight = $(this.$refs.minimapContent).outerHeight() * 0.1;
                this.minimapWidth = $(this.$refs.minimapContent).outerWidth() * 0.1;
                this.previewScroll();
            },
            previewScroll() {
                if (typeof this.$refs.previewColumn == 'undefined') {
                    return;
                }
                this.minimapScrollerTop = this.$refs.previewColumn.scrollTop * 0.1;
                this.minimapScrollerBottom = Math.max(0, (this.$refs.previewColumn.scrollHeight - this.$refs.previewColumn.scrollTop - this.columnHeight) * 0.1);
            },
            update() {
                this.contentImages = [];
                this.contentMarked = this.$options.filters['marked'](this.content);

                var that = this;
                setTimeout(function() {
                    var $previewColumn = $(that.$refs.previewColumn);
                    var headers = $previewColumn.find('h1, h2, h3, h4, h5, h6');
                    that.tocItems = [];
                    var minLevel = 10, curLevel;
                    var items = [];
                    headers.each(function() {
                        curLevel = parseInt(this.tagName.charAt(1));
                        minLevel = Math.min(curLevel, minLevel);
                        items.push({
                            to: '#' + $(this).attr('id'),
                            level: curLevel,
                            name: $(this).text()
                        });
                    });
                    for (var i = 0; i < items.length; i++) {
                        items[i].level -= minLevel;
                    }
                    that.tocItems = items;

                    that.contentImages = $previewColumn.find('img');
                    that.contentImages.on('load', function() {
                        that.adjustMinimapSize();
                    });
                    that.contentImagesCheck();

                    that.adjustMinimapSize();

                    // Go to permalink
                    if (window.location.hash != '') {
                        for (i in that.tocItems) {
                            if (window.location.hash == that.tocItems[i].to) {
                                that.scrollMeTo(that.tocItems[i].to);
                            }
                        }
                    }
                }, 200);
            },
            updateContentImages() {
            },
            contentImagesCheck() {
                for (var i = this.contentImages.length - 1; i >= 0; i--) {
                    var img = this.contentImages[i];
                    img.dataset.fixed = typeof img.dataset.fixed == 'undefined' ? 0 : img.dataset.fixed;
                    if (img.complete) {
                        if (img.naturalHeight === 0 && img.dataset.fixed < 2) {
                            var srcCur = img.attributes.src.nodeValue;
                            var srcNew = srcCur.split('?')[0] + '?' + (new Date().getTime());
                            img.src = srcNew;
                            $(this.$refs.minimapContent).find('img[src="' + srcCur + '"]')[0].src = srcNew;
                            img.dataset.fixed++;
                        } else {
                            this.contentImages.splice(i, 1);
                        }
                    }
                }
                if (this.contentImages.length > 0) {
                    setTimeout(this.contentImagesCheck, 500);
                }
            },
            minimapScroll(x, y) {
                var mm = $(this.$refs.minimapContent);
                $(this.$refs.previewColumn).scrollTop((y - mm.offset().top) * 10 - this.columnHeight / 2);
            },
            scrollMeTo(to) {
                var that = this;
                this.tocHide = true;
                this.tocShow = false;
                this.tocExpand = false;
                setTimeout(function() {
                    that.tocHide = false;
                }, 500);
                let prevcol = $(this.$refs.previewColumn);
                prevcol.scrollTop(prevcol.scrollTop() + $(to).offset().top - 110);

                // Highlight link text
                prevcol.find('.text-warning').removeClass('text-warning');
                $(to).addClass('text-warning');
            },
            edit() {
                this.$router.push('/update/' + this.path_cur);
            },
            tocToggle(positive) {
                clearTimeout(this.tocExpandTO);
                if (positive) {
                    this.tocExpand = true;
                } else {
                    var that = this;
                    this.tocExpandTO = setTimeout(function() {
                        that.tocExpand = false;
                    }, 250);
                }
            }
        }
    }
</script>
