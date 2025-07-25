import Lottie from 'lottie-react';
import React, { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import loader from '../../assets/animation/loader.json';
import { PagebuilderCategory, ThemeItem } from '../../lib/types';
import CategoryMenu from './CategoryMenu';
import Demos from './Demos';

type Props = {
	categories: PagebuilderCategory[];
	demos: ThemeItem[];
};

const Content = ({ categories, demos }: Props) => {
	const [searchParams] = useSearchParams();
	const theme = searchParams.get('tab') || 'all';
	const pagebuilder = searchParams.get('pagebuilder') || 'all';
	const category = searchParams.get('category') || 'all';
	const plan = searchParams.get('plan') || 'all';
	const search = searchParams.get('search') || '';
	const [demoLoading, setDemoLoading] = useState(true);
	const newDemos = useMemo(() => {
		return demos
			.filter((d) => ('all' !== theme ? d.theme_slug == theme : true))
			.filter((d) =>
				'all' !== pagebuilder ? Object.keys(d.pagebuilders).some((p) => p === pagebuilder) : true,
			)
			.filter((d) =>
				'all' !== category ? Object.keys(d.categories).some((p) => p === category) : true,
			)
			.filter((d) =>
				'all' !== plan ? (plan === 'pro' ? d.pro || d.premium : !d.pro && !d.premium) : true,
			)
			.filter((d) => (search ? d.name.toLowerCase().indexOf(search.toLowerCase()) !== -1 : true));
	}, [theme, category, pagebuilder, plan, search, demos]);

	useEffect(() => {
		setDemoLoading(true);
	}, [theme, category, pagebuilder, plan, search]);

	useEffect(() => {
		if (newDemos) {
			const timer = setTimeout(() => {
				setDemoLoading(false);
			}, 1000);

			return () => clearTimeout(timer);
		}
	}, [newDemos]);

	return (
		<>
			{categories && (
				<>
					<CategoryMenu categories={categories} />
					{demoLoading ? (
						<Lottie animationData={loader} loop={true} autoplay={true} className="h-40" />
					) : (
						<Demos demos={newDemos} />
					)}
				</>
			)}
		</>
	);
};

export default Content;
