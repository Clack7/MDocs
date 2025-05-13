<template>
    <nav class="col-md-3 col-lg-2 d-none d-md-block sidebar">
        <div class="sidebar-sticky" ref="sidebarContainer">
            <ul class="nav flex-column">
                <li v-for="file in levels" class="nav-item" @click="toggleCollapse(file)" v-if="file.show">
                    <span class="nav-link sidebar-parent" v-if="!file.file" v-html="levelName(file)" :class="{ 'child-active': routePath.indexOf(file.path) == 0 && file.path != routePath, 'parent-collapsed': collapsed.indexOf(file.path) >= 0 }"></span>
                    <router-link class="nav-link" v-if="file.file" v-html="levelName(file)" :to="'/' + file.path" :class="{ active: file.path == routePath, 'child-active': routePath.indexOf(file.path) == 0 && file.path != routePath }"></router-link>
                </li>
               <!--  <li v-for="file in files" class="nav-item">
                    <router-link class="nav-link" :to="'/' + file.path" :class="{ active: file.path == routePath }" v-html="formatPath(file.path)" :title="file.path.split('/').pop()"></router-link>
                </li> -->
            </ul>
        </div>
    </nav>
</template>

<script>
    export default {
        data() {
            return {
                files: [],
                levels: [],
                collapsed: [],
            };
        },
        mounted() {
            if (localStorage.sidebarCollapsed) {
                this.collapsed = localStorage.sidebarCollapsed.split(';');
            }
            this.load();
            this.$root.$on('filesChange', () => {
                this.load();
            });
        },
        computed: {
            routePath() {
                try {
                    return this.$route.params.path || '';
                } catch (e) { /*console.log(e);*/ };
                return '';
            }
        },
        watch: {
            routePath(newVal, oldVal) {
                this.scrollToActive();
            },
        },
        methods: {
            load() {
                axios.get('/api/file')
                    .then((response) => {
                        this.files = response.data.files;
                        MDocs.files = response.data.files;
                        // Format tree list
                        var levels = [];
                        function levelFill(list, level) {
                            for (var i in list) {
                                if (list[i].path != 'index' || list[i].children.length > 0) {
                                    levels.push({
                                        name: list[i].name,
                                        path: list[i].path,
                                        file: list[i].file,
                                        level: level + 0,
                                        show: 1,
                                    });
                                }
                                levelFill(list[i].children, level + 1);
                            }
                        }
                        levelFill(response.data.tree, 0);
                        this.levels = levels;
                        this.updateCollapsedShow();
                        this.scrollToActive();
                    }).catch((error) => {
                        Vue.handleAxiosError(error);
                    });
            },
            formatPath(path) {
                path = path.split('/').reverse();
                var parts = [], i;
                for (i in path) {
                    parts.push('<span class="sidebar-path-level-' + Math.min(parseInt(i) + 1, 3) + '">' + path[i] + (i > 0 ? '&nbsp;&#x2F;&nbsp;' : '') + '</span>');
                }
                return parts.reverse().join('');
            },
            levelName(file) {
                return ('<span class="sidebar-level-marker ' + (this.routePath.indexOf(file.path) == 0 ? 'active' : '') + '">&raquo;</span>').repeat(file.level + 1) + '<span class="sidebar-path-level-' + (!file.file ? '1' : '1') + '">' + file.name + '</span>';
            },
            toggleCollapse(file) {
                if (file.file) {
                    return;
                }
                if (this.collapsed.indexOf(file.path) >= 0) {
                    this.collapsed.splice(this.collapsed.indexOf(file.path), 1);
                } else {
                    this.collapsed.push(file.path);
                }
                localStorage.sidebarCollapsed = this.collapsed.join(';');
                this.updateCollapsedShow();
            },
            updateCollapsedShow() {
                var i, show, minLevel = 100;
                for (i in this.levels) {
                    if (minLevel >= this.levels[i].level) {
                        show = 1;
                        minLevel = 100;
                    }

                    this.levels[i].show = show;

                    if (this.collapsed.indexOf(this.levels[i].path) >= 0) {
                        if (this.levels[i].file) {
                            this.collapsed.splice(this.collapsed.indexOf(this.levels[i].path), 1);
                        } else {
                            minLevel = Math.min(minLevel, this.levels[i].level);
                            show = 0;
                        }
                    }
                }
            },
            scrollToActive() {
                let sidebar = $(this.$refs.sidebarContainer);
                // Let some time to update the active link
                setTimeout(function() {
                    let link = sidebar.find('.nav-link.active:first');
                    if (link.length > 0) {
                        let sidebarRect = sidebar[0].getBoundingClientRect();
                        let linkRect = link[0].getBoundingClientRect();
                        let top = linkRect.top - sidebarRect.top + sidebar[0].scrollTop - (sidebar[0].clientHeight / 2) + (link[0].offsetHeight/* / 2*/);

                        // Apply the scroll movement
                        sidebar.css('scroll-behavior', 'smooth');
                        sidebar[0].scrollTo(0, top);
                    }
                }, 50);
            },
        }
    }
</script>
