/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
require('./bootstrap');

window.Vue = require('vue');

// Util plugin
import Util from './extra/util';
Vue.use(Util, UtilConfig);

// Marked
import marked from 'marked';
// Image size
const renderer = new marked.Renderer();
function sanitizeMarkedImage(str) {
    return str.replace(/&<"/g, function (m) {
        if (m === "&") return "&amp;"
        if (m === "<") return "&lt;"
        return "&quot;"
    })
}
renderer.image = function (src, title, alt) {
    const exec = /=\s*(\d*)\s*x\s*(\d*)\s*$/.exec(title)
    let res = '<img src="' + sanitizeMarkedImage(src) + '" alt="' + sanitizeMarkedImage(alt)
    if (exec && exec[1]) res += '" width="' + exec[1]
    if (exec && exec[2]) res += '" height="' + exec[2]
    return res + '">'
}
renderer.listitem = function(text, task) {
    return '<li' + (task ? ' class="task-list-item"' : '') + '>' + text + '</li>\n';
};
// Mermaid code block
import mermaid from 'mermaid';
window.mermaid = mermaid;
mermaid.initialize({
    themeCSS: ".label foreignObject { overflow: visible; }"
  // theme: 'forest',
  // gantt: { axisFormatter: [
  //   ['%Y-%m-%d', (d) => {
  //     return d.getDay() === 1
  //   }]
  // ] }
});
let mermaidCounter = 0;
import nomnoml from 'nomnoml';
window.nomnoml = nomnoml;
let nomnomlDirectives =
`#fill: #ECECFF; #F0F0FF
#lineWidth: 1.5
#fillArrows: true
#.class: none` // remove bold
;
let rendererCode = renderer.code;
renderer.code = function(code, infostring, escaped) {
    if ([
        'graph TD',
        'graph TB',
        'graph BT',
        'graph RL',
        'graph LR',
        'sequenceDiagram',
        'gantt',
        'uml',
    ].indexOf(infostring) >= 0) {
        mermaidCounter++;
        let graphSvg = '<div class="alert alert-warning">Graph Parse Error</div>';
        try {
            if (infostring == 'uml') {
                graphSvg = nomnoml.renderSvg(nomnomlDirectives + "\n" + code);
            } else {
                graphSvg = mermaid.render('mermaid_' + mermaidCounter, infostring + "\n" + code);
            }
        } catch (e) { console.log(e); }

        return '<div class="graph-container">' + graphSvg + '</div>';
    }

    return rendererCode.apply(renderer, [code, infostring, escaped]);
}
marked.setOptions({
    renderer: renderer
});

// Emojis
import emoji from 'node-emoji';
import hljs from 'highlight.js';
// Add filter
Vue.filter('marked', function(input) {
    const replacer = (match) => emoji.emojify(match);
    input = input.replace(/(:.*:)/g, replacer);
    return marked(input, {
        gfm: true,
        breaks: true,
        highlight: function(code, lang) {
            if (typeof hljs.getLanguage(lang) == 'undefined') {
                lang = 'plaintext';
            }
            return hljs.highlight(lang, code).value;
        }
    })
});

