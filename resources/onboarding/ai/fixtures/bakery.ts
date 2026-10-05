import { image, NicheFixture, thumbnail } from './helpers';

export const bakery: NicheFixture = {
	niche: 'food',
	keywords: ['bakery', 'bread', 'cake', 'cafe', 'coffee', 'restaurant', 'food', 'pastry', 'kitchen'],
	demo: { slug: 'zakra-bakery', name: 'Bakery', thumbnail: thumbnail('zakra-bakery') },
	alternatives: [
		{ slug: 'zakra-cafe', name: 'Cafe', thumbnail: thumbnail('zakra-cafe') },
		{ slug: 'zakra-restaurant', name: 'Restaurant', thumbnail: thumbnail('zakra-restaurant') },
		{ slug: 'zakra-food-blog', name: 'Food Blog', thumbnail: thumbnail('zakra-food-blog') },
	],
	brand: {
		tagline: 'Baked fresh every morning',
		palette: {
			primary: '#C2410C',
			secondary: '#FDBA74',
			accent: '#1E293B',
			text: '#1F2937',
			background: '#FFFBF5',
		},
		fonts: { heading: 'Playfair Display', body: 'Inter' },
	},
	pages: {
		home: {
			title: 'Home',
			sections: [
				{
					id: 'hero',
					type: 'hero',
					slots: {
						hero_title: 'Fresh Bread, Baked With Love',
						hero_subtitle:
							'{brand} is your neighbourhood family bakery for daily loaves, flaky pastries and custom celebration cakes.',
						hero_cta: 'Order Now',
						hero_image: image('bakery-hero', 'Fresh bread on a wooden table'),
					},
				},
				{
					id: 'features',
					type: 'features',
					slots: {
						features_heading: 'Why Our Neighbours Love Us',
						features_item_1_title: 'Baked at Dawn',
						features_item_1_text: 'Every loaf comes out of the oven before you wake up.',
						features_item_2_title: 'Real Ingredients',
						features_item_2_text: 'Local flour, real butter and no shortcuts.',
						features_item_3_title: 'Custom Cakes',
						features_item_3_text: 'Birthdays, weddings and everything in between.',
					},
				},
				{
					id: 'about',
					type: 'content-image',
					slots: {
						about_heading: 'A Family Recipe Since Day One',
						about_text:
							'What started in our grandmother’s kitchen is now a bakery the whole street visits. We still knead by hand and still taste every batch.',
						about_cta: 'Our Story',
						about_image: image('bakery-about', 'Baker kneading dough by hand'),
					},
				},
				{
					id: 'testimonials',
					type: 'testimonials',
					slots: {
						testimonials_heading: 'Kind Words From Our Regulars',
						testimonials_1_quote: 'The sourdough is the best I have had anywhere. I come in every Saturday.',
						testimonials_1_author: 'Anita R.',
						testimonials_2_quote: 'They made our wedding cake and every guest asked where it was from.',
						testimonials_2_author: 'Sujan & Mira',
					},
				},
				{
					id: 'cta',
					type: 'cta',
					slots: {
						cta_heading: 'Planning a Celebration?',
						cta_text: 'Tell us your idea and we will bake a cake to match.',
						cta_button: 'Request a Cake',
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
						hero_title: 'Our Story',
						hero_subtitle: 'Three generations, one oven and a lot of flour.',
						hero_image: image('bakery-story', 'Inside a warm family bakery'),
					},
				},
				{
					id: 'values',
					type: 'features',
					slots: {
						values_heading: 'What We Believe',
						values_item_1_title: 'Slow Baking',
						values_item_1_text: 'Long fermentation for better flavour and easier digestion.',
						values_item_2_title: 'Local First',
						values_item_2_text: 'We buy from farmers and mills close to home.',
						values_item_3_title: 'Zero Waste',
						values_item_3_text: 'Unsold bread goes to the community kitchen every evening.',
					},
				},
			],
		},
		services: {
			title: 'Menu',
			sections: [
				{
					id: 'hero',
					type: 'page-header',
					slots: {
						hero_title: 'What We Bake',
						hero_subtitle: 'Everyday favourites and made-to-order treats.',
					},
				},
				{
					id: 'services',
					type: 'services',
					slots: {
						services_item_1_title: 'Daily Breads',
						services_item_1_text: 'Sourdough, baguettes, multigrain and soft milk bread.',
						services_item_1_image: image('bakery-breads', 'Assorted loaves of bread'),
						services_item_2_title: 'Pastries',
						services_item_2_text: 'Croissants, danishes and seasonal tarts.',
						services_item_2_image: image('bakery-pastry', 'Croissants on a tray'),
						services_item_3_title: 'Custom Cakes',
						services_item_3_text: 'Designed with you for any occasion.',
						services_item_3_image: image('bakery-cake', 'Decorated celebration cake'),
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
						hero_title: 'Come Say Hello',
						hero_subtitle: 'Drop by for a warm loaf or call ahead to place an order.',
					},
				},
				{
					id: 'contact_info',
					type: 'contact',
					slots: {
						contact_info_address: 'Jhamsikhel Road, Lalitpur',
						contact_info_phone: '+977 1-5555555',
						contact_info_email: 'hello@example.com',
						contact_info_hours: 'Every day, 6:30am – 7pm',
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
						hero_title: 'From Our Kitchen',
						hero_subtitle: 'Recipes, baking tips and news from the {brand} team.',
					},
				},
			],
		},
	},
};
