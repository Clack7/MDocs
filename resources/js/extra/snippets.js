const snippets = [
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
        snippet: "## ${1:000}. ${2:title} <small style=\"float:right;\">${3:2019}/${4:00}/${5:00}</small>",
        // short description
        meta: "MDocs"
    },
];

export default snippets;