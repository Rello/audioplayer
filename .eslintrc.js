module.exports = {
    'env': {
        'browser': true,
        'es6': true,
        'jquery': true
    },
    'extends': 'eslint:recommended',
    'globals': {
        'OC': 'readonly',
        'OCA': 'readonly',
        't': 'readonly'
    },
    'parserOptions': {
        'ecmaVersion': 6,
        'sourceType': 'module'
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
