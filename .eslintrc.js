/**
 * External dependencies
 */
const glob = require( 'glob' ).sync;
const { join } = require( 'path' );

/**
 * The list of patterns matching files used only for development purposes.
 *
 * @type {string[]}
 */
const developmentFiles = [
	'**/benchmark/**/*.js',
	'**/@(__mocks__|__tests__|test)/**/*.[tj]s?(x)',
	'**/@(storybook|stories)/**/*.[tj]s?(x)',
	'packages/babel-preset-default/bin/**/*.js',
];

// All files from packages that have types provided with TypeScript.
const typedFiles = glob( 'packages/*/package.json' )
	.filter( ( fileName ) => require( join( __dirname, fileName ) ).types )
	.map( ( fileName ) => fileName.replace( 'package.json', '**/*.js' ) );

const restrictedImports = [
	{
		name: 'framer-motion',
		message:
			'Please use the Framer Motion API through `@wordpress/components` instead.',
	},
	{
		name: 'lodash',
		message: 'Please use native functionality instead.',
	},
	{
		name: '@ariakit/react',
		message:
			'Please use Ariakit API through `@wordpress/components` instead.',
	},
	{
		name: 'redux',
		importNames: [ 'combineReducers' ],
		message: 'Please use `combineReducers` from `@wordpress/data` instead.',
	},
	{
		name: '@emotion/css',
		message:
			'Please use `@emotion/react` and `@emotion/styled` in order to maintain iframe support. As a replacement for the `cx` function, please use the `useCx` hook defined in `@wordpress/components` instead.',
	},
	{
		name: '@wordpress/edit-post',
		message:
			"edit-post is a WordPress top level package that shouldn't be imported into other packages",
	},
	{
		name: '@wordpress/edit-site',
		message:
			"edit-site is a WordPress top level package that shouldn't be imported into other packages",
	},
	{
		name: '@wordpress/edit-widgets',
		message:
			"edit-widgets is a WordPress top level package that shouldn't be imported into other packages",
	},
	{
		name: 'classnames',
		message:
			"Please use `clsx` instead. It's a lighter and faster drop-in replacement for `classnames`.",
	},
];

const restrictedSyntax = [
	// NOTE: We can't include the forward slash in our regex or
	// we'll get a `SyntaxError` (Invalid regular expression: \ at end of pattern)
	// here. That's why we use \\u002F in the regexes below.
	{
		selector:
			'ImportDeclaration[source.value=/^@wordpress\\u002F.+\\u002F/]',
		message: 'Path access on WordPress dependencies is not allowed.',
	},
	{
		selector:
			'CallExpression[callee.object.name="page"][callee.property.name="waitFor"]',
		message:
			'This method is deprecated. You should use the more explicit API methods available.',
	},
	{
		selector:
			'CallExpression[callee.object.name="page"][callee.property.name="waitForTimeout"]',
		message: 'Prefer page.waitForSelector instead.',
	},
	{
		selector: 'JSXAttribute[name.name="id"][value.type="Literal"]',
		message:
			'Do not use string literals for IDs; use withInstanceId instead.',
	},
	{
		// Discourage the usage of `Math.random()` as it's a code smell
		// for UUID generation, for which we already have a higher-order
		// component: `withInstanceId`.
		selector:
			'CallExpression[callee.object.name="Math"][callee.property.name="random"]',
		message:
			'Do not use Math.random() to generate unique IDs; use withInstanceId instead. (If you’re not generating unique IDs: ignore this message.)',
	},
	{
		selector:
			'CallExpression[callee.name="withDispatch"] > :function > BlockStatement > :not(VariableDeclaration,ReturnStatement)',
		message:
			'withDispatch must return an object with consistent keys. Avoid performing logic in `mapDispatchToProps`.',
	},
	{
		selector:
			'LogicalExpression[operator="&&"][left.property.name="length"][right.type="JSXElement"]',
		message:
			'Avoid truthy checks on length property rendering, as zero length is rendered verbatim.',
	},
	{
		selector:
			'CallExpression[callee.name=/^(__|_x|_n|_nx)$/] > Literal[value=/toggle\\b/i]',
		message: "Avoid using the verb 'Toggle' in translatable strings",
	},
	{
		selector:
			'CallExpression[callee.name=/^(__|_x|_n|_nx)$/] > Literal[value=/(?<![-\\w])sidebar(?![-\\w])/i]',
		message:
			"Avoid using the word 'sidebar' in translatable strings. Consider using 'panel' instead.",
	},
];

/** `no-restricted-syntax` rules for components. */
const restrictedSyntaxComponents = [
	{
		selector:
			'JSXOpeningElement[name.name="Button"]:not(:has(JSXAttribute[name.name="accessibleWhenDisabled"])) JSXAttribute[name.name="disabled"]',
		message:
			'`disabled` used without the `accessibleWhenDisabled` prop. Disabling a control without maintaining focusability can cause accessibility issues, by hiding their presence from screen reader users, or preventing focus from returning to a trigger element. (Ignore this error if you truly mean to disable.)',
	},
];

module.exports = {
	root: true,
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'plugin:eslint-comments/recommended',
		'plugin:storybook/recommended',
	],
	plugins: [ 'react-compiler' ],
	globals: {
		wp: 'off',
		globalThis: 'readonly',
	},
	settings: {
		jsdoc: {
			mode: 'typescript',
		},
		'import/internal-regex': null,
		'import/resolver': require.resolve( './tools/eslint/import-resolver' ),
	},
	rules: {
		'jest/expect-expect': 'off',
		'react/jsx-boolean-value': 'error',
		'react/jsx-curly-brace-presence': [
			'error',
			{ props: 'never', children: 'never' },
		],
		'@wordpress/dependency-group': 'error',
		'@wordpress/wp-global-usage': 'error',
		'@wordpress/react-no-unsafe-timeout': 'error',
		'@wordpress/i18n-hyphenated-range': 'error',
		'@wordpress/i18n-no-flanking-whitespace': 'error',
		'@wordpress/i18n-text-domain': [
			'error',
			{
				allowedTextDomain: 'default',
			},
		],
		'@wordpress/no-unsafe-wp-apis': 'off',
		'import/default': 'error',
		'import/named': 'error',
		'no-restricted-imports': [
			'error',
			{
				paths: restrictedImports,
			},
		],
		'@typescript-eslint/no-restricted-imports': [
			'error',
			{
				paths: [
					{
						name: 'react',
						message:
							'Please use React API through `@wordpress/element` instead.',
						allowTypeImports: true,
					},
				],
			},
		],
		'@typescript-eslint/consistent-type-imports': [
			'error',
			{
				prefer: 'type-imports',
				disallowTypeAnnotations: false,
			},
		],
		'no-restricted-syntax': [ 'error', ...restrictedSyntax ],
		'jsdoc/check-tag-names': [
			'error',
			{
				definedTags: [ 'jest-environment' ],
			},
		],
		'react-compiler/react-compiler': [
			'error',
			{
				environment: {
					enableTreatRefLikeIdentifiersAsRefs: true,
					validateRefAccessDuringRender: false,
				},
			},
		],
		'no-bitwise': 'off',
	},
};
