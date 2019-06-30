module.exports = {
    'env': {
        'browser': true,
        'es6': true,
        'jquery': true
    },
    'extends': 'eslint:recommended',
    'globals': {
        '_': 'readonly', // from js/dist/main.js
        'OC': 'readonly',
        'OCA': 'readonly',
        'OCdialogs': 'readonly', // from /js/core/merged-template-prepend.js
        't': 'readonly'
    },
    'parserOptions': {
        'ecmaFeatures': 'impliedStrict',
        'ecmaVersion': 6,
        'sourceType': 'script'
    },
    'rules': {
        'brace-style': [
            'error',
            '1tbs',
            { 'allowSingleLine': true }
        ],
        'curly': [
            'error',
            'all'
        ],
        'indent': [
            'error',
            4
        ],
        'linebreak-style': [
            'error',
            'unix'
        ],
        'no-console': [
            'error',
            { allow: [ 'warn', 'error' ] }
        ],
        'quotes': [
            'error',
            'single'
        ],
        'semi': [
            'error',
            'always'
        ],
    }
};
