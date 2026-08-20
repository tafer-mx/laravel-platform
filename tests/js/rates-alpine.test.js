import { describe, expect, it, vi } from 'vitest';

import { createRatesComponent } from '../../resources/js/rates/alpine.js';

function createDependencies(overrides = {}) {
    return {
        languageResolver: () => 'en',
        logger: {
            error: vi.fn(),
        },
        rateService: {
            getRatePlansBySuite: vi.fn(),
        },
        ...overrides,
    };
}

describe('Alpine rates component', () => {
    it('loads and formats automatic rate plans', async () => {
        const plans = {
            AI: { discounted_price: 1234.6 },
            EP: { discounted_price: 900 },
        };
        const dependencies = createDependencies();
        dependencies.rateService.getRatePlansBySuite.mockResolvedValue(plans);
        const rates = createRatesComponent(dependencies);
        const component = rates({
            suiteId: 'suite-1',
            campaignCode: 'summer',
            rateCaptions: { AI: 'All inclusive' },
        });

        await component.init();

        expect(dependencies.rateService.getRatePlansBySuite)
            .toHaveBeenCalledWith('summer', 'suite-1');
        expect(component.plans).toBe(plans);
        expect(component.rateCaptions).toEqual({ AI: 'All inclusive' });
        expect(component.isLoading).toBe(false);
        expect(component.error).toBeNull();
        expect(component.formattedRate('AI')).toBe('1,235');
        expect(component.formattedRate('missing')).toBe('—');
    });

    it('uses Spanish currency and number locale', () => {
        const rates = createRatesComponent(createDependencies({
            languageResolver: () => 'es-MX',
        }));
        const component = rates();

        expect(component.currency).toBe('MXN');
        expect(component.numberLocale).toBe('es-MX');
    });

    it('uses valid manual rates without requesting the API', async () => {
        const dependencies = createDependencies();
        const rates = createRatesComponent(dependencies);
        const component = rates({
            useManualRates: true,
            manualRates: {
                AI: 700,
                EP: 500,
            },
        });

        await component.init();

        expect(component.plans).toEqual({
            AI: { discounted_price: 700 },
            EP: { discounted_price: 500 },
        });
        expect(component.isLoading).toBe(false);
        expect(component.error).toBeNull();
        expect(dependencies.rateService.getRatePlansBySuite).not.toHaveBeenCalled();
    });

    it('reports invalid manual rates', async () => {
        const dependencies = createDependencies();
        const rates = createRatesComponent(dependencies);
        const component = rates({
            useManualRates: true,
            manualRates: {
                AI: 700,
            },
        });

        await component.init();

        expect(component.error).toBe('Rates are currently unavailable.');
        expect(component.isLoading).toBe(false);
        expect(dependencies.logger.error).toHaveBeenCalledWith(
            'Unable to use manual rates.',
            expect.any(Error),
        );
    });

    it('does not request rates without suite and campaign identifiers', async () => {
        const dependencies = createDependencies();
        const rates = createRatesComponent(dependencies);
        const component = rates();

        await component.init();

        expect(component.error).toBe('Rates are currently unavailable.');
        expect(component.isLoading).toBe(false);
        expect(dependencies.rateService.getRatePlansBySuite).not.toHaveBeenCalled();
    });

    it('reports API failures and stops loading', async () => {
        const dependencies = createDependencies();
        const requestError = new Error('Request failed');
        dependencies.rateService.getRatePlansBySuite.mockRejectedValue(requestError);
        const rates = createRatesComponent(dependencies);
        const component = rates({
            suiteId: 'suite-1',
            campaignCode: 'summer',
        });

        await component.init();

        expect(component.error).toBe('Rates are currently unavailable.');
        expect(component.isLoading).toBe(false);
        expect(dependencies.logger.error).toHaveBeenCalledWith(
            'Unable to retrieve campaign rates.',
            requestError,
        );
    });
});
