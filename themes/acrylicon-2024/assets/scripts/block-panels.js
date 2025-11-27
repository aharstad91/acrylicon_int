wp.domReady(function() {
	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { Fragment } = wp.element;
	const { InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl } = wp.components;
	const el = wp.element.createElement;

	// Add custom attributes to blocks
	addFilter(
		'blocks.registerBlockType',
		'my-plugin/custom-attributes',
		function(settings, name) {
			if (name === 'core/heading' || name === 'core/paragraph') {
				settings.attributes = Object.assign({}, settings.attributes, {
					fontSize: {
						type: 'string',
						default: ''
					},
					marginTop: {
						type: 'string',
						default: ''
					},
					fontFamily: {          // Added new attribute
						type: 'string',
						default: ''
					}
				});
			}
			if (name === 'core/columns') {
				settings.attributes = Object.assign({}, settings.attributes, {
					marginTop: {
						type: 'string',
						default: ''
					},
					gap: {
						type: 'string',
						default: ''
					}
				});
			}
			if (name === 'core/post-featured-image') {
				settings.attributes = Object.assign({}, settings.attributes, {
					rounded: {
						type: 'boolean',
						default: false
					}
				});
			}
			return settings;
		}
	);

	// Create inspector controls
	const withInspectorControls = createHigherOrderComponent(
		function(BlockEdit) {
			return function(props) {
				if (!['core/heading', 'core/paragraph', 'core/columns', 'core/post-featured-image'].includes(props.name)) {
					return wp.element.createElement(BlockEdit, props);
				}

				function updateClassNames(newAttributes) {
					let className = props.attributes.className || '';
					
					if (props.name === 'core/heading' || props.name === 'core/paragraph') {
						// Handle font size
						if (newAttributes.fontSize !== undefined) {
							className = className
								.split(' ')
								.filter(cls => {
									return !(
										cls.includes('text-') || 
										cls.includes('title-') ||
										cls.startsWith('lg-') || 
										cls.startsWith('md-')
									);
								})
								.join(' ');
					
							if (newAttributes.fontSize) {
								className += ' ' + newAttributes.fontSize.split(',').map(cls => cls.trim()).join(' ');
							}
						}

						// Handle font family
						if (newAttributes.fontFamily !== undefined) {
							className = className
								.split(' ')
								.filter(cls => !cls.startsWith('font-sohne-'))
								.join(' ');
							
							if (newAttributes.fontFamily) {
								className += ` ${newAttributes.fontFamily}`;
							}
						}
					}
					
					// Handle margin top for heading, paragraph, and columns
					if (['core/heading', 'core/paragraph', 'core/columns'].includes(props.name) && newAttributes.marginTop !== undefined) {
						className = className.replace(/\s*margin-top-(none|small|medium|large)/g, '');
						if (newAttributes.marginTop) {
							className += ` margin-top-${newAttributes.marginTop}`;
						}
					}
				
					// Handle column gap
					if (props.name === 'core/columns' && newAttributes.gap !== undefined) {
						className = className.replace(/\s*(medium|large)-gap/g, '');
						if (newAttributes.gap) {
							className += ` ${newAttributes.gap}-gap`;
						}
					}
				
					// Handle featured image rounded corners
					if (props.name === 'core/post-featured-image' && newAttributes.rounded !== undefined) {
						className = className.replace(/\s*rounded/g, '');
						if (newAttributes.rounded) {
							className += ' rounded';
						}
					}
				
					props.setAttributes({
						...newAttributes,
						className: className.trim()
					});
				}

				let inspectorControls = [];

				// Typography panel for headings and paragraphs
				if (props.name === 'core/heading' || props.name === 'core/paragraph') {
					inspectorControls.push(
						wp.element.createElement(
							PanelBody,
							{
								title: 'Typography',
								initialOpen: true
							},
							[
								// Font Family Control
								wp.element.createElement(SelectControl, {
									label: 'Font Family',
									value: props.attributes.fontFamily || '',
									options: [
										{ label: 'Default', value: '' },
										{ label: 'Sohne Buch', value: 'font-sohne-buch' },
										{ label: 'Sohne Kursiv', value: 'font-sohne-kursiv' },
										{ label: 'Sohne Mono', value: 'font-sohne-mono' }
									],
									onChange: function(value) {
										updateClassNames({ fontFamily: value });
									}
								}),
								// Font Size Control
								wp.element.createElement(SelectControl, {
									label: 'Font Size',
									value: props.attributes.fontSize || '',
									options: props.name === 'core/heading' ? [
										// Headings
										{ label: 'Default', value: '' },
										{ label: 'Font: 16px', value: 'text-base' },
										{ label: 'Font: 18px', value: 'text-lg' },
										{ label: 'Font: 20px', value: 'lg-text-xl,md-text-lg,text-base' },
										{ label: 'Font: 24px', value: 'lg-text-2xl,md-text-xl,text-lg' },
										{ label: 'Font: 32px', value: 'lg-text-3xl,md-text-2xl,text-xl' },
										{ label: 'Font: 36px', value: 'lg-text-4xl,md-text-3xl,text-2xl' },
										{ label: 'Font: 48px', value: 'lg-text-5xl,md-text-3xl,text-2xl' },
										{ label: 'Font: 72px', value: 'lg-text-7xl,md-text-4xl,text-4xl' },
										{ label: 'Font: 80px', value: 'lg-text-8xl,md-text-5xl,text-4xl' }
									] : [
										// Paragraph
										{ label: 'Default', value: '' },
										{ label: 'Font: 16px', value: 'text-base' },
										{ label: 'Font: 18px', value: 'text-lg' },
										{ label: 'Font: 20px', value: 'lg-text-xl,md-text-lg,text-base' },
										{ label: 'Font: 24px', value: 'lg-text-2xl,md-text-xl,text-lg' },
										{ label: 'Font: 32px', value: 'lg-text-3xl,md-text-2xl,text-xl' }
									],
									onChange: function(value) {
										updateClassNames({ fontSize: value });
									}
								})
							]
						)
					);
				}

				// Spacing panel for headings, paragraphs, and columns
				if (['core/heading', 'core/paragraph', 'core/columns'].includes(props.name)) {
					inspectorControls.push(
						wp.element.createElement(
							PanelBody,
							{
								title: 'Spacing',
								initialOpen: true
							},
							wp.element.createElement(SelectControl, {
								label: 'Top Margin',
								value: props.attributes.marginTop || '',
								options: [
									{ label: 'Default', value: 'default' },
									{ label: 'None', value: 'none' },
									{ label: 'Small', value: 'small' },
									{ label: 'Medium', value: 'medium' },
									{ label: 'Large', value: 'large' }
								],
								onChange: function(value) {
									updateClassNames({ marginTop: value });
								}
							})
						)
					);
				}
				
				// Spacing panel for headings, paragraphs, and columns
				if (['core/heading', 'core/paragraph', 'core/columns'].includes(props.name)) {
					inspectorControls.push(
						wp.element.createElement(
							PanelBody,
							{
								title: 'Spacing',
								initialOpen: true
							},
							wp.element.createElement(SelectControl, {
								label: 'Bottom Margin',
								value: props.attributes.marginBottom || '',
								options: [
									{ label: 'Default', value: 'default' },
									{ label: 'None', value: 'none' },
									{ label: 'Small', value: 'small' },
									{ label: 'Medium', value: 'medium' },
									{ label: 'Large', value: 'large' }
								],
								onChange: function(value) {
									updateClassNames({ marginBottom: value });
								}
							})
						)
					);
				}

				// Gap panel for columns
				if (props.name === 'core/columns') {
					inspectorControls.push(
						wp.element.createElement(
							PanelBody,
							{
								title: 'Gap',
								initialOpen: true
							},
							wp.element.createElement(SelectControl, {
								label: 'Column Gap',
								value: props.attributes.gap || '',
								options: [
									{ label: 'Default', value: '' },
									{ label: 'X-small', value: 'x-small' },
									{ label: 'Small', value: 'small' },
									{ label: 'Medium', value: 'medium' },
									{ label: 'Large', value: 'large' }
								],
								onChange: function(value) {
									updateClassNames({ gap: value });
								}
							})
						)
					);
				}

				// Shape panel for featured image
				if (props.name === 'core/post-featured-image') {
					inspectorControls.push(
						wp.element.createElement(
							PanelBody,
							{
								title: 'Shape',
								initialOpen: true
							},
							wp.element.createElement(SelectControl, {
								label: 'Corner Style',
								value: props.attributes.rounded ? 'rounded' : '',
								options: [
									{ label: 'Default', value: '' },
									{ label: 'Rounded', value: 'rounded' }
								],
								onChange: function(value) {
									updateClassNames({ rounded: value === 'rounded' });
								}
							})
						)
					);
				}

				return wp.element.createElement(
					Fragment,
					null,
					wp.element.createElement(BlockEdit, props),
					wp.element.createElement(
						InspectorControls,
						null,
						...inspectorControls
					)
				);
			};
		},
		'withInspectorControls'
	);

	addFilter(
		'editor.BlockEdit',
		'my-plugin/with-inspector-controls',
		withInspectorControls
	);
});