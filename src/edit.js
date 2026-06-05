import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	PanelBody,
	Placeholder,
	TextareaControl,
	SelectControl,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';

import { priceText } from './price';

const ICON = 'format-image';

// The shared generation form, used both in the placeholder and the sidebar.
function GeneratorForm( {
	models,
	prompt,
	model,
	size,
	onChange,
	onGenerate,
	generating,
	error,
	submitLabel,
} ) {
	const selected = models.find( ( item ) => item.id === model ) || null;
	const sizes = selected ? selected.sizes : [];

	const modelOptions = [
		{
			label: __( 'Select a model…', 'ai-image-block-for-nanogpt' ),
			value: '',
		},
		...models.map( ( item ) => ( { label: item.name, value: item.id } ) ),
	];

	const sizeOptions = [
		{
			label: __( 'Provider default', 'ai-image-block-for-nanogpt' ),
			value: '',
		},
		...sizes.map( ( value ) => ( { label: value, value } ) ),
	];

	const price = selected ? priceText( selected, size ) : '';

	return (
		<>
			<TextareaControl
				label={ __( 'Prompt', 'ai-image-block-for-nanogpt' ) }
				value={ prompt }
				onChange={ ( value ) => onChange( 'prompt', value ) }
				placeholder={ __(
					'Describe the image you want to create.',
					'ai-image-block-for-nanogpt'
				) }
			/>
			<SelectControl
				label={ __( 'Model', 'ai-image-block-for-nanogpt' ) }
				value={ model }
				options={ modelOptions }
				onChange={ ( value ) => onChange( 'model', value ) }
			/>
			<SelectControl
				label={ __( 'Image size', 'ai-image-block-for-nanogpt' ) }
				value={ size }
				options={ sizeOptions }
				onChange={ ( value ) => onChange( 'size', value ) }
				disabled={ ! selected }
				help={ __(
					'Choose Provider default to let NanoGPT decide.',
					'ai-image-block-for-nanogpt'
				) }
			/>
			{ price && (
				<p className="nanogpt-ai-image__price">
					{ __( 'Estimated price:', 'ai-image-block-for-nanogpt' ) }{ ' ' }
					{ price }{ ' ' }
					{ __( 'per image', 'ai-image-block-for-nanogpt' ) }
				</p>
			) }
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<Button
				variant="primary"
				onClick={ onGenerate }
				disabled={ generating || ! prompt || ! model }
			>
				{ generating && <Spinner /> }
				{ submitLabel }
			</Button>
		</>
	);
}

// Block edit component.
export default function Edit( { attributes, setAttributes } ) {
	const { url, alt, prompt, model, size } = attributes;
	const blockProps = useBlockProps();

	const [ models, setModels ] = useState( [] );
	const [ generating, setGenerating ] = useState( false );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		let active = true;
		apiFetch( { path: '/nanogpt-ai-image/v1/models' } )
			.then( ( data ) => {
				if ( ! active || ! data ) {
					return;
				}
				const list = Array.isArray( data.models ) ? data.models : [];
				setModels( list );

				// Pre-fill empty fields from the provider's configured defaults.
				const defaults = data.defaults || {};
				const updates = {};
				if ( ! model && defaults.model ) {
					updates.model = defaults.model;
				}
				if ( ! size && defaults.size ) {
					updates.size = defaults.size;
				}
				if ( Object.keys( updates ).length ) {
					setAttributes( updates );
				}
			} )
			.catch( () => {
				if ( active ) {
					setError(
						__(
							'Could not load the model list.',
							'ai-image-block-for-nanogpt'
						)
					);
				}
			} );
		return () => {
			active = false;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const onChange = ( key, value ) => setAttributes( { [ key ]: value } );

	const onGenerate = () => {
		setGenerating( true );
		setError( '' );
		apiFetch( {
			path: '/nanogpt-ai-image/v1/generate',
			method: 'POST',
			data: { prompt, model, size },
		} )
			.then( ( res ) => {
				setAttributes( {
					attachmentId: res.attachmentId,
					url: res.url,
					alt: res.alt || prompt,
				} );
			} )
			.catch( ( err ) => {
				setError(
					( err && err.message ) ||
						__(
							'Image generation failed.',
							'ai-image-block-for-nanogpt'
						)
				);
			} )
			.finally( () => setGenerating( false ) );
	};

	if ( url ) {
		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody
						title={ __(
							'Generation settings',
							'ai-image-block-for-nanogpt'
						) }
						initialOpen={ true }
					>
						<GeneratorForm
							models={ models }
							prompt={ prompt }
							model={ model }
							size={ size }
							onChange={ onChange }
							onGenerate={ onGenerate }
							generating={ generating }
							error={ error }
							submitLabel={ __(
								'Regenerate',
								'ai-image-block-for-nanogpt'
							) }
						/>
					</PanelBody>
				</InspectorControls>
				<figure className="nanogpt-ai-image">
					<img src={ url } alt={ alt || '' } />
				</figure>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<Placeholder
				icon={ ICON }
				label={ __(
					'AI Image (NanoGPT)',
					'ai-image-block-for-nanogpt'
				) }
				instructions={ __(
					'Describe the image, pick a model and size, then generate.',
					'ai-image-block-for-nanogpt'
				) }
			>
				<div style={ { width: '100%' } }>
					<GeneratorForm
						models={ models }
						prompt={ prompt }
						model={ model }
						size={ size }
						onChange={ onChange }
						onGenerate={ onGenerate }
						generating={ generating }
						error={ error }
						submitLabel={ __(
							'Generate image',
							'ai-image-block-for-nanogpt'
						) }
					/>
				</div>
			</Placeholder>
		</div>
	);
}
