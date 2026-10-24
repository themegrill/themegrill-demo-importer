import { __ } from '@wordpress/i18n';
import React, { useId } from 'react';
import { cn } from '../../../lib/utils';
import { BrandKit, ImageSlot, isImageSlot, Section } from '../../types';

const ROLE_LABELS: Record<string, string> = {
	hero: __('Hero', 'themegrill-demo-importer'),
	features: __('Features', 'themegrill-demo-importer'),
	content: __('Content', 'themegrill-demo-importer'),
	testimonials: __('Testimonials', 'themegrill-demo-importer'),
	team: __('Team', 'themegrill-demo-importer'),
	faq: __('FAQ', 'themegrill-demo-importer'),
	gallery: __('Gallery', 'themegrill-demo-importer'),
	cta: __('Call to action', 'themegrill-demo-importer'),
	contact: __('Contact', 'themegrill-demo-importer'),
	pricing: __('Pricing', 'themegrill-demo-importer'),
	logos: __('Logos', 'themegrill-demo-importer'),
	stats: __('Stats', 'themegrill-demo-importer'),
	posts: __('Blog posts', 'themegrill-demo-importer'),
	dynamic: __('Dynamic content', 'themegrill-demo-importer'),
};

// Text styling per slot kind, so the card reads like the finished section.
const textStyle = (kind: string, brand: BrandKit): { className: string; style: React.CSSProperties } => {
	const heading = { fontFamily: `'${brand.fonts.heading}', sans-serif`, color: brand.palette.text };
	const body = { fontFamily: `'${brand.fonts.body}', sans-serif`, color: brand.palette.text };
	switch (kind) {
		case 'h1':
			return { className: 'text-[30px] leading-[1.2] font-bold', style: heading };
		case 'h2':
			return { className: 'text-[24px] leading-[1.25] font-bold', style: heading };
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
		case 'question':
		case 'name':
			return { className: 'text-[17px] leading-snug font-semibold', style: heading };
		case 'role':
		case 'caption':
		case 'overlay':
			return { className: 'text-[13px] leading-snug', style: { ...body, opacity: 0.75 } };
		default:
			return { className: 'text-[15px] leading-relaxed', style: body };
	}
};

const SectionPreview = ({ section, brand }: { section: Section; brand: BrandKit }) => {
	const uid = useId();
	const kinds = section.kinds ?? {};
	const entries = Object.entries(section.slots);
	const texts = entries.filter((e): e is [string, string] => typeof e[1] === 'string');
	const images = entries.filter((e): e is [string, ImageSlot] => isImageSlot(e[1]));
	const buttons = texts.filter(([id]) => kinds[id] === 'button');
	const copy = texts.filter(([id]) => kinds[id] !== 'button');

	return (
		<section aria-labelledby={`${uid}-title`} className="rounded-lg border border-solid border-[#E5E7EB] bg-white overflow-hidden">
			<h3
				id={`${uid}-title`}
				className="m-0 px-4 py-2 border-0 border-b border-solid border-[#F1F5F9] bg-[#FAFBFC] text-[12px] font-medium text-[#6B6B6B] uppercase tracking-wide"
			>
				{ROLE_LABELS[section.type] ?? section.type}
			</h3>

			<div
				className={cn('grid gap-6 p-6', images.length && copy.length ? 'md:grid-cols-[1fr_minmax(0,40%)]' : '')}
				style={{ background: brand.palette.background }}
			>
				{(copy.length > 0 || buttons.length > 0) && (
					<div className="flex flex-col gap-3 min-w-0">
						{!!section.fixed?.length && (
							<p className="m-0 text-[13px] font-semibold" style={{ color: brand.palette.primary }}>
								{section.fixed.join(' · ')}
							</p>
						)}
						{copy.map(([id, value]) => {
							const { className, style } = textStyle(kinds[id] ?? 'paragraph', brand);
							return kinds[id] === 'list-item' ? (
								<p key={id} className={cn('m-0 flex gap-2 items-start', className)} style={style}>
									<span aria-hidden="true" className="mt-[10px] w-[6px] h-[6px] rounded-full shrink-0" style={{ background: brand.palette.primary }} />
									{value}
								</p>
							) : (
								<p key={id} className={cn('m-0 whitespace-pre-line', className)} style={style}>
									{value}
								</p>
							);
						})}
						{buttons.length > 0 && (
							<div className="flex flex-wrap gap-3 mt-1">
								{buttons.map(([id, value]) => (
									<span
										key={id}
										className="inline-block rounded-full px-5 py-2 text-[14px] font-semibold text-white"
										style={{ background: brand.palette.primary, fontFamily: `'${brand.fonts.body}', sans-serif` }}
									>
										{value}
									</span>
								))}
							</div>
						)}
					</div>
				)}
				{images.length > 0 && (
					<div className={cn('grid gap-3', images.length > 1 ? 'grid-cols-2' : '')}>
						{images.map(([id, image]) => (
							<img
								key={id}
								src={image.url}
								alt={image.alt}
								loading="lazy"
								className="block w-full aspect-[4/3] object-cover rounded-md bg-[#F1F5F9]"
							/>
						))}
					</div>
				)}
			</div>
		</section>
	);
};

export default SectionPreview;
