import {
    createRateService,
    getCurrencyByLanguage,
} from './index.js';

function getCurrentLanguage() {
    return typeof document === 'undefined'
        ? 'en'
        : document.documentElement.lang || 'en';
}

function getManualRatePlans(manualRates) {
    const aiRate = manualRates?.AI;
    const epRate = manualRates?.EP;

    if (
        typeof aiRate !== 'number'
        || typeof epRate !== 'number'
        || aiRate < 0
        || epRate < 0
    ) {
        throw new Error('Both manual rates must be valid numbers.');
    }

    return {
        AI: {
            discounted_price: aiRate,
        },
        EP: {
            discounted_price: epRate,
        },
    };
}

export function createRatesComponent({
    baseUrl,
    httpClient,
    languageResolver = getCurrentLanguage,
    logger = console,
    rateService = createRateService({
        baseUrl,
        httpClient,
        languageResolver,
    }),
} = {}) {
    return function rates({
        suiteId,
        campaignCode,
        useManualRates = false,
        manualRates = {},
        rateCaptions = {},
    } = {}) {
        const language = languageResolver();

        return {
            selectedPlan: 'AI',
            isLoading: true,
            error: null,
            plans: {},
            rateCaptions,
            currency: getCurrencyByLanguage(language),
            numberLocale: language.toLowerCase().startsWith('es') ? 'es-MX' : 'en-US',

            async init() {
                if (useManualRates) {
                    try {
                        this.plans = getManualRatePlans(manualRates);
                    } catch (error) {
                        logger.error('Unable to use manual rates.', error);
                        this.error = 'Rates are currently unavailable.';
                    } finally {
                        this.isLoading = false;
                    }

                    return;
                }

                if (!suiteId || !campaignCode) {
                    this.error = 'Rates are currently unavailable.';
                    this.isLoading = false;

                    return;
                }

                try {
                    this.plans = await rateService.getRatePlansBySuite(campaignCode, suiteId);
                } catch (error) {
                    logger.error('Unable to retrieve campaign rates.', error);
                    this.error = 'Rates are currently unavailable.';
                } finally {
                    this.isLoading = false;
                }
            },

            formattedRate(plan) {
                const price = this.plans?.[plan]?.discounted_price;

                if (typeof price !== 'number') {
                    return '—';
                }

                return new Intl.NumberFormat(this.numberLocale, {
                    maximumFractionDigits: 0,
                }).format(price);
            },
        };
    };
}
