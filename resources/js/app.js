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
import hljs from 'highlight.js';
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
marked.setOptions({
		renderer: renderer
});
// Emojis
import emoji from 'node-emoji';
// Add filter
Vue.filter('marked', function(input) {
		const replacer = (match) => emoji.emojify(match);
		input = input.replace(/(:.*:)/g, replacer);
		return marked(input, {
				gfm: true,
				breaks: true,
				highlight: function(code) {
						return hljs.highlightAuto(code).value;
				}
		})
});

// Ace
import ace from 'ace-builds/src-noconflict/ace';
import 'ace-builds/webpack-resolver';
import './extra/ext-emmet.js';
import snippetManager from 'ace-builds/src-noconflict/ext-language_tools';
snippetManager.setCompleters(null);
snippetManager.addCompleter({
	identifierRegexps: [/[a-zA-Z_0-9\$\:\-\u00A2-\uFFFF]/], // added : to start
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
		if (/*prefix.length < 2 || */prefix.charAt(0) != '-') { callback(null, []); return }
		callback(null, snippets);
	}
});

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

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i);
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default));

Vue.component('sidebar-component', require('./components/Sidebar.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
	el: '#app',
	router
});
