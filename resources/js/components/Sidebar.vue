<template>
    <nav class="col-md-3 col-lg-2 d-none d-md-block bg-light sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li v-for="file in levels" class="nav-item">
                    <span class="nav-link disabled" v-if="!file.file" v-html="levelName(file)" :class="{ 'child-active': routePath.indexOf(file.path) == 0 && file.path != routePath }"></span>
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
            };
        },
        mounted() {
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
                                levels.push({
                                    name: list[i].name,
                                    path: list[i].path,
                                    file: list[i].file,
                                    level: level + 0,
                                });
                                levelFill(list[i].children, level + 1);
                            }
                        }
                        levelFill(response.data.tree, 0);
                        this.levels = levels;
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
            }
        }
    }
</script>
