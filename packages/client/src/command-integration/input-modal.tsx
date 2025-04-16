/**
 * WordPress dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import {
	Modal,
	TextControl,
	Button,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';
import { URLInput } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { store } from '../store';

// Define the shape of a single property in the schema
interface PropertySchema {
	type: string;
	description?: string;
	ui_hint?: string;
	// Add other potential fields like 'enum', 'default', etc. if needed
}

interface InputModalProps {
	featureId: string; // Keeping featureId for now, might be useful for context/title
}

export default function InputModal( { featureId }: InputModalProps ) {
	const registry = useRegistry();
	const [ formData, setFormData ] = useState< Record< string, any > >( {} );
	const setFeatureInputInProgress =
		useDispatch( store ).setFeatureInputInProgress;
	// Ref for the container of inputs
	const inputContainerRef = useRef< HTMLDivElement >( null );

	const feature = useSelect(
		( select ) => {
			return select( store ).getRegisteredFeature( featureId );
		},
		[ featureId ]
	);

	const inputSchema = feature?.input_schema;

	// Initialize form data when schema changes or modal opens
	useEffect( () => {
		if ( inputSchema?.properties ) {
			const initialData = Object.keys( inputSchema.properties ).reduce(
				( acc, key ) => {
					// Set default values if needed, e.g., based on schema defaults
					acc[ key ] = '';
					return acc;
				},
				{} as Record< string, any >
			);
			setFormData( initialData );
		} else {
			setFormData( {} );
		}
	}, [ inputSchema ] );

	// Focus the first input when the modal content is ready
	useEffect( () => {
		if ( inputSchema && inputContainerRef.current ) {
			// Find the first input element within the container
			const firstInput =
				inputContainerRef.current.querySelector( 'input' );
			if ( firstInput ) {
				firstInput.focus();
			}
		}
		// Run this effect only when the inputSchema changes (i.e., when modal loads)
	}, [ inputSchema ] );

	const handleInputChange = ( key: string, value: any ) => {
		setFormData( ( prevData ) => ( {
			...prevData,
			[ key ]: value,
		} ) );
	};

	const handleSubmit = () => {
		feature?.callback?.( formData, {
			// @ts-ignore
			data: { dispatch: registry.dispatch, select: registry.select },
		} );
		setFeatureInputInProgress( null );
	};

	// Function to handle key down events on inputs
	const handleKeyDown = (
		event: React.KeyboardEvent< HTMLInputElement >
	) => {
		if ( event.key === 'Enter' ) {
			// Prevent default form submission if inside a form
			event.preventDefault();
			handleSubmit();
		}
	};

	const renderInput = ( key: string, propSchema: PropertySchema ) => {
		const { type, description } = propSchema;
		const uiHint = propSchema.ui_hint;
		// Type assertion needed because feature/inputSchema might be undefined initially
		const isRequired = ( feature?.input_schema?.required ?? [] ).includes(
			key
		);

		switch ( uiHint || type ) {
			case 'integer':
				return (
					<TextControl
						key={ key }
						label={ key }
						type="number"
						value={ formData[ key ] || '' }
						onChange={ ( value ) =>
							handleInputChange( key, parseInt( value, 10 ) || 0 )
						}
						onKeyDown={ handleKeyDown }
						help={ description }
						required={ isRequired }
						style={ { fontFamily: 'monospace' } } // Monospace font
					/>
				);
			case 'url':
				return (
					<div>
						<URLInput
							// @ts-ignore
							label={ key }
							value={ formData[ key ] || '' }
							onChange={ ( value ) =>
								handleInputChange( key, value )
							}
							placeholder={ description }
							onKeyDown={ handleKeyDown }
						/>
					</div>
				);
			case 'string':
			default:
				return (
					<TextControl
						key={ key }
						label={ key }
						value={ formData[ key ] || '' }
						onChange={ ( value ) =>
							handleInputChange( key, value )
						}
						onKeyDown={ handleKeyDown }
						help={ description }
						required={ isRequired }
						style={ { fontFamily: 'monospace' } } // Monospace font
					/>
				);
		}
	};

	return (
		<Modal
			title={ feature?.name }
			onRequestClose={ () => setFeatureInputInProgress( null ) }
		>
			<VStack ref={ inputContainerRef } spacing={ 4 }>
				{ inputSchema?.properties ? (
					Object.entries( inputSchema.properties ).map(
						( [ key, propSchema ] ) =>
							// Cast propSchema to PropertySchema here
							renderInput( key, propSchema as PropertySchema )
					)
				) : (
					<p>{ __( 'No properties defined for this feature.' ) }</p>
				) }
				<Button variant="primary" onClick={ handleSubmit }>
					{ __( 'Run' ) }
				</Button>
			</VStack>
		</Modal>
	);
}
