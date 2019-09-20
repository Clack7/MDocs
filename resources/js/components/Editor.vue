<template>
    <div @dragenter="dragOverlayToggle(true, $event)">
        <div class="error-alert alert alert-danger m-0 rounded-0" v-if="error != ''" @click.prevent="error = ''" v-html="error"></div>
        <div class="card border-0">
            <path-header-component mode="editor"></path-header-component>
            <div class="row no-gutters">
                <div class="col-6" ref="editorColumn" :style="{ height: columnHeight + 'px', overflow: 'auto', 'border-right': '1px solid #ccc' }">
                    <div style="position:relative;height:100%;" ref="editorHolder">
                        <!-- <pre id="editor"></pre> -->
                    </div>
                    <div class="editor-bottom-actions">
                        <a @click.prevent="selectionComment" href="#" v-if="selection.comment">Comment Selection</a>
                        <a @click.prevent="selectionFold" href="#" v-if="selection.fold">Fold Selection</a>
                        <a @click.prevent="selectionDownloadUrl" href="#" v-if="selection.youtubeThumbnail">Youtube Thumbnail</a>
                        <a @click.prevent="selectionDownloadUrl" href="#" v-if="selection.downloadUrl">Download Selection</a>
                        <a @click.prevent="selectionParseGraph" href="#" v-if="selection.parseGraph">Parse SVG Graph</a>
                    </div>
                </div>
                <div class="col-6" ref="previewColumn" :style="{ height: columnHeight + 'px', overflow: 'auto' }" @scroll="previewScroll">
                    <div v-html="contentMarked" class="markdown-body" :class="{ 'images-small': imagesSmall }"></div>
                </div>
                <div style="position: absolute;bottom:5px;right:10px;text-align: right;">
                    <!-- <a href="#" v-if="draftContent != null && draftContent != ''" style="display:inline-block;font-size: 12px;position: relative;top:3px;" class="text-muted">Remove</a>&nbsp; -->
                    <a @click="loadDraft" href="#" v-if="draftContent != null && draftContent != ''" style="display:inline-block;font-size: 12px;position: relative;top:3px;">Load Draft</a>&nbsp;
                    <div class="spinner-border spinner-border-sm text-primary fade-opacity" :class="{ 'fade-opacity-show': draftSaving > 0 }">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <transition name="slide-fade-fast">
                    <div v-if="applied" style="position: absolute;top:55px;right:17px;text-align: right;font-size:13px;">
                        <div class="alert alert-success m-0 p-1 px-2 rounded-0">Saved!</div>
                    </div>
                </transition>
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

        <div class="drop-overlay"  :class="{ 'drop-overlay-show': dragOverlay }" @dragleave="dragOverlayToggle(false)" @drop="dropUpload($event)" @dragover="dragPreventOpen($event)">
            <div class="d-flex justify-content-center align-items-center h-100">
                Drop files here to upload.
            </div>
        </div>
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
                applied: false,
                editor: null,
                columnHeight: 100,
                onScrollEditor: true,
                onScrollEditorTO: null,
                onScrollPreview: true,
                onScrollPreviewTO: null,
                error: '',
                createAction: null,
                showModal: false,
                imagesSmall: true,
                draftSaving: 0,
                draftContent: null,
                selection: {
                    comment: false,
                    fold: false,
                    downloadUrl: false,
                    youtubeThumbnail: false,
                    parseGraph: false,
                },
                regex: {
                    url: /^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?$/,
                    youtube: /^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$/,
                    graph: /^``` (uml|graph TD|graph TB|graph BT|graph RL|graph LR|sequenceDiagram|gantt)\n(.|\n)+\n```$/
                },
                dragOverlay: false,
                dragOverlayTO: null,
            };
        },
        mounted() {
            // Remove the md extension from the url
            if (this.path_cur.match(/\.md$/i) != null) {
                this.$router.replace('/update/' + this.path_cur.slice(0, -3));
                return;
            }

            var that = this;

            // Load editor
            var draftDebounce = _.debounce(function () {
                that.saveDraft();
            }, 1000), draftSave = false;
            this.editor = MDocs.editor.load(this.$refs.editorHolder, {
                onSave: this.save,
                onApply: this.apply,
                onCancel: this.cancel,
                onChange: function() {
                    that.update(that.editor.getValue(), true);
                    if (draftSave) {
                        draftDebounce();
                    }  else {
                        draftSave = true;
                    }
                },
                onScroll: function(scroll) {
                    that.editorScroll(scroll);
                }
            });
            this.editor.selection.on("changeSelection", function(a, b, c) {
                that.selection.comment = false;
                that.selection.fold = false;
                that.selection.downloadUrl = false;
                that.selection.youtubeThumbnail = false;
                that.selection.parseGraph = false;
                var text = _.trim(that.editor.getSelectedText());
                if (text != '') {
                    that.selection.comment = true;
                    that.selection.fold = true;
                    if (that.regex.youtube.test(text)) {
                        that.selection.youtubeThumbnail = true;
                        return;
                    }
                    if (that.regex.url.test(text)) {
                        that.selection.downloadUrl = true;
                        return;
                    }
                    if (that.regex.graph.test(text)) {
                        that.selection.parseGraph = true;
                    }
                }
            })

            // Load content
            // if (this.path_cur != '') {
                this.content = 'Loading...';
                axios.get('/api/file/' + (this.path_cur == '' ? '@empty' : this.path_cur))
                    .then((response) => {
                        this.createAction = false;
                        this.update(response.data.content, false);
                        this.draftContent = response.data.draft;
                        this.saving = false;
                    }).catch((error) => {
                        if (error.response.status == 404 && error.response.data.message == 'File not found.') {
                            this.createAction = true;
                            this.saving = false;
                            this.update(this.path_new != '' ? '# ' + this.path_new.split('/').pop() + "\n\n" : '');
                            this.path_cur = '';
                        } else {
                            this.error = 'Server error: ' + error.response.data.message;
                        }
                        Vue.handleAxiosError(error);
                    });
            // } else {
            //     this.update('', false);
            //     this.saving = false;
            // }

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
                        axios.post('/api/file/attach', { path: that.path_cur, base64: base64Image })
                            .then(({ data }) => {
                                that.editor.session.insert(that.editor.getCursorPosition(), '![img](' + data.url + ' "=x300")')
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
                        that.editorUpdateFolds();
                    }, 100);
                }
            },
            editorUpdateFolds() {
                var that = this;
                ace.config.loadModule("ace/range", function(m) {
                    // Expand previous graph folds
                    var expand = [], folds = that.editor.session.getAllFolds(), i;
                    for (i in folds) {
                        if (folds[i].placeholder == '..graph..') {
                            expand.push(folds[i]);
                        }
                    }
                    that.editor.session.expandFolds(expand);
                    // Find current folds
                    var str = that.content, match, idx, end;
                    str = str.split("\n");
                    for (i in str) {
                        match = str[i].match(/^<div class="graph-container">(.*)<\/div>$/);
                        if (match != null) {
                            that.editor.session.addFold("..graph..", new m.Range(parseInt(i), 29, parseInt(i), str[i].length - 6));
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
                this.saveProcess(false);
            },
            apply() {
                this.saveProcess(true);
            },
            saveProcess(apply) {
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
                        this.$root.$emit('filesChange');
                        if (apply) {
                            this.applied = true;
                            clearTimeout(this.appliedTO);
                            var that = this;
                            this.appliedTO = setTimeout(function() {
                                that.applied = false;
                            }, 1000);
                            this.createAction = false;
                            if (data.path != this.path_cur) {
                                this.path_cur = data.path;
                                this.$router.push('/update/' + data.path);
                            }
                        } else {
                            this.$router.push('/' + data.path);
                        }
                    }).catch((error) => {
                        this.saving = false;
                        this.error = error.response.data.message;
                        Vue.handleAxiosError(error);
                    });
            },
            saveDraft() {
                this.draftSaving++;
                var draftSavingMinus = function(that) {
                    setTimeout(function() {
                        that.draftSaving--;
                    }, 1500);
                };
                axios.post('/api/file/draft', { path_cur: this.path_cur, content: this.content })
                    .then(({ data }) => {
                        draftSavingMinus(this);
                    }).catch((error) => {
                        draftSavingMinus(this);
                        this.error = error.response.data.message;
                        Vue.handleAxiosError(error);
                    });
            },
            loadDraft() {
                if (this.draftContent !== null && this.draftContent != '') {
                    this.update(this.draftContent, false);
                }
            },
            cancel() {
                this.$router.push('/' + (typeof this.$route.params.path == 'undefined' ? '' : this.$route.params.path));
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
                        this.$root.$emit('filesChange');
                        this.$router.push('/' + this.path_cur);
                    }).catch((error) => {
                        this.saving = false;
                        this.error = error.response.data.message;
                        Vue.handleAxiosError(error);
                    });
            },
            selectionComment() {
                this.editor.session.replace(this.editor.selection.getRange(), this.commentWrap(this.editor.getSelectedText()));
            },
            selectionFold() {
                this.editor.session.addFold("...", this.editor.selection.getRange());
            },
            selectionDownloadUrl() {
                if (Vue.loaderActive()) {
                    return;
                }

                var that = this;
                var text = that.editor.getSelectedText();
                var range = that.editor.selection.getRange();
                var textClean = _.trim(text);
                var doDownload = function(url) {
                    Vue.loaderShow();
                    axios.post('/api/file/attach-url', { path: that.path_cur, url: url })
                        .then(({ data }) => {
                            Vue.loaderHide();
                            that.editor.session.replace(range, text.replace(textClean, '![img](' + data.url + ' "=x300")'));
                        }).catch((error) => {
                            Vue.loaderHide();
                            that.error = error.response.data.message;
                            Vue.handleAxiosError(error);
                        });
                };
                if (textClean != '') {
                    if (that.regex.youtube.test(textClean)) {
                        var code = textClean.match(that.regex.youtube)[5];
                        doDownload('https://img.youtube.com/vi/' + code + '/maxresdefault.jpg');
                        return;
                    }
                    if (that.regex.url.test(textClean)) {
                        doDownload(textClean);
                    }
                }
            },
            selectionParseGraph() {
                if (Vue.loaderActive()) {
                    return;
                }

                var that = this;
                var text = that.editor.getSelectedText();
                var range = that.editor.selection.getRange();
                var textClean = _.trim(text);
                if (textClean != '' && that.regex.graph.test(textClean)) {
                    var parsed = this.$options.filters['marked'](textClean);
                    var match = parsed.match(/^<div class="graph-container">(.*)<\/div>$/);
                    if (match) {
                        parsed = match[1];
                    }
                    // Find width and height
                    var h = '', w = '';
                    match = parsed.match(/^<svg width="(\d+)" height="(\d+)" /);
                    if (match) {
                        w = match[1];
                        h = match[2];
                    } else {
                        match = parsed.match(/ viewBox="([-0-9\.]+) ([-0-9\.]+) ([0-9\.]+) ([0-9\.]+)"/);
                        if (match) {
                            w = Math.floor(match[3]);
                            w = w < 1 ? '' : w;
                            h = Math.floor(match[4]);
                            h = h < 1 ? '' : h;

                            // Replace height and width from main svg element
                            match = parsed.match(/^<svg ([^>]+)>/);
                            if (match) {
                                match[1] = match[0].replace(/ width="[0-9\.%]*"/, '').replace(/ height="[0-9\.%]*"/, '').replace('<svg', '<svg width="' + w + '" height="' + h + '" ');
                                parsed = parsed.replace(match[0], match[1]);
                            }
                        }
                    }

                    Vue.loaderShow();
                    axios.post('/api/file/attach-svg', { path: that.path_cur, svg: parsed })
                        .then(({ data }) => {
                            Vue.loaderHide();
                            that.editor.session.replace(range, text.replace(textClean, that.commentWrap(textClean) + "\n" + '![svg](' + data.url + ' "=' + w + 'x' + h + '")')); // resize image only with width
                        }).catch((error) => {
                            Vue.loaderHide();
                            that.error = error.response.data.message;
                            Vue.handleAxiosError(error);
                        });
                }
            },
            commentWrap(text) {
                var countEnd = (text.match(/-->/g) || []).length;
                if (countEnd > 0) {
                    text = text.split('-->').join('--\\>') + ' (' + countEnd + ' HTML end comment escaped with --\\>)';
                }
                return '<!-- ' + text + ' -->';
            },
            dragOverlayToggle(positive, e) {
                var that = this;
                clearTimeout(that.dragOverlayTO);
                if (positive) {
                    if (that.dropGetFiles(e).length > 0) {
                        that.dragOverlay = true;
                    }
                } else {
                    that.dragOverlayTO = setTimeout(function() {
                        that.dragOverlay = false;
                    }, 300);
                }
            },
            dropUpload(e) {
                // Prevent default behavior (Prevent file from being opened)
                e.preventDefault();
                this.dragOverlayToggle(false);

                var that = this, files = this.dropGetFiles(e), i;
                if (files.length < 1) {
                    return;
                }
                let formData = new FormData();
                formData.append('path', that.path_cur);
                for (i in files) {
                    formData.append('files[]', files[i]);
                }
                Vue.loaderShow();
                axios.post('/api/file/attach-drop', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })
                    .then(({ data }) => {
                        Vue.loaderHide();
                        var text = [], uk;
                        for (uk in data.urls) {
                            text.push('![img](' + data.urls[uk] + ' "=x300")');
                        }
                        if (text.length > 0) {
                            that.editor.session.insert(that.editor.getCursorPosition(), text.join("\n") + "\n");
                        }
                        if (data.errors.length > 0) {
                            that.error = data.errors.join('<br>');
                        }
                    }).catch((error) => {
                        Vue.loaderHide();
                        that.error = error.response.data.message;
                        Vue.handleAxiosError(error);
                    });
            },
            dragPreventOpen(e) {
                // Prevent default behavior (Prevent file from being opened)
                e.preventDefault();
            },
            dropGetFiles(e) {
                var files = [];
                if (e.dataTransfer.items) {
                    // Use DataTransferItemList interface to access the file(s)
                    for (var i = 0; i < e.dataTransfer.items.length; i++) {
                        // If dropped items aren't files, reject them
                        if (e.dataTransfer.items[i].kind === 'file') {
                            files.push(e.dataTransfer.items[i].getAsFile());
                        }
                    }
                } else {
                    // Use DataTransfer interface to access the file(s)
                    for (var i = 0; i < e.dataTransfer.files.length; i++) {
                        files.push(e.dataTransfer.files[i]);
                    }
                }
                return files;
            },
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