wp.domReady(function () {
	// Heading styles with combinations
	wp.blocks.registerBlockStyle('core/heading', [
		// Small font size combinations
		{
			name: 'small-no-margin',
			label: 'Small - No Margin'
		},
		{
			name: 'small-margin-small',
			label: 'Small - Small Margin'
		},
		{
			name: 'small-margin-medium',
			label: 'Small - Medium Margin'
		},
		{
			name: 'small-margin-large',
			label: 'Small - Large Margin'
		},
		// Medium font size combinations
		{
			name: 'medium-no-margin',
			label: 'Medium - No Margin'
		},
		{
			name: 'medium-margin-small',
			label: 'Medium - Small Margin'
		},
		{
			name: 'medium-margin-medium',
			label: 'Medium - Medium Margin'
		},
		{
			name: 'medium-margin-large',
			label: 'Medium - Large Margin'
		},
		// Large font size combinations
		{
			name: 'large-no-margin',
			label: 'Large - No Margin'
		},
		{
			name: 'large-margin-small',
			label: 'Large - Small Margin'
		},
		{
			name: 'large-margin-medium',
			label: 'Large - Medium Margin'
		},
		{
			name: 'large-margin-large',
			label: 'Large - Large Margin'
		},
		{
			name: 'default',
			label: 'Default',
			isDefault: true
		}
	]);
});