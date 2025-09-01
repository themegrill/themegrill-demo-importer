import { __ } from '@wordpress/i18n';
import { useMemo } from 'react';
import { DemoType } from '../../../../../../lib/types';
import { Route } from '../../../../../../routes';
import Demo from '../demos/Demo';

type ContentProps = {
	demos: DemoType[];
};

const Content = ({ demos }: ContentProps) => {
	const searchParams = Route.useSearch();
	const search = searchParams.search || '';
	const builder = searchParams.builder || '';
	const selectedCategories = searchParams.category?.split(',').filter(Boolean) || [];
	const newDemos = useMemo(() => {
		return demos
			.filter((d) => {
				if (!builder) {
					return true;
				}
				return d.pagebuilder.toLowerCase() === builder;
			})
			.filter((d) => {
				if (selectedCategories.length === 0) {
					return true;
				}
				const normalizedCategories = d.categories.map((cat) =>
					cat.toLowerCase().replace(/\s+/g, '-'),
				);
				return selectedCategories.some((cat) => normalizedCategories.includes(cat));
			})
			.filter((d) => (search ? d.title.toLowerCase().indexOf(search.toLowerCase()) !== -1 : true));
	}, [selectedCategories, builder, search, demos]);

	return (
		<>
			{newDemos.length === 0 ? (
				<div className="flex-1 flex items-center justify-center bg-[#fff]">
					<div className="text-center">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							fill="none"
							viewBox="0 0 60 60"
							width={60}
							height={60}
						>
							<g fill="#D3D3D3" clip-path="url(#a)">
								<path d="M58.267 58.284a3.747 3.747 0 0 1-5.297 0L40.951 46.262l5.297-5.297 12.019 12.037a3.747 3.747 0 0 1 0 5.282Z" />
								<path d="M7.608 7.627a26.009 26.009 0 1 0 36.8 36.763 26.009 26.009 0 0 0-36.8-36.763Zm35.299 35.298A23.895 23.895 0 1 1 9.114 9.132a23.895 23.895 0 0 1 33.793 33.793Z" />
							</g>
							<defs>
								<clipPath id="a">
									<path fill="#fff" d="M0 0h60v60H0z" />
								</clipPath>
							</defs>
						</svg>
						<h3 className="mb-[7px] mt-[16px] text-[#383838] text-[20px] leading-[29px]">
							{__('Sorry, no result found', 'themegrill-demo-importer')}
						</h3>
						<p className="m-0 text-[14px] leading-[29px] text-[#7A7A7A]">
							{__('Please try another search', 'themegrill-demo-importer')}
						</p>
					</div>
				</div>
			) : (
				// <div className="flex-1 p-20 sm:p-20 lg:p-[48px] grid [grid-template-columns:repeat(auto-fill,minmax(345px,1fr))] gap-10 overflow-y-auto bg-[#fff]">
				<div className="flex-1 p-14 sm:p-14 2xl:p-[88px] grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-10 overflow-y-auto bg-[#fff] content-wrapper">
					{newDemos.map((demo, index) => (
						<Demo key={`${demo.slug}-${index}`} demo={demo} />
					))}
				</div>
			)}
		</>
	);
};

export default Content;
