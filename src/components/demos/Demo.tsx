import { __ } from '@wordpress/i18n';
import React from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { DemoType } from '../../lib/types';

declare const require: any;

type DemoProps = {
	demo: DemoType;
};

const Demo = ({ demo }: DemoProps) => {
	const [searchParams] = useSearchParams();
	const pagebuilder = searchParams.get('pagebuilder') || 'all';
	const isPremium = demo?.categories?.find((c) => c === 'premium');
	const isNew = Date.now() - new Date('2025-07-17 03:14:59').getTime() < 7 * 24 * 60 * 60 * 1000;

	return (
		<Link
			to={`/import-detail/${demo.theme_slug}/${pagebuilder}/${demo.slug}`}
			className="text-[#383838] rounded-md no-underline hover:text-[#383838] tg-demo flex flex-col gap-0 flex-shrink-0 self-start"
		>
			<div className="border-2 rounded-md border-solid cursor-pointer border-[#EDEDED] hover:border-[#5182EF]">
				<div className="relative" style={{ aspectRatio: '.84 / 1' }}>
					{demo.previewImage ? (
						// <img
						// 	src={require(`../../assets/images/test.jpg`)}
						// 	alt=""
						// 	className="w-full h-[503px] rounded-t-md"
						// />
						<img src={demo.previewImage} alt="" className="w-full h-full rounded-t-md" />
					) : (
						<img
							src={require(`../../assets/images/demo-skeleton.jpg`)}
							className="w-full h-full rounded-[2px]"
						/>
					)}
					{isPremium && (
						<div className="tg-demo-pro text-white">
							<p className="m-0 font-medium">{__('Premium', 'themegrill-demo-importer')}</p>
						</div>
					)}
				</div>
				<div className="bg-white px-4 py-4 border-0 border-t border-solid border-[#EDEDED] rounded-b-md">
					<h4 className="flex items-center gap-2 m-0 text-[#383838] text-[16px]">
						{demo.title ||
							demo.slug.replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())}
						{isNew && (
							<span className="bg-[#27AE60] px-2 py-0.5 text-[10px] text-white rounded-[3px]">
								{__('New', 'themegrill-demo-importer')}
							</span>
						)}
					</h4>
				</div>
			</div>
		</Link>
	);
};

export default Demo;
