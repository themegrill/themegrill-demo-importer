import { type SiteDetailForm } from "@/starter-templates/features/sites/components/detail/site-detail-form";
import { memo, useState } from "react";
import { Controller, useFormContext } from "react-hook-form";
import { MediaUpload } from "@wordpress/media-utils";
import { Button } from "@/starter-templates/components/ui/button";
import { __ } from "@wordpress/i18n";
import { PencilLine, Trash2 } from "lucide-react";
import { useShallow } from "zustand/shallow";
import { useSiteDetailStore } from "@/starter-templates/stores/site-detail.store";

export const Customize = memo(() => {
	const form = useFormContext<SiteDetailForm>();
	const [logoUrl, setLogoUrl] = useSiteDetailStore(
		useShallow((s) => [s.logoUrl, s.setLogoUrl])
	);
	return (
		<div className="">
			<Controller
				name="logo"
				control={form.control}
				render={({ field }) => (
					<div className="flex flex-col space-y-5">
						<label htmlFor="logo">Logo</label>
						<MediaUpload
							allowedTypes={["image"]}
							onSelect={(data: Record<string, unknown>) => {
								field.onChange(data.id as number);
								setLogoUrl(data.url as string);
							}}
							render={({ open }: { open: () => void }) => {
								if (field.value && logoUrl) {
									return (
										<div className="h-[62px] bg-white relative border-2 border-dashed border-[#D0D0D0] rounded-md flex flex-col items-center justify-center">
											<img
												src={logoUrl}
												className="max-w-full max-h-full border border-[#eee] rounded object-contain"
											/>
											<div className="flex items-center w-full absolute bottom-0 left-0 bg-white">
												<Button
													className="h-[32px] text-primary hover:bg-white hover:text-primary focus-visible:ring-0 flex-1 p-0 outline-none shadow-none [&_svg]:size-[12px] rounded-none border-0 border-t-[1px]"
													variant="outline"
													onClick={open}
												>
													<PencilLine />
													<span className="text-xs">
														{__(
															"Change",
															"themegrill-demo-importer"
														)}
													</span>
												</Button>
												<div className="w-[2px] h-[12px] bg-border"></div>
												<Button
													className="h-[32px] text-red-500 hover:bg-white hover:text-red-500 focus-visible:ring-0 flex-1 p-0 outline-none shadow-none [&_svg]:size-[12px] rounded-none border-0 border-t-[1px]"
													variant="outline"
													onClick={() => {
														field.onChange(
															undefined
														);
														setLogoUrl("");
													}}
												>
													<Trash2 />
													<span className="text-xs">
														{__(
															"Remove",
															"themegrill-demo-importer"
														)}
													</span>
												</Button>
											</div>
										</div>
									);
								}
								return (
									<Button
										onClick={open}
										data-active={!!field.value}
										className="h-[62px] hover:bg-inherit border-2 border-dashed border-[#D0D0D0] rounded-md"
										variant="ghost"
									>
										<span>
											{__(
												"Upload Logo Here",
												"themegrill-demo-importer"
											)}
										</span>
									</Button>
								);
							}}
						/>
					</div>
				)}
			/>
		</div>
	);
});

Customize.displayName = "Customize";
