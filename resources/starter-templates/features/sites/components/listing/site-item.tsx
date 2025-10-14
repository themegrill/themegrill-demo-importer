import { Site } from "@/starter-templates/features/sites/api/sites.types";
import { Link } from "@tanstack/react-router";
import { __ } from "@wordpress/i18n";
import { memo } from "react";
import placeholder from "@/starter-templates/assets/images/placeholder.png";

export const SiteItem = memo(({ site }: { site: Site }) => {
	return (
		<div className="border-[2px] size-full relative border-solid border-[#EBEDEF] overflow-hidden rounded-md hover:border-primary transition-all hover:shadow-[0_4.089px_24.531px_0_rgba(0,0,0,0.10)]">
			<div className="w-full h-[calc(100%-60px)] aspect-[20/27]">
				<img
					className="w-full h-full object-cover"
					src={site.previewImage || placeholder}
				/>
			</div>
			<h2 className="h-[60px] flex items-center px-4 border-t border-solid border-[#EBEDEF]">
				{site.title}
			</h2>
			<Link
				className="absolute inset-0"
				to="/detail/$source/$id"
				params={{
					source: btoa(site.source),
					id: site.slug.replaceAll("/", ""),
				}}
			>
				<span className="sr-only">{site.title}</span>
			</Link>
			{site.categories.includes("premium") && (
				<span className="absolute top-3 right-3 rounded text-sm bg-[rgb(249,93,93)] text-white z-10 inline-block py-[5px] px-2">
					{__("Premium", "themegrill-demo-importer")}
				</span>
			)}
		</div>
	);
});

SiteItem.displayName = "SiteItem";
