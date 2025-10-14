import { useSiteDetailStore } from "@/starter-templates/stores/site-detail.store";
import { PropsWithChildren } from "react";
import { FormProvider, useForm } from "react-hook-form";

export type SiteDetailForm = {
	logo?: number;
	palette?: {
		name: string;
		colors: Record<string, string>;
	};
	typography?: {
		body: string;
		headings: string;
	};
	plugins?: string[];
};
export const SiteDetailForm = (props: PropsWithChildren) => {
	const site = useSiteDetailStore.getState().site!;
	const form = useForm<SiteDetailForm>({
		defaultValues: {
			plugins: Object.entries(site.plugins)
				.filter((x) => x[1].name.trim() !== "")
				.map((x) => x[0]),
		},
	});
	return <FormProvider {...form}>{props.children}</FormProvider>;
};
