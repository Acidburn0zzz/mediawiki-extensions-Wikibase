import { getters } from '@/store/getters';

describe( 'getters', () => {
	describe( 'statementsTaintedState', () => {
		it( 'should return a function that returns the tainted state for a given guid', () => {
			const mockState = { statementsTaintedState: {
				foo: false,
				bar: true,
			}, statementsPopperIsOpen: {} };
			const getStatementsTaintedState = getters.statementsTaintedState( mockState, {}, mockState, {} );
			expect( getStatementsTaintedState ).toBeDefined();
			expect( getStatementsTaintedState( 'bar' ) ).toBeTruthy();
			expect( getStatementsTaintedState( 'foo' ) ).toBeFalsy();
		} );
	} );
} );
