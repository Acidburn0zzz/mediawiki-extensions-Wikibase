import Vue from 'vue';
import DataBridge from '@/presentation/components/DataBridge.vue';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import { createStore } from '@/store';
import Vuex, {
	Store,
} from 'vuex';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import ReferenceSection from '@/presentation/components/ReferenceSection.vue';
import Application from '@/store/Application';
import newMockServiceContainer from '../../services/newMockServiceContainer';

let store: Store<Application>;
const localVue = createLocalVue();

localVue.use( Vuex );

describe( 'DataBridge', () => {
	beforeEach( () => {
		store = createStore( newMockServiceContainer( {} ) );
		Vue.set( store, 'getters', {
			targetValue: { type: 'string', value: '' },
			targetLabel: { value: 'P123', language: 'zxx' },
			targetReferences: [],
		} );
	} );

	it( 'mounts StringDataValue', () => {
		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( StringDataValue ).exists() ).toBeTruthy();
	} );

	it( 'delegates the necessary props to StringDataValue', () => {
		const targetValue = { type: 'string', value: 'Töfften' };
		const targetLabel = { value: 'P123', language: 'zxx' };
		const stringMaxLength = 200;
		Vue.set( store.getters, 'targetValue', targetValue );
		Vue.set( store.getters, 'targetLabel', targetLabel );

		const wrapper = shallowMount( DataBridge, {
			store,
			mocks: {
				$bridgeConfig: { stringMaxLength },
			},
			localVue,
		} );

		expect( wrapper.find( StringDataValue ).props( 'dataValue' ) ).toBe( targetValue );
		expect( wrapper.find( StringDataValue ).props( 'label' ) ).toBe( targetLabel );
		expect( wrapper.find( StringDataValue ).props( 'maxlength' ) ).toBe( stringMaxLength );
	} );

	it( 'mounts ReferenceSection', () => {
		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( ReferenceSection ).exists() ).toBeTruthy();
	} );
} );
