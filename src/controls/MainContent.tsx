import React from 'react';
import { useLocation } from 'react-router-dom';
import { Tabs } from '../components/Tabs';
import Content from './content/Content';
import Header from './header/Header';

const MainContent = () => {
	const { pathname } = useLocation();
	const showTabs = pathname !== '/import-detail';
	return (
		<>
			{showTabs && (
				<Tabs defaultValue="all">
					<Header />
					<div className="bg-[#FAFAFC]">
						<Content />
					</div>
				</Tabs>
			)}
		</>
	);
};

export default MainContent;
