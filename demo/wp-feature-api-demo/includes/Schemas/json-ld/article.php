<?php

return [
	'type'       => 'object',
	'required'   => [ '@context', '@type', 'headline', 'author', 'publisher', 'datePublished' ],
	'properties' => [
		'@context'         => [
			'type'  => 'string',
			'const' => 'https://schema.org',
		],
		'@type'            => [
			'type'  => 'string',
			'const' => 'Article',
		],
		'headline'         => [
			'type'        => 'string',
			'maxLength'   => 110,
			'description' => 'Headline of the article (max 110 characters)',
		],
		'description'      => [
			'type'        => 'string',
			'description' => 'Description or summary of the article',
		],
		'image'            => [
			'oneOf'       => [
				[ 'type' => 'string', 'format' => 'uri' ],
				[ 'type' => 'array', 'items' => [ 'type' => 'string', 'format' => 'uri' ] ],
			],
			'description' => 'URL or array of URLs for images related to the article',
		],
		'author'           => [
			'type'       => 'object',
			'required'   => [ '@type', 'name' ],
			'properties' => [
				'@type' => [
					'type'  => 'string',
					'const' => 'Person',
				],
				'name'  => [
					'type'        => 'string',
					'description' => 'Name of the author',
				],
				'url'   => [
					'type'        => 'string',
					'format'      => 'uri',
					'description' => 'URL of the author\'s profile',
				],
			],
		],
		'publisher'        => [
			'type'       => 'object',
			'required'   => [ '@type', 'name', 'logo' ],
			'properties' => [
				'@type' => [
					'type'  => 'string',
					'const' => 'Organization',
				],
				'name'  => [
					'type'        => 'string',
					'description' => 'Name of the publisher',
				],
				'logo'  => [
					'type'       => 'object',
					'required'   => [ '@type', 'url' ],
					'properties' => [
						'@type' => [
							'type'  => 'string',
							'const' => 'ImageObject',
						],
						'url'   => [
							'type'        => 'string',
							'format'      => 'uri',
							'description' => 'URL of the publisher\'s logo',
						],
					],
				],
			],
		],
		'datePublished'    => [
			'type'        => 'string',
			'format'      => 'date-time',
			'description' => 'Date when the article was published (ISO 8601)',
		],
		'dateModified'     => [
			'type'        => 'string',
			'format'      => 'date-time',
			'description' => 'Date when the article was last modified (ISO 8601)',
		],
		'mainEntityOfPage' => [
			'type'        => 'string',
			'format'      => 'uri',
			'description' => 'URL of the main page where the article is published',
		],
	],
	'additionalProperties' => false,
];
