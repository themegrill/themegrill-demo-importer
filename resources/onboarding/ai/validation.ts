import { __, sprintf } from '@wordpress/i18n';
import { BRAND_NAME_MAX, DESCRIPTION_MAX, DESCRIPTION_MIN } from './constants';
import { GenerateRequest } from './types';

export type DescribeErrors = Partial<Record<'brandName' | 'description' | 'pages', string>>;

export const validateDescribe = (form: GenerateRequest): DescribeErrors => {
	const errors: DescribeErrors = {};
	const brandName = form.brandName.trim();
	const description = form.description.trim();

	if (!brandName) {
		errors.brandName = __('Enter your brand or site name.', 'themegrill-demo-importer');
	} else if (brandName.length > BRAND_NAME_MAX) {
		errors.brandName = sprintf(
			/* translators: %d: maximum number of characters. */
			__('Keep the name under %d characters.', 'themegrill-demo-importer'),
			BRAND_NAME_MAX,
		);
	}

	if (!description) {
		errors.description = __('Tell us what your site is about.', 'themegrill-demo-importer');
	} else if (description.length < DESCRIPTION_MIN) {
		errors.description = sprintf(
			/* translators: %d: minimum number of characters. */
			__('Add a little more detail (at least %d characters).', 'themegrill-demo-importer'),
			DESCRIPTION_MIN,
		);
	} else if (description.length > DESCRIPTION_MAX) {
		errors.description = sprintf(
			/* translators: %d: maximum number of characters. */
			__('Keep the description under %d characters.', 'themegrill-demo-importer'),
			DESCRIPTION_MAX,
		);
	}

	if (!form.pages.includes('home')) {
		errors.pages = __('The Home page is required.', 'themegrill-demo-importer');
	}

	return errors;
};
