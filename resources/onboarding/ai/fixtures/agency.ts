import { image, NicheFixture, thumbnail } from './helpers';

export const agency: NicheFixture = {
	niche: 'agency',
	keywords: ['agency', 'marketing', 'design', 'studio', 'consult', 'business', 'startup', 'software', 'digital', 'branding'],
	demo: { slug: 'zakra-agency', name: 'Agency', thumbnail: thumbnail('zakra-agency') },
	alternatives: [
		{ slug: 'zakra-business', name: 'Business', thumbnail: thumbnail('zakra-business') },
		{ slug: 'zakra-startup', name: 'Startup', thumbnail: thumbnail('zakra-startup') },
		{ slug: 'zakra-consulting', name: 'Consulting', thumbnail: thumbnail('zakra-consulting') },
	],
	brand: {
		tagline: 'Ideas that move brands forward',
		palette: {
			primary: '#4F46E5',
			secondary: '#A5B4FC',
			accent: '#F59E0B',
			text: '#111827',
			background: '#FFFFFF',
		},
		fonts: { heading: 'Manrope', body: 'Inter' },
	},
	pages: {
		home: {
			title: 'Home',
			sections: [
				{
					id: 'hero',
					type: 'hero',
					slots: {
						hero_title: 'We Build Brands People Remember',
						hero_subtitle:
							'{brand} is a strategy and design studio helping ambitious companies launch, grow and stand out online.',
						hero_cta: 'Start a Project',
						hero_image: image('agency-hero', 'Team collaborating around a laptop'),
					},
				},
				{
					id: 'features',
					type: 'features',
					slots: {
						features_heading: 'How We Help',
						features_item_1_title: 'Brand Strategy',
						features_item_1_text: 'Positioning and messaging that sets you apart.',
						features_item_2_title: 'Web Design',
						features_item_2_text: 'Fast, beautiful websites built to convert.',
						features_item_3_title: 'Growth Marketing',
						features_item_3_text: 'Campaigns measured by results, not vanity metrics.',
					},
				},
				{
					id: 'about',
					type: 'content-image',
					slots: {
						about_heading: 'Small Team, Big Results',
						about_text:
							'We are a senior team of strategists, designers and developers. No hand-offs to juniors, just people who care about your outcome.',
						about_cta: 'Meet the Team',
						about_image: image('agency-about', 'Designers reviewing work on a wall'),
					},
				},
				{
					id: 'testimonials',
					type: 'testimonials',
					slots: {
						testimonials_heading: 'Trusted by Growing Companies',
						testimonials_1_quote: 'Our signups doubled within two months of the relaunch.',
						testimonials_1_author: 'Priya S., Founder at Lumen',
						testimonials_2_quote: 'They understood our product better than we did.',
						testimonials_2_author: 'Daniel K., CMO at Northwind',
					},
				},
				{
					id: 'cta',
					type: 'cta',
					slots: {
						cta_heading: 'Have a Project in Mind?',
						cta_text: 'Book a free 30-minute call and we will map out the next steps.',
						cta_button: 'Book a Call',
					},
				},
			],
		},
		about: {
			title: 'About',
			sections: [
				{
					id: 'hero',
					type: 'page-header',
					slots: {
						hero_title: 'About {brand}',
						hero_subtitle: 'Independent, senior and obsessed with craft.',
						hero_image: image('agency-office', 'Bright modern studio office'),
					},
				},
				{
					id: 'values',
					type: 'features',
					slots: {
						values_heading: 'How We Work',
						values_item_1_title: 'Clarity First',
						values_item_1_text: 'Every project starts with a clear, shared goal.',
						values_item_2_title: 'Ship Often',
						values_item_2_text: 'Small releases, fast feedback, steady progress.',
						values_item_3_title: 'Own the Outcome',
						values_item_3_text: 'We measure success by your results.',
					},
				},
			],
		},
		services: {
			title: 'Services',
			sections: [
				{
					id: 'hero',
					type: 'page-header',
					slots: {
						hero_title: 'Our Services',
						hero_subtitle: 'Everything you need to launch and grow.',
					},
				},
				{
					id: 'services',
					type: 'services',
					slots: {
						services_item_1_title: 'Brand Identity',
						services_item_1_text: 'Logos, visual systems and brand guidelines.',
						services_item_1_image: image('agency-brand', 'Brand identity sketches'),
						services_item_2_title: 'Websites',
						services_item_2_text: 'Design and development on WordPress.',
						services_item_2_image: image('agency-web', 'Website mockups on screens'),
						services_item_3_title: 'Marketing',
						services_item_3_text: 'SEO, paid ads and content that converts.',
						services_item_3_image: image('agency-marketing', 'Analytics dashboard'),
					},
				},
			],
		},
		contact: {
			title: 'Contact',
			sections: [
				{
					id: 'hero',
					type: 'page-header',
					slots: {
						hero_title: 'Let’s Talk',
						hero_subtitle: 'Tell us about your project and we will reply within one business day.',
					},
				},
				{
					id: 'contact_info',
					type: 'contact',
					slots: {
						contact_info_address: '120 Market Street, Suite 4',
						contact_info_phone: '+1 555 010 2030',
						contact_info_email: 'hello@example.com',
						contact_info_hours: 'Mon – Fri, 9am – 6pm',
					},
				},
			],
		},
		blog: {
			title: 'Insights',
			sections: [
				{
					id: 'hero',
					type: 'page-header',
					slots: {
						hero_title: 'Insights',
						hero_subtitle: 'Notes on brand, design and growth from the {brand} team.',
					},
				},
			],
		},
	},
};
