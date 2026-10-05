import { image, NicheFixture, thumbnail } from './helpers';

export const fitness: NicheFixture = {
	niche: 'fitness',
	keywords: ['gym', 'fitness', 'yoga', 'trainer', 'training', 'workout', 'health', 'crossfit', 'pilates', 'coach', 'wellness'],
	demo: { slug: 'zakra-fitness', name: 'Fitness', thumbnail: thumbnail('zakra-fitness') },
	alternatives: [
		{ slug: 'zakra-yoga', name: 'Yoga', thumbnail: thumbnail('zakra-yoga') },
		{ slug: 'zakra-gym', name: 'Gym', thumbnail: thumbnail('zakra-gym') },
		{ slug: 'zakra-spa', name: 'Spa & Wellness', thumbnail: thumbnail('zakra-spa') },
	],
	brand: {
		tagline: 'Stronger every single day',
		palette: {
			primary: '#DC2626',
			secondary: '#FCA5A5',
			accent: '#0F172A',
			text: '#18181B',
			background: '#FAFAFA',
		},
		fonts: { heading: 'Montserrat', body: 'Lato' },
	},
	pages: {
		home: {
			title: 'Home',
			sections: [
				{
					id: 'hero',
					type: 'hero',
					slots: {
						hero_title: 'Train Hard. Feel Unstoppable.',
						hero_subtitle:
							'{brand} offers coached classes, open gym and personal training for every level, from first squat to first marathon.',
						hero_cta: 'Book a Free Class',
						hero_image: image('fitness-hero', 'Athlete lifting weights in a gym'),
					},
				},
				{
					id: 'features',
					type: 'features',
					slots: {
						features_heading: 'Everything You Need to Progress',
						features_item_1_title: 'Expert Coaches',
						features_item_1_text: 'Certified trainers who know your name and your goals.',
						features_item_2_title: 'Small Classes',
						features_item_2_text: 'Never more than twelve people, so form always comes first.',
						features_item_3_title: 'Open 6am – 10pm',
						features_item_3_text: 'Train before work, after work or anytime in between.',
					},
				},
				{
					id: 'about',
					type: 'content-image',
					slots: {
						about_heading: 'A Gym That Feels Like a Team',
						about_text:
							'We built {brand} for people who want results without the intimidation. Show up, work hard and we will handle the plan.',
						about_cta: 'See the Space',
						about_image: image('fitness-about', 'Group fitness class in progress'),
					},
				},
				{
					id: 'testimonials',
					type: 'testimonials',
					slots: {
						testimonials_heading: 'Real Members, Real Results',
						testimonials_1_quote: 'I lost 12kg and finally enjoy working out.',
						testimonials_1_author: 'Rohan M.',
						testimonials_2_quote: 'The coaches pushed me to my first pull-up at 45.',
						testimonials_2_author: 'Linda T.',
					},
				},
				{
					id: 'cta',
					type: 'cta',
					slots: {
						cta_heading: 'Your First Class Is On Us',
						cta_text: 'Pick a time, bring water and we will take care of the rest.',
						cta_button: 'Claim Free Class',
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
						hero_title: 'Why {brand}',
						hero_subtitle: 'Built by coaches, for people who want to get better.',
						hero_image: image('fitness-coach', 'Coach guiding a member'),
					},
				},
				{
					id: 'values',
					type: 'features',
					slots: {
						values_heading: 'What Drives Us',
						values_item_1_title: 'Consistency',
						values_item_1_text: 'Small daily wins add up to big changes.',
						values_item_2_title: 'Community',
						values_item_2_text: 'Train together, celebrate together.',
						values_item_3_title: 'Safety',
						values_item_3_text: 'Good form before heavy weight, always.',
					},
				},
			],
		},
		services: {
			title: 'Programs',
			sections: [
				{
					id: 'hero',
					type: 'page-header',
					slots: {
						hero_title: 'Programs',
						hero_subtitle: 'Find the training style that fits your goals.',
					},
				},
				{
					id: 'services',
					type: 'services',
					slots: {
						services_item_1_title: 'Strength & Conditioning',
						services_item_1_text: 'Coached barbell and functional training.',
						services_item_1_image: image('fitness-strength', 'Barbell on a gym floor'),
						services_item_2_title: 'HIIT',
						services_item_2_text: 'Fast, intense sessions that fit a lunch break.',
						services_item_2_image: image('fitness-hiit', 'People doing a HIIT workout'),
						services_item_3_title: 'Personal Training',
						services_item_3_text: 'One-to-one plans built around you.',
						services_item_3_image: image('fitness-pt', 'Personal trainer with a client'),
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
						hero_title: 'Visit Us',
						hero_subtitle: 'Questions about memberships? We are happy to help.',
					},
				},
				{
					id: 'contact_info',
					type: 'contact',
					slots: {
						contact_info_address: '48 Riverside Avenue',
						contact_info_phone: '+44 20 5555 0101',
						contact_info_email: 'hello@example.com',
						contact_info_hours: 'Mon – Sun, 6am – 10pm',
					},
				},
			],
		},
		blog: {
			title: 'Blog',
			sections: [
				{
					id: 'hero',
					type: 'page-header',
					slots: {
						hero_title: 'Training Notes',
						hero_subtitle: 'Workouts, nutrition and recovery tips from the {brand} coaches.',
					},
				},
			],
		},
	},
};
