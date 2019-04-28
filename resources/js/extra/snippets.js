let today = new Date();
const snippets = [
    {
        score: 3,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-** Bold",
        // optional, snippet that can be inseted instead of value
        snippet: "**${1:Text}** ${0}",
        // short description
        meta: "MDocs"
    },
    {
        score: 3,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-_ Italic",
        // optional, snippet that can be inseted instead of value
        snippet: "_${1:Text}_ ${0}",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Task List",
        // optional, snippet that can be inseted instead of value
        snippet: "- [ ] ${0}",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Date",
        // optional, snippet that can be inseted instead of value
        snippet: today.getUTCFullYear() + '/' + ('0' + (today.getUTCMonth() + 1)).slice(-2) + '/' + ('0' + today.getUTCDate()).slice(-2),
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Link",
        // optional, snippet that can be inseted instead of value
        snippet: "[${1:Text}](${2:url})",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Image Link",
        // optional, snippet that can be inseted instead of value
        snippet: "[![${1:alt}](${2:src})](${2:src})",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Image",
        // optional, snippet that can be inseted instead of value
        snippet: "![${1:alt}](${2:src})",
        // short description
        meta: "MDocs"
    },

    {
        score: 1,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Objetive Header",
        // optional, snippet that can be inseted instead of value
        snippet: "## ${1:000}. ${2:title} <small style=\"float:right;\">${3:" + today.getUTCFullYear() + "}/${4:" + ('0' + (today.getUTCMonth() + 1)).slice(-2) + "}/${5:" + ('0' + today.getUTCDate()).slice(-2) + "}</small>",
        // short description
        meta: "MDocs"
    },
];

export default snippets;