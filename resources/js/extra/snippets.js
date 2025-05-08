let today = new Date();
const snippets = [
    {
        score: 4,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "--- Front Matter",
        // optional, snippet that can be inseted instead of value
        snippet: "---\ntitle: \"${1:Title}\"\n---\n\n${0}",
        // short description
        meta: "MDocs"
    },
    {
        score: 4,
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
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Table",
        // optional, snippet that can be inseted instead of value
        snippet: "| ${1:header} | ${2:header} |\n| -- | -- |\n| ${3:content} | ${4:content} |",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "- Check ✔️",
        // optional, snippet that can be inseted instead of value
        snippet: ":heavy_check_mark:",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "- Cross ❌",
        // optional, snippet that can be inseted instead of value
        snippet: ":x:",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "- Exclamation ❗",
        // optional, snippet that can be inseted instead of value
        snippet: ":exclamation:",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "- Question ❓",
        // optional, snippet that can be inseted instead of value
        snippet: ":question:",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "- Question 2 ⁉️",
        // optional, snippet that can be inseted instead of value
        snippet: ":interrobang:",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "- Plus ➕",
        // optional, snippet that can be inseted instead of value
        snippet: ":heavy_plus_sign:",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "- Minus ➖",
        // optional, snippet that can be inseted instead of value
        snippet: ":heavy_minus_sign:",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "- Diamond 💠",
        // optional, snippet that can be inseted instead of value
        snippet: ":diamond_shape_with_a_dot_inside:",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "@TODO",
        // optional, snippet that can be inseted instead of value
        snippet: "`@TODO`",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "@SEE",
        // optional, snippet that can be inseted instead of value
        snippet: "`@SEE`",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "@MORE",
        // optional, snippet that can be inseted instead of value
        snippet: "`@MORE`",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-UML Graph",
        // optional, snippet that can be inseted instead of value
        snippet:
"``` uml\n\
[${1:Start}]->[${2:Continue}]\n\
```\n",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Flowchart",
        // optional, snippet that can be inseted instead of value
        snippet:
"``` graph ${1:TD}\n\
    ${2:Start} --> ${3:Continue}\n\
```\n",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Sequence Diagram",
        // optional, snippet that can be inseted instead of value
        snippet:
"``` sequenceDiagram\n\
    Alice->>John: Hello John, how are you?\n\
    John-->>Alice: Great!\n\
```\n",
        // short description
        meta: "MDocs"
    },
    {
        score: 2,
        // string used for filtering
        // value: ".obj",
        // optional, allows to display a caption different from value
        caption: "-Gantt",
        // optional, snippet that can be inseted instead of value
        snippet:
"``` gantt\n\
    title A Gantt Diagram\n\
    dateFormat  YYYY-MM-DD\n\
    section Section\n\
    A task           :a1, 2014-01-01, 30d\n\
    Another task     :after a1  , 20d\n\
    section Another\n\
    Task in sec      :2014-01-12  , 12d\n\
    another task      : 24d\n\
```\n",
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
