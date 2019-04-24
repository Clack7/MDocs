/**
 * Util vue extensions
 */
export default {
    // The install method will be called with the Vue constructor as the first argument, along with possible options
    install (Vue, options) {
        // console.log(options);

        // 1. add global method or property
        Vue.handleAxiosError = function (error) {
            if (error.response) {
              // The request was made and the server responded with a status code
              // that falls out of the range of 2xx
              console.log(error.response.data);
              console.log(error.response.status);
              console.log(error.response.headers);
            } else if (error.request) {
              // The request was made but no response was received
              // `error.request` is an instance of XMLHttpRequest in the browser and an instance of
              // http.ClientRequest in node.js
              console.log(error.request);
            } else {
              // Something happened in setting up the request that triggered an Error
              console.log('Error', error.message);
            }
            console.log(error.config);
        }

        // 2. add a global asset
        // Vue.directive('my-directive', {
        //     bind (el, binding, vnode, oldVnode) {
        //         // some logic ...
        //     }
        //     // ...
        // })

        // 3. inject some component options
        // Vue.mixin({
        //     created: function () {
        //         // some logic ...
        //     }
        //     // ...
        // })

        // 4. add an instance method
        // Vue.prototype.$myMethod = function (methodOptions) {
        //     // some logic ...
        // }
    }
}
