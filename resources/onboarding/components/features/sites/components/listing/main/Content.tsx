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
							width="60"
							height="60"
							viewBox="0 0 60 60"
							fill="none"
						>
							<g clip-path="url(#clip0_9785_6820)">
								<path
									d="M58.2669 58.2843C57.5643 58.9862 56.6117 59.3805 55.6185 59.3805C54.6252 59.3805 53.6727 58.9862 52.97 58.2843L40.9512 46.2618L46.2481 40.9648L58.2669 53.002C58.9644 53.704 59.3559 54.6535 59.3559 55.6431C59.3559 56.6327 58.9644 57.5822 58.2669 58.2843Z"
									fill="#D3D3D3"
								/>
								<path
									d="M7.60839 7.627C3.97368 11.2654 1.49924 15.8997 0.497831 20.9441C-0.503575 25.9885 0.0130159 31.2166 1.98231 35.9675C3.9516 40.7184 7.28518 44.7788 11.5617 47.6355C15.8382 50.4921 20.8656 52.0168 26.0084 52.0168C31.1513 52.0168 36.1787 50.4921 40.4552 47.6355C44.7317 44.7788 48.0653 40.7184 50.0346 35.9675C52.0038 31.2166 52.5204 25.9885 51.519 20.9441C50.5176 15.8997 48.0432 11.2654 44.4085 7.627C41.993 5.20911 39.1247 3.29099 35.9675 1.98229C32.8103 0.673599 29.4261 0 26.0084 0C22.5907 0 19.2066 0.673599 16.0494 1.98229C12.8922 3.29099 10.0238 5.20911 7.60839 7.627ZM42.9066 42.9252C39.5648 46.2669 35.3072 48.5426 30.6721 49.4646C26.0369 50.3865 21.2325 49.9133 16.8663 48.1047C12.5002 46.2962 8.76833 43.2335 6.14276 39.3041C3.51719 35.3746 2.1158 30.7548 2.1158 26.0289C2.1158 21.303 3.51719 16.6832 6.14276 12.7537C8.76833 8.82423 12.5002 5.76157 16.8663 3.95302C21.2325 2.14447 26.0369 1.67125 30.6721 2.5932C35.3072 3.51515 39.5648 5.79086 42.9066 9.13256C45.1255 11.3514 46.8856 13.9856 48.0864 16.8846C49.2873 19.7837 49.9053 22.8909 49.9053 26.0289C49.9053 29.1668 49.2873 32.274 48.0864 35.1731C46.8856 38.0722 45.1255 40.7064 42.9066 42.9252Z"
									fill="#D3D3D3"
								/>
							</g>
							<defs>
								<clipPath id="clip0_9785_6820">
									<rect width="60" height="60" fill="white" />
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
