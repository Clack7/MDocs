<template>
    <div class="w-100 position-relative">
        <input ref="searchInput" v-model="query" @input="search()" :class="{ 'text-success': loading }" class="search-input form-control form-control-dark w-100" type="text" placeholder="Search" aria-label="Search" v-shortkey.native.focus="['alt', 'f']" v-on:keydown.down="resultFocus(true, $event)" v-on:keydown.tab.prevent="resultFocus(true, $event)" v-on:keydown.up="resultFocus(false, $event)" v-on:keydown.enter="goFocus()">
        <div class="search-autocomplete" v-if="showResults">
            <a href="#" v-for="(result, index) in results" :key="index" @click.prevent="go(result.path)" :class="{ active: focusIndex == index }">
                <div v-html="highlightQuery(result.path) + '<span>.md</span>'"></div>
                <p v-for="match in result.match" v-html="highlightQuery(match)"></p>
            </a>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                query: '',
                queryCancel: null,
                loading: false,
                results: [],
                focusIndex: null,
                showResults: true
            };
        },
        mounted() {
        },
        methods: {
            search() {
                this.query = this.query.trim();
                this.focusIndex = null;

                if (typeof this.queryCancel == 'function') {
                    this.queryCancel();
                    this.queryCancel = null;
                }

                if (this.query.length < 3) {
                    this.results = [];
                    this.loading = false;
                    return;
                }

                const CancelToken = axios.CancelToken;
                let that = this;
                this.loading = true;
                axios.get('/api/file/search', {
                        params: { query: this.query },
                        cancelToken: new CancelToken(function executor(c) {
                            // An executor function receives a cancel function as a parameter
                            that.queryCancel = c;
                        })
                    }).then((response) => {
                        this.results = response.data.result;
                        if (this.results.length > 0) {
                            this.focusIndex = 0;
                        }
                        this.loading = false;
                    }).catch((error) => {
                        if (error.message == undefined) {
                            return;
                        }
                        this.results = [];
                        this.loading = false;
                        Vue.handleAxiosError(error);
                    });
            },
            resultFocus(positive, event) {
                if (event.shiftKey) {
                    positive = !positive;
                }
                if (this.results.length == 0) {
                    this.focusIndex = null;
                    return;
                }
                let idx = this.focusIndex;
                if (idx === null) {
                    idx = positive ? -1 : this.results.length;
                }
                idx += positive ? 1 : -1;
                idx = idx < 0 ? this.results.length - 1 : (idx >= this.results.length ? 0 : idx);
                this.focusIndex = idx;
            },
            go(path) {
                this.query = '';
                this.search();
                this.$refs.searchInput.blur();
                this.$router.push('/' + path);
            },
            goFocus() {
                if (this.focusIndex === null) {
                    return;
                }
                this.go(this.results[this.focusIndex].path);
            },
            highlightQuery(match) {
                match = jQuery('<div>').text(match).html();
                if (this.query == '') {
                    return match;
                }
                let query = jQuery('<div>').text(this.query).html();
                return match.replace(new RegExp(query, 'gi'), '<em>' + query + '</em>');
            }
        }
    }
</script>
