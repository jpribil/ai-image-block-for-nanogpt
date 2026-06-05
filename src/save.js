import { useBlockProps } from '@wordpress/block-editor';

// Static save: renders the generated image as a figure.
export default function save( { attributes } ) {
	const { url, alt } = attributes;

	if ( ! url ) {
		return null;
	}

	const blockProps = useBlockProps.save( { className: 'nanogpt-ai-image' } );

	return (
		<figure { ...blockProps }>
			<img src={ url } alt={ alt || '' } />
		</figure>
	);
}
