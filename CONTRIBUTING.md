# Contributing to Audio Player

## JS
In addition to the eslint rules any submitted code must be compatible with iOS 9.3.5 (Safari 9).
Said browser has full ES5 support and limited ES6 support. Most notably:
- ES6 classes are [allowed](https://caniuse.com/#feat=es6-class)
- Template literals are [allowed](https://caniuse.com/#feat=template-literals)
- `let` keyword is [disallowed](https://caniuse.com/#feat=let)
- `const` keyword is [disallowed](https://caniuse.com/#feat=const)
- ES6 modules (`import`, `export`) are [disallowed](https://caniuse.com/#feat=es6-module)
- arrow functions (`(args) => {body}`) are [disallowed](https://caniuse.com/#feat=arrow-functions)
- vanilla JS as far as possible; $ajax from jQuery still required due to AUTH-Header from NC