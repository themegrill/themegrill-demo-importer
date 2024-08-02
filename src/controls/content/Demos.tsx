import React from 'react';
import { useSearchParams } from 'react-router-dom';
import SingleDemo from './SingleDemo';

type Props = {
	currentHeaderTab: string;
};

const Demos = ({ currentHeaderTab }: Props) => {
	const [searchParams] = useSearchParams();
	const currentCategoryTab = searchParams.get('category') || 'all';
	return (
		<>
			<div className="tg-demos mt-0">
				<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-[40px]">
					<SingleDemo isNew={true} />
					<SingleDemo isPro={true} />
					<SingleDemo isPremium={true} />
					<SingleDemo />
					<SingleDemo />
				</div>
			</div>
		</>
	);
};

export default Demos;
