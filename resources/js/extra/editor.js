/**
 * Editor init and config
 */
export default  {
    instance: null,
    element: null,
    holder: null,
    onSave: null,
    onChange: null,
    onScroll: null,
    vimApi: null,
    load: function(parent, options) {
        let that = this;
        this.onSave   = options.onSave;
        this.onChange = options.onChange;
        this.onScroll = options.onScroll;

        if (this.instance === null) {
            // Create element
            this.element = document.createElement('pre');
            this.element.id = 'mdEditor';
            parent.appendChild(this.element);

            // Vim save command
            ace.config.loadModule("ace/keyboard/vim", function(m) {
                that.vimApi = m.CodeMirror.Vim;
                that.vimApi.defineEx("write", "w", function(cm, input) {
                    that.onSave();
                });
            });

            // Init ace editor
            this.instance = ace.edit("mdEditor", {
                theme: "ace/theme/chrome",
                mode: "ace/mode/markdown",
                wrap: true,
                // minLines: 5,
                // maxLines: 30,
                value: '',
                autoScrollEditorIntoView: true,
                fontFamily: 'Monaco',
                fontSize: '16px',
                showLineNumbers: false,
                showGutter: false,

                enableBasicAutocompletion: true,
                enableLiveAutocompletion: true,
                enableSnippets: true,
                enableEmmet: true
            });

            // Editor init
            this.instance.setKeyboardHandler("ace/keyboard/vim");
            this.instance.resize();
            this.instance.on("change", function(e) {
                that.onChange();
            });
            this.instance.getSession().on('changeScrollTop', function(scroll) {
                that.onScroll(scroll);
            });

            // Create holder
            this.holder = document.createElement('div');
            this.holder.className = 'd-none';
            document.body.appendChild(this.holder);
        } else {
            parent.appendChild(this.element);
            this.instance.resize();
        }

        return this.instance;
    },
    unload: function() {
        this.vimApi.exitInsertMode(this.instance.state.cm);
        this.onSave = function() {};
        this.onChange = function() {};
        this.onScroll = function() {};
        this.instance.setValue('', -1);
        this.holder.appendChild(this.element);
    }
}
