<template>
    <nav class="col-md-3 col-lg-2 d-none d-md-block bg-light sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li v-for="file in files" class="nav-item">
                    <router-link class="nav-link":to="'/' + file.path" :class="{ active: file.path == $route.params.path }" v-html="formatPath(file.path)"></router-link>
                </li>
            </ul>
        </div>
    </nav>
</template>

<script>
    export default {
        data() {
            return {
                files: []
            };
        },
        mounted() {
            this.load();
            this.$root.$on('filesChange', () => {
                this.load();
            });
        },
        methods: {
            load() {
                axios.get('/api/file')
                    .then((response) => {
                        this.files = response.data.files;
                        MDocs.files = response.data.files;
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
            }
        }
    }
</script>
