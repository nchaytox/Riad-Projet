import vue from 'eslint-plugin-vue';
import vueParser from 'vue-eslint-parser';
import globals from 'globals';

const ignores = [
    'node_modules/**',
    'public/**',
    'vendor/**',
    'bootstrap/**',
    'storage/**',
    'docs/**',
];

const sharedJsRules = {
    'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
};

export default [
    {
        files: ['resources/js/**/*.vue'],
        ignores,
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                parser: {
                    js: 'espree',
                },
                ecmaVersion: 2021,
                sourceType: 'module',
            },
            globals: {
                ...globals.browser,
            },
        },
        plugins: {
            vue,
        },
        rules: {
            ...sharedJsRules,
            'vue/html-indent': ['error', 4],
            'vue/max-attributes-per-line': ['error', {
                singleline: 3,
                multiline: {
                    max: 1,
                },
            }],
        },
    },
    {
        files: ['resources/js/**/*.js'],
        ignores,
        languageOptions: {
            ecmaVersion: 2021,
            sourceType: 'module',
            globals: {
                ...globals.browser,
            },
        },
        rules: sharedJsRules,
    },
];
