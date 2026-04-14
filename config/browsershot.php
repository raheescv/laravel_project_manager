<?php

return [
    /*
     * Override binary paths via .env. If left empty, the controller will
     * auto-detect them with `which` at runtime.
     *
     * Mac (Homebrew Intel):   /usr/local/bin/node
     * Mac (Homebrew Silicon): /opt/homebrew/bin/node
     * Linux:                  /usr/bin/node  or  /usr/local/bin/node
     *
     * BROWSERSHOT_NODE_BINARY=
     * BROWSERSHOT_NPM_BINARY=
     * BROWSERSHOT_CHROME_PATH=
     */
    'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
    'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
];
