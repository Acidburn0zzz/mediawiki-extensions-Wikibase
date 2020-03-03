import Application, { InitializedApplicationState } from '@/store/Application';
import ApplicationStatus from '@/definitions/ApplicationStatus';

type RecursivePartial<T> = {
	[P in keyof T]?: RecursivePartial<T[P]>;
};

export default function ( fields?: RecursivePartial<InitializedApplicationState> ): Application {
	let AppState: any = {
		targetProperty: '',
		targetLabel: null,
		editFlow: '',
		applicationStatus: ApplicationStatus.INITIALIZING,
		applicationErrors: [],
		wikibaseRepoConfiguration: null,
		editDecision: null,
		targetValue: null,
		entityTitle: '',
		originalHref: '',
		pageTitle: '',
	};

	if ( fields !== null ) {
		AppState = { ...AppState, ...fields };
	}

	return AppState;
}
