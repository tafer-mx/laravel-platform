import axios from 'axios';

const DEFAULT_BASE_URL = 'https://middleware.taferresorts.com';
const CAMPAIGNS_ENDPOINT = '/api/paraty/campaign';
const DEFAULT_CURRENCY = 'USD';
const CURRENCY_BY_LANGUAGE = Object.freeze({
    en: 'USD',
    es: 'MXN',
});

function isObject(value) {
    return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function getCurrentLanguage() {
    return typeof document === 'undefined'
        ? 'en'
        : document.documentElement.lang || 'en';
}

export function getCurrencyByLanguage(language = getCurrentLanguage()) {
    const languageCode = language.toLowerCase().split('-')[0];

    return CURRENCY_BY_LANGUAGE[languageCode] ?? DEFAULT_CURRENCY;
}

export function getRateBySuiteId(campaignRates, suiteId) {
    const suiteRate = campaignRates[suiteId];

    if (!isObject(suiteRate)) {
        throw new Error(`Rates were not found for suite ${suiteId}.`);
    }

    return suiteRate;
}

export function getPlansFromSuiteRate(suiteRate) {
    if (!isObject(suiteRate.rate)) {
        throw new Error('Suite rate plans were not found.');
    }

    const plans = Object.values(suiteRate.rate)[0];

    if (!isObject(plans)) {
        throw new Error('Suite rate plans were not found.');
    }

    return plans;
}

export function createRateService({
    baseUrl = DEFAULT_BASE_URL,
    httpClient = axios,
    languageResolver = getCurrentLanguage,
} = {}) {
    if (typeof baseUrl !== 'string' || baseUrl.trim() === '') {
        throw new TypeError('Rate service base URL must be a non-empty string.');
    }

    if (!httpClient || typeof httpClient.get !== 'function') {
        throw new TypeError('Rate service HTTP client must provide a get method.');
    }

    if (typeof languageResolver !== 'function') {
        throw new TypeError('Rate service language resolver must be a function.');
    }

    const middlewareUrl = baseUrl.trim().replace(/\/+$/, '');

    async function getRatesByCampaign(campaignCode) {
        if (typeof campaignCode !== 'string' || campaignCode.trim() === '') {
            throw new Error('Campaign code is required.');
        }

        const endpoint = `${middlewareUrl}${CAMPAIGNS_ENDPOINT}/${encodeURIComponent(campaignCode.trim())}`;
        const response = await httpClient.get(endpoint, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': null,
            },
            params: {
                currency: getCurrencyByLanguage(languageResolver()),
            },
        });
        const campaignRates = response.data;

        if (!isObject(campaignRates)) {
            throw new Error('Campaign rates response must be a JSON object.');
        }

        return campaignRates;
    }

    async function getRatePlansBySuite(campaignCode, suiteId) {
        const campaignRates = await getRatesByCampaign(campaignCode);
        const suiteRate = getRateBySuiteId(campaignRates, suiteId);

        return getPlansFromSuiteRate(suiteRate);
    }

    return Object.freeze({
        getRatesByCampaign,
        getRatePlansBySuite,
    });
}
