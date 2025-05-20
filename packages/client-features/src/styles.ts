/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Get the style schema for a core block.
 * @param blockName - The name of the block to get the style schema for.
 * @return The style schema for the block.
 */
export const getStyleSchema = ( blockName: string ) => {
	const schemaDescription = blockName
		? sprintf(
				/* translators: %s is the name of the block. */
				__( 'Style object for the %s block.' ),
				blockName
		  )
		: __( 'Style object for the block.' );

	// Could this be parsed from https://github.com/WordPress/gutenberg/blob/trunk/schemas/json/theme.json?

	return {
		type: 'object',
		description: schemaDescription,
		properties: {
			typography: {
				type: 'object',
				description: __( 'Typography object for the block style.' ),
				properties: {
					fontSize: {
						type: 'string',
						description: __( 'Font size for the block style.' ),
					},
					lineHeight: {
						type: 'number',
						description: __( 'Line height for the block style.' ),
					},
					fontStyle: {
						type: 'string',
						description: __( 'Font style for the block style.' ),
						enum: [ 'normal', 'italic', 'bold', 'bold italic' ],
					},
					letterSpacing: {
						type: 'string',
						description: __(
							'Letter spacing for the block style.'
						),
					},
					fontWeight: {
						type: 'number',
						description: __( 'Font weight for the block style.' ),
						pattern: '^[1-9]00$',
					},
					textTransform: {
						type: 'string',
						description: __(
							'Text transform for the block style.'
						),
						enum: [
							'none',
							'uppercase',
							'lowercase',
							'capitalize',
						],
					},
					textDecoration: {
						type: 'string',
						description: __(
							'Text decoration for the block style.'
						),
						enum: [ 'none', 'underline', 'line-through' ],
					},
				},
			},
			border: {
				description: __( 'Border object for the block style.' ),
				type: 'object',
				properties: {
					color: {
						description: __(
							'Border color for the block style in rgb, rgba or hex format.'
						),
						type: 'string',
					},
					radius: {
						description: __(
							'Border radius for the block style with px unit.'
						),
						type: 'string',
						pattern: '^[0-9]+px$',
					},
					style: {
						description: __( 'Border style for the block style.' ),
						type: 'string',
						enum: [ 'solid', 'dashed', 'dotted', 'double' ],
					},
					width: {
						description: __(
							'Border width for the block style with px unit.'
						),
						type: 'string',
						pattern: '^[0-9]+px$',
					},
				},
				additionalProperties: false,
			},
			color: {
				type: 'object',
				description: __( 'Color object for the block style.' ),
				properties: {
					text: {
						type: 'string',
						description: __(
							'Text color for the block style in rgb, rgba or hex format.'
						),
					},
					background: {
						type: 'string',
						description: __(
							'Background color for the block style in rgb, rgba or hex format.'
						),
					},
				},
			},
			spacing: {
				type: 'object',
				description: __( 'Spacing object for the block style.' ),
				properties: {
					padding: {
						type: 'string',
						description: __(
							'Padding for the block style with px, rem, em or percentage unit.'
						),
					},
					margin: {
						type: 'string',
						description: __(
							'Margin for the block style with px, rem, em or percentage unit.'
						),
					},
				},
			},
		},
	};
};

/**
 
  Get a core block by name. Could rather check the block support for the block.
  https://github.com/WordPress/gutenberg/blob/8889f82eda340ea66c83e945098423ed1ae3f5d3/packages/block-editor/src/hooks/supports.js
  Or do it from the backend via registry.
 
 
  export const getBlockStyleSchema = (
  blockName: string
  ): Record< string, unknown > | null => {
  const foundBlock = getBlockTypes().find(
  ( block ) => block.name === blockName
  );
  console.log( 'foundBlock', blockName, foundBlock );
  if ( ! foundBlock ) {
  return {};
  }
  const validStyleGroups = {
  typography: 'typography',
  color: 'color',
  spacing: 'spacing',
  __experimentalBorder: 'border',
  };
 
  const validStyleSubkeys = {
  typography: {
  fontSize: 'fontSize',
  lineHeight: 'lineHeight',
  __experimentalFontStyle: 'fontStyle',
  __experimentalLetterSpacing: 'letterSpacing',
  __experimentalFontWeight: 'fontWeight',
  __experimentalTextTransform: 'textTransform',
  __experimentalTextDecoration: 'textDecoration',
  },
  border: {
  color: 'color',
  radius: 'radius',
  style: 'style',
  width: 'width',
  },
  color: {
  background: 'background',
  text: 'text',
  },
  spacing: {
  padding: 'padding',
  margin: 'margin',
  },
  };
 
  const styleSchema = {
  type: 'object',
  description: __( 'Style object for the paragraph.' ),
  properties: {},
  };
 
  // Temp ignore type errors
  // @ts-ignore
  // Check for valid style group keys.
  Object.keys( validStyleGroups ).forEach( ( key ) => {
  if (
  foundBlock.supports &&
  typeof foundBlock.supports === 'object' &&
  key in foundBlock.supports
  ) {
  if ( ! ( validStyleGroups[ key ] in styleSchema.properties ) ) {
  styleSchema.properties[ validStyleGroups[ key ] ] = {};
 
  // Check for valid style group subkeys and add the properties to the style schema.
  Object.keys( foundBlock.supports[ key ] ).forEach(
  ( subkey ) => {
  if ( 'color' === key ) {
  styleSchema.properties[ validStyleGroups[ key ] ] =
  VALID_STYLE_GROUPS[ validStyleGroups[ key ] ]
  ?.properties;
  }
 
  const validStyleSubkey = validStyleSubkeys[ key ]?.[ subkey ];
 
  if (
  !! validStyleSubkey &&
  !! VALID_STYLE_GROUPS[ validStyleGroups[ key ] ]
  ?.properties?.[ validStyleSubkey ]
  ) {
  styleSchema.properties[ validStyleGroups[ key ] ][
  validStyleSubkey
  ] =
  VALID_STYLE_GROUPS[ validStyleGroups[ key ] ]
  ?.properties?.[ validStyleSubkey ];
  }
  }
  );
  }
  }
  } );
  console.log( 'styleSchema', blockName, styleSchema );
  if ( Object.keys( styleSchema.properties ).length === 0 ) {
  return {};
  }
 
  return styleSchema;
  };
 
 
 
 */
