import React from 'react';
import { Demo, PageWithSelection } from '../../../../../../lib/types';
import Page from './Page';

type Props = {
	pages: PageWithSelection[];
	setAllPages: React.Dispatch<React.SetStateAction<PageWithSelection[]>>;
	demo: Demo;
};

const Pages = ({ pages, setAllPages, demo }: Props) => {
	return (
		<div className="flex-1 p-20 sm:p-20 lg:p-[88px] grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-10 overflow-y-auto bg-[#fff] content-wrapper">
			{pages.map((page, index) => (
				<Page key={index} page={page} setAllPages={setAllPages} demo={demo} />
			))}
		</div>
	);
};

export default Pages;
