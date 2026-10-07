// Single source of truth for the AI flow: the describe form, the current
// screen and the (locally edited) generation package.
import React, { createContext, useContext, useMemo, useReducer } from 'react';
import { AiApiError } from '../api/errors';
import { getSiteLanguage, LANGUAGES } from '../constants';
import { BrandKit, GenerateRequest, GenerationPackage, Section, SlotValue } from '../types';

export type AiStage = 'describe' | 'generating' | 'preview' | 'confirm' | 'importing' | 'success';

export type AiFlowState = {
	stage: AiStage;
	form: GenerateRequest;
	pkg: GenerationPackage | null;
	error: AiApiError | null;
};

export type AiFlowAction =
	| { type: 'UPDATE_FORM'; patch: Partial<GenerateRequest> }
	| { type: 'SET_STAGE'; stage: AiStage }
	| { type: 'SET_PACKAGE'; pkg: GenerationPackage }
	| { type: 'SET_ERROR'; error: AiApiError | null }
	| { type: 'UPDATE_SLOT'; page: string; sectionId: string; slotId: string; value: SlotValue }
	| { type: 'REPLACE_SECTION'; page: string; section: Section }
	// Bring a removed group back (with freshly written sections) or leave it out again.
	| { type: 'SET_GROUP_RESTORED'; page: string; groupId: string; restored: boolean; sections?: Section[] }
	| { type: 'UPDATE_BRAND'; patch: Partial<Omit<BrandKit, 'palette' | 'fonts'>> & {
			palette?: Partial<BrandKit['palette']>;
			fonts?: Partial<BrandKit['fonts']>;
	  } }
	| { type: 'RESET' };

const siteLanguage = getSiteLanguage();

export const initialForm: GenerateRequest = {
	brandName: '',
	description: '',
	niche: 'auto',
	tone: 'friendly',
	language: LANGUAGES.some((l) => l.value === siteLanguage) ? siteLanguage : 'en',
	pages: ['home', 'about', 'services', 'contact'],
};

const initialState: AiFlowState = {
	stage: 'describe',
	form: initialForm,
	pkg: null,
	error: null,
};

// Edits in the preview change the package in place: one source of truth for import.
const updateSections = (state: AiFlowState, page: string, update: (section: Section) => Section): AiFlowState =>
	state.pkg
		? {
				...state,
				pkg: {
					...state.pkg,
					pages: state.pkg.pages.map((p) => (p.slug === page ? { ...p, sections: p.sections.map(update) } : p)),
				},
			}
		: state;

const reducer = (state: AiFlowState, action: AiFlowAction): AiFlowState => {
	switch (action.type) {
		case 'UPDATE_FORM':
			return { ...state, form: { ...state.form, ...action.patch } };
		case 'SET_STAGE':
			return { ...state, stage: action.stage };
		case 'SET_PACKAGE':
			return { ...state, pkg: action.pkg, error: null };
		case 'SET_ERROR':
			return { ...state, error: action.error };
		case 'UPDATE_SLOT':
			return updateSections(state, action.page, (section) =>
				section.id === action.sectionId
					? { ...section, slots: { ...section.slots, [action.slotId]: action.value } }
					: section,
			);
		case 'REPLACE_SECTION':
			return updateSections(state, action.page, (section) =>
				section.id === action.section.id ? action.section : section,
			);
		case 'SET_GROUP_RESTORED': {
			if (!state.pkg) return state;
			const pages = state.pkg.pages.map((p) => {
				const group = p.removed?.find((r) => r.groupId === action.groupId);
				if (p.slug !== action.page || !group) return p;
				const ids = new Set(group.sections.map((s) => s.id));
				// Leaving it out again keeps any edits for the next restore.
				const sections = action.restored
					? [...p.sections, ...(action.sections ?? group.sections)].sort((a, b) => (a.index ?? 0) - (b.index ?? 0))
					: p.sections.filter((s) => !ids.has(s.id));
				const groupSections = action.restored ? action.sections ?? group.sections : p.sections.filter((s) => ids.has(s.id));
				return {
					...p,
					sections,
					removed: p.removed!.map((r) =>
						r.groupId === action.groupId ? { ...r, restored: action.restored, sections: groupSections } : r,
					),
				};
			});
			return { ...state, pkg: { ...state.pkg, pages } };
		}
		case 'UPDATE_BRAND': {
			if (!state.pkg) return state;
			const { palette, fonts, ...rest } = action.patch;
			const brand = state.pkg.brand;
			return {
				...state,
				pkg: {
					...state.pkg,
					brand: {
						...brand,
						...rest,
						palette: { ...brand.palette, ...palette },
						fonts: { ...brand.fonts, ...fonts },
					},
				},
			};
		}
		case 'RESET':
			return initialState;
		default:
			return state;
	}
};

type AiFlowContextType = {
	state: AiFlowState;
	dispatch: React.Dispatch<AiFlowAction>;
};

const AiFlowContext = createContext<AiFlowContextType | null>(null);

export const AiFlowProvider = ({ children }: { children: React.ReactNode }) => {
	const [state, dispatch] = useReducer(reducer, initialState);
	const value = useMemo(() => ({ state, dispatch }), [state]);

	return <AiFlowContext.Provider value={value}>{children}</AiFlowContext.Provider>;
};

export const useAiFlow = () => {
	const context = useContext(AiFlowContext);
	if (!context) {
		throw new Error('useAiFlow must be used within an AiFlowProvider');
	}
	return context;
};
