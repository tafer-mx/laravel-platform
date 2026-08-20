import { describe, expect, it, vi } from 'vitest';

import {
    createRateService,
    getCurrencyByLanguage,
    getPlansFromSuiteRate,
    getRateBySuiteId,
} from '../../resources/js/rates/index.js';

describe('rate service', () => {
    it('resolves supported and fallback currencies from a language', () => {
        expect(getCurrencyByLanguage('en')).toBe('USD');
        expect(getCurrencyByLanguage('en-US')).toBe('USD');
        expect(getCurrencyByLanguage('es-MX')).toBe('MXN');
        expect(getCurrencyByLanguage('fr')).toBe('USD');
    });

    it('uses English when no DOM is available', () => {
        expect(getCurrencyByLanguage()).toBe('USD');
    });

    it('normalizes the base URL and sends the expected campaign request', async () => {
        const campaignRates = {
            'suite-1': {
                rate: {
                    public: {
                        AI: { discounted_price: 500 },
                    },
                },
            },
        };
        const httpClient = {
            get: vi.fn().mockResolvedValue({ data: campaignRates }),
        };
        const service = createRateService({
            baseUrl: 'https://middleware.example.test///',
            languageResolver: () => 'es-MX',
            httpClient,
        });

        await expect(service.getRatesByCampaign(' summer/sale '))
            .resolves.toBe(campaignRates);
        expect(httpClient.get).toHaveBeenCalledOnce();
        expect(httpClient.get).toHaveBeenCalledWith(
            'https://middleware.example.test/api/paraty/campaign/summer%2Fsale',
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': null,
                },
                params: {
                    currency: 'MXN',
                },
            },
        );
    });

    it('requires a campaign code', async () => {
        const service = createRateService({
            httpClient: { get: vi.fn() },
        });

        await expect(service.getRatesByCampaign('   '))
            .rejects.toThrow('Campaign code is required.');
    });

    it('rejects campaign responses that are not JSON objects', async () => {
        const service = createRateService({
            httpClient: { get: vi.fn().mockResolvedValue({ data: [] }) },
        });

        await expect(service.getRatesByCampaign('campaign'))
            .rejects.toThrow('Campaign rates response must be a JSON object.');
    });

    it('finds a suite and rejects a missing suite', () => {
        const suiteRate = { rate: {} };

        expect(getRateBySuiteId({ suite: suiteRate }, 'suite')).toBe(suiteRate);
        expect(() => getRateBySuiteId({}, 'missing'))
            .toThrow('Rates were not found for suite missing.');
    });

    it('extracts the first plan group and rejects malformed suite data', () => {
        const plans = {
            AI: { discounted_price: 500 },
            EP: { discounted_price: 350 },
        };

        expect(getPlansFromSuiteRate({ rate: { public: plans } })).toBe(plans);
        expect(() => getPlansFromSuiteRate({}))
            .toThrow('Suite rate plans were not found.');
        expect(() => getPlansFromSuiteRate({ rate: {} }))
            .toThrow('Suite rate plans were not found.');
    });

    it('retrieves rate plans for a suite through the configured client', async () => {
        const plans = {
            AI: { discounted_price: 500 },
            EP: { discounted_price: 350 },
        };
        const service = createRateService({
            httpClient: {
                get: vi.fn().mockResolvedValue({
                    data: {
                        suite: {
                            rate: { public: plans },
                        },
                    },
                }),
            },
        });

        await expect(service.getRatePlansBySuite('campaign', 'suite'))
            .resolves.toBe(plans);
    });

    it('validates service dependencies at construction time', () => {
        expect(() => createRateService({ baseUrl: ' ' }))
            .toThrow('Rate service base URL must be a non-empty string.');
        expect(() => createRateService({ httpClient: {} }))
            .toThrow('Rate service HTTP client must provide a get method.');
        expect(() => createRateService({ languageResolver: 'en' }))
            .toThrow('Rate service language resolver must be a function.');
    });
});
