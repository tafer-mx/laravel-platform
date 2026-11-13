{{--
    Google Consent Mode v2.
    Va en el <head>, ANTES de cualquier script de GTM/gtag, para que las etiquetas
    vean el estado inicial correcto.

    - Envía `consent default` leyendo las preferencias guardadas (todo denegado si no hay).
    - Expone window.taferConsent.update(prefs) para que el banner envíe la elección real.
--}}
@php
    $consentConfig = [
        'storageKey' => (string) config('tafer.consent.storage_key', 'cookie-preferences-v1'),
        'waitForUpdate' => (int) config('tafer.consent.wait_for_update', 500),
        'signals' => (object) config('tafer.consent.signals', []),
        'alwaysGranted' => array_values((array) config('tafer.consent.always_granted', [])),
    ];
@endphp
<script>
    (function (w) {
        if (w.taferConsent) return;

        var cfg = @json($consentConfig);

        w.dataLayer = w.dataLayer || [];
        function gtag() { w.dataLayer.push(arguments); }

        function readPrefs() {
            try {
                var raw = w.localStorage.getItem(cfg.storageKey);
                return raw ? JSON.parse(raw) : null;
            } catch (e) {
                return null;
            }
        }

        function toState(prefs) {
            var state = {};
            Object.keys(cfg.signals).forEach(function (signal) {
                state[signal] = prefs && prefs[cfg.signals[signal]] ? 'granted' : 'denied';
            });
            cfg.alwaysGranted.forEach(function (signal) {
                state[signal] = 'granted';
            });
            return state;
        }

        var initial = toState(readPrefs());
        if (cfg.waitForUpdate > 0) {
            initial.wait_for_update = cfg.waitForUpdate;
        }
        gtag('consent', 'default', initial);

        w.taferConsent = {
            update: function (prefs) {
                gtag('consent', 'update', toState(prefs));
            }
        };
    })(window);
</script>