// Ace
window.MDocs = { name: 'MDocs', files: [], editor: null };
import ace from 'ace-builds/src-noconflict/ace';
import 'ace-builds/webpack-resolver';
import './extra/ext-emmet.js';
import snippetManager from 'ace-builds/src-noconflict/ext-language_tools';
snippetManager.setCompleters(null);
snippetManager.addCompleter({
    identifierRegexps: [/[a-zA-Z_0-9@\$\:\-\u00A2-\uFFFF/]/], // added : to start, @ for markers and / slash for files
    getCompletions: function(editor, session, pos, prefix, callback) {
        // console.log('prefix', prefix);
        if (prefix.length < 2 || prefix.charAt(0) != ':') { callback(null, []); return }
        callback(null, emoji.search(prefix.substring(1)).slice(0,10).map(function(emo) {
            return { caption: emo.emoji + ' :' + emo.key + ':', value: emo.emoji, score: 1, meta: 'emoji' };
        }));
    }
});
// Snippets
import snippets from './extra/snippets';
snippetManager.addCompleter({
    // identifierRegexps: [/[a-zA-Z_0-9\$\-\u00A2-\uFFFF]/], // added . to start
    getCompletions: function(editor, session, pos, prefix, callback) {
        // console.log('prefix', prefix);
        if (/*prefix.length < 2 || */['-', '@'].indexOf(prefix.charAt(0)) < 0) { callback(null, []); return }
        callback(null, snippets);
    }
});
// Autocomplete paths
// Same code as in FileController.php
function getRelativePath(fPath, tPath) {
    fPath = fPath.split('/'); // from
    fPath.pop(); // remove current from
    tPath = tPath.split('/'); // to
    var current = tPath.pop(); // save current to
    var rPath = tPath.slice(0); // relative
    var i, o;
    for (i in fPath) {
        // find first non-matching dir
        if (typeof tPath[i] != 'undefined' && fPath[i] === tPath[i]) {
            // ignore this directory
            rPath.shift();
        } else {
            // get number of remaining dirs to from
            var remaining = fPath.length - i;
            for (o = 0; o < remaining; o++) {
                rPath.unshift('..');
            }
            break;
        }
    }
    rPath.push(current); // readd current to
    return './' + rPath.join('/');
}
// var testRelativePath = [
//  ['a', 'b', './b'],
//  ['a/b/c', 'a/b/d', './d'],
//  ['a/b/c', 'a/b/c/d', './c/d'],
//  ['a/b/c', 'd/e/f/d', './../../d/e/f/d'],
//  ['d/e/f/d', 'a/b/c', './../../../a/b/c'],
//  ['a/b/c', 'a/f/d', './../f/d'],
// ];
// for (var t in testRelativePath) {
//  console.log(testRelativePath[t][0], testRelativePath[t][1], testRelativePath[t][2], getRelativePath(testRelativePath[t][0], testRelativePath[t][1]) == testRelativePath[t][2]);
// }
function rawurlencode(url) {
    url = (url + '')
    // Tilde should be allowed unescaped in future versions of PHP (as reflected below),
    // but if you want to reflect current
    // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
    return encodeURIComponent(url)
        .replace(/!/g, '%21')
        .replace(/'/g, '%27')
        .replace(/\(/g, '%28')
        .replace(/\)/g, '%29')
        .replace(/\*/g, '%2A');
}
snippetManager.addCompleter({
    // identifierRegexps: [/[a-zA-Z_0-9\$\-\u00A2-\uFFFF]/], // added . to start
    getCompletions: function(editor, session, pos, prefix, callback) {
        // Check files list
        if (window.MDocs.files.length < 1) {
            callback(null, []); return;
        }
        // Check prefix
        if (prefix.charAt(0) != '/') {
            callback(null, []); return;
        }
        // Check available route path
        var list = [], i, parts, path = null;
        try {
            path = app.$route.params.path;
        } catch (e) { console.log(e); };
        if (path == null) {
            callback(null, []); return;
        }
        // Display list
        for (i in window.MDocs.files) {
            parts = window.MDocs.files[i].path.split('/');
            list.push({
                caption: '/' + window.MDocs.files[i].path,
                value: '[' + parts[parts.length - 1] + '](' + rawurlencode(getRelativePath(path, window.MDocs.files[i].path)).replace(/%2F/g, '/') + '.md)',
                score: 1,
                meta: 'MDocs'
            });
        }
        callback(null, list);
    }
});

// Load editor handler
import editor from './extra/editor';
window.MDocs.editor = editor;

// Shortkey
Vue.use(require('vue-shortkey'));

// Router
import VueRouter from 'vue-router';
Vue.use(VueRouter);
let routes = [];
_.forEach(UtilConfig.spaRoutes, function(value, key) {
    routes.push({ path: key, component: require('./components/' + value + '.vue').default });
});
const router = new VueRouter({
    mode: 'history',
    routes // short for `routes: routes`
});

// Gallery
import blueimp from 'blueimp-gallery/js/blueimp-gallery.min.js';
window.blueimp = { Gallery: blueimp };

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i);
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default));

Vue.component('search-component', require('./components/Search.vue').default);
Vue.component('sidebar-component', require('./components/Sidebar.vue').default);
Vue.component('path-header-component', require('./components/PathHeader.vue').default);
Vue.component('gallery-component', require('./components/Gallery.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
    router,
    data: {
        loader: false
    }
});
