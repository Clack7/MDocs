<template>
    <div>
        <div class="alert alert-danger m-0 rounded-0" v-if="error != ''">{{ error }}</div>
        <div class="card border-0">
            <path-header-component mode="editor"></path-header-component>
            <div class="row no-gutters">
                <div class="col-6" ref="editorColumn" :style="{ height: columnHeight + 'px', overflow: 'auto', 'border-right': '1px solid #ccc' }">
                    <div style="position:relative;height:100%;" ref="editorHolder">
                        <!-- <pre id="editor"></pre> -->
                    </div>
                </div>
                <div class="col-6" ref="previewColumn" :style="{ height: columnHeight + 'px', overflow: 'auto' }" @scroll="previewScroll">
                    <div v-html="contentMarked" class="markdown-body" :class="{ 'images-small': imagesSmall }"></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="destroyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Delete file?</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <strong>{{ path_cur }}</strong>.md
                    </div>
                    <div class="modal-footer text-center d-block">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
                        &nbsp;&nbsp;&nbsp;
                        <button type="button" class="btn btn-danger" data-dismiss="modal" @click="destroyFile()">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <gallery-component refContainer="previewColumn"></gallery-component>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                path_cur: this.$route.params.path || '',
                path_new: this.$route.params.path || '',
                content: '',
                contentMarked: 'Loading...',
                saving: true,
                editor: null,
                columnHeight: 100,
                onScrollEditor: true,
                onScrollEditorTO: null,
                onScrollPreview: true,
                onScrollPreviewTO: null,
                error: '',
                createAction: true,
                showModal: false,
                imagesSmall: true
            };
        },
        mounted() {
            var that = this;

            // Load editor
            this.editor = MDocs.editor.load(this.$refs.editorHolder, {
                onSave: this.save,
                onChange: function() {
                    that.update(that.editor.getValue(), true);
                },
                onScroll: function(scroll) {
                    that.editorScroll(scroll);
                }
            });

            // Load content
            if (this.path_cur != '') {
                this.content = 'Loading...';
                axios.get('/api/file/' + this.path_cur)
                    .then((response) => {
                        this.update(response.data.content, false);
                        this.saving = false;
                        this.createAction = false;
                    }).catch((error) => {
                        if (error.response.status == 404 && error.response.data.message == 'File not found.') {
                            this.saving = false;
                            this.update('');
                            this.path_cur = '';
                        } else {
                            this.error = 'Server error: ' + error.response.data.message;
                        }
                        Vue.handleAxiosError(error);
                    });
            } else {
                this.update('', false);
                this.saving = false;
            }

            window.addEventListener('resize', this.adjustWindowHeight);
            this.adjustWindowHeight();
            this.$refs.editorColumn.onpaste = this.handlePaste;
        },
        beforeDestroy() {
            MDocs.editor.unload();
            window.removeEventListener('resize', this.adjustWindowHeight);
        },
        methods: {
            adjustWindowHeight(e) {
                this.columnHeight = document.documentElement.clientHeight - (this.$refs.previewColumn.getBoundingClientRect().top + document.documentElement.scrollTop + 1);
            },
            handlePaste(e) {
                var that = this;

                var callback = function(base64Image) {
                    // If there's an image, open it in the browser as a new window :)
                    if (base64Image) {
                        that.editorImagesHide();
                        axios.post('/api/file/attach', { path: that.path_cur, base64: base64Image })
                            .then(({ data }) => {
                                that.editor.session.insert(that.editor.getCursorPosition(), '![img](' + data.url + ')')
                            }).catch((error) => {
                                that.error = error.response.data.message;
                                Vue.handleAxiosError(error);
                            });
                    }
                };

                if (e.clipboardData == false) {
                    return;
                }

                var items = e.clipboardData.items;
                if (items == undefined) {
                    return;
                }

                for (var i = 0; i < items.length; i++) {
                    // Skip content if not image
                    if (items[i].type.indexOf("image") == -1) continue;
                    // Retrieve image on clipboard as blob
                    var blob = items[i].getAsFile();

                    // Create an abstract canvas and get context
                    var mycanvas = document.createElement("canvas");
                    var ctx = mycanvas.getContext('2d');

                    // Create an image
                    var img = new Image();

                    // Once the image loads, render the img on the canvas
                    img.onload = function(){
                        // Update dimensions of the canvas with the dimensions of the image
                        mycanvas.width = this.width;
                        mycanvas.height = this.height;

                        // Draw the image
                        ctx.drawImage(img, 0, 0);

                        // Execute callback with the base64 URI of the image
                        if(typeof(callback) == "function"){
                            callback(mycanvas.toDataURL("image/png"));
                        }
                    };

                    // Crossbrowser support for URL
                    var URLObj = window.URL || window.webkitURL;

                    // Creates a DOMString containing a URL representing the object given in the parameter
                    // namely the original Blob
                    img.src = URLObj.createObjectURL(blob);
                }
            },
            update(content, fromEditor) {
                this.content = content;
                // _.throttle(function () {
                    this.contentMarked = this.$options.filters['marked'](this.content);
                // }, 100),
                var that = this;
                if (!fromEditor) {
                    setTimeout(function() {
                        that.editor.setValue(that.content, -1);
                        that.editor.resize();
                        that.editor.focus();
                        that.editorImagesHide();
                    }, 100);
                }
            },
            editorImagesHide() {
                var that = this;
                ace.config.loadModule("ace/range", function(m) {
                    that.editor.session.expandFolds(that.editor.session.getAllFolds());
                    var str = that.content, match, idx, end;
                    str = str.split("\n");
                    for (let i in str) {
                        match = str[i].match(/\(data:image\/[^\)]+\)/gi);
                        if (match != null) {
                            for (let a in match) {
                                idx = str[i].indexOf(match[a]);
                                console.log(match[a].substr(-2, 1));
                                if (match[a].substr(-2, 1) == '"') {
                                    end = -3;
                                    while (end < 0) {
                                        end--;
                                        if (match[a].substr(end, 1) == '"') {
                                            end--;
                                            break;
                                        } else if (end < -15) {
                                            end = -1;
                                            break;
                                        }
                                    }
                                    end = idx + match[a].length + end;
                                } else {
                                    end = idx + match[a].length - 1;
                                }
                                that.editor.session.addFold("...", new m.Range(parseInt(i),idx + 12,parseInt(i), end));
                            }
                        }
                    }
                });
            },
            editorImagesToggle() {
                this.imagesSmall = !this.imagesSmall;
            },
            editorScroll(scroll) {
                if (!this.onScrollEditor) {
                    return;
                }
                var that = this;
                this.onScrollPreview = false; clearTimeout(this.onScrollPreviewTO);
                this.onScrollPreviewTO = setTimeout(function() { that.onScrollPreview = true; }, 500);

                var editPercent = scroll / (this.editor.renderer.layerConfig.maxHeight - this.columnHeight);
                var prevTop = (this.$refs.previewColumn.scrollHeight - this.columnHeight) * editPercent;
                this.$refs.previewColumn.scrollTo(0, prevTop);
            },
            previewScroll() {
                if (!this.onScrollPreview) {
                    return;
                }
                var that = this;
                this.onScrollEditor = false; clearTimeout(this.onScrollEditorTO);
                this.onScrollEditorTO = setTimeout(function() { that.onScrollEditor = true; }, 500);

                var prevPercent = this.$refs.previewColumn.scrollTop / (this.$refs.previewColumn.scrollHeight - this.columnHeight);
                var editTop = (this.editor.renderer.layerConfig.maxHeight - this.columnHeight) * prevPercent;
                this.editor.getSession().setScrollTop(editTop);
            },
            save() {
                if (this.saving) {
                    return;
                }
                this.error = '';
                if (this.path_new == '') {
                    this.error = 'The file path is required.';
                    return;
                }
                this.saving = true;
                axios.post('/api/file/' + this.path_new, { path_cur: this.path_cur, content: this.content })
                    .then(({ data }) => {
                        this.saving = false;
                        this.$router.push('/' + data.path);
                        this.$root.$emit('filesChange');
                    }).catch((error) => {
                        this.saving = false;
                        this.error = error.response.data.message;
                        Vue.handleAxiosError(error);
                    });
            },
            cancel() {
                this.$router.push('/' + this.$route.params.path);
            },
            destroyConfirm() {
                if (this.saving) {
                    return;
                }
                $('#destroyModal').modal('show');
            },
            destroyFile() {
                this.saving = true;
                axios.delete('/api/file/' + this.path_cur)
                    .then(({ data }) => {
                        this.$router.push('/' + this.path_cur);
                        this.$root.$emit('filesChange');
                    }).catch((error) => {
                        this.saving = false;
                        this.error = error.response.data.message;
                        Vue.handleAxiosError(error);
                    });
            }
        }
    }
</script>

<style scoped>
/* Confirm modal */
.modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, .5);
    display: table;
    transition: opacity .3s ease;
}
</style>