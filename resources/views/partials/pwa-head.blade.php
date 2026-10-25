<link rel="manifest" href="{{ route('pwa.manifest', [], false) }}">
<link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="format-detection" content="telephone=no">

<script data-navigate-once>
    (function () {
        // Register the service worker (only works on https or localhost)
        if ('serviceWorker' in navigator && window.isSecureContext) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            });
        }

        window.__installPrompt = null;

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            window.__installPrompt = e;
            window.dispatchEvent(new Event('pwa-installable'));
        });

        window.addEventListener('appinstalled', function () {
            window.__installPrompt = null;
            window.dispatchEvent(new Event('pwa-installed'));
        });

        window.pwaState = function () {
            var ua = window.navigator.userAgent;

            return {
                standalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
                ios: /iphone|ipad|ipod/i.test(ua) || (ua.indexOf('Mac') > -1 && 'ontouchend' in document),
                canPrompt: !!window.__installPrompt
            };
        };

        // Alpine component used by the install card
        window.installApp = function (always) {
            return {
                show: false,
                ios: false,
                always: !!always,
                dismissed: false,

                init: function () {
                    var self = this;

                    try {
                        var until = parseInt(localStorage.getItem('pwa-dismissed') || '0', 10);
                        this.dismissed = !this.always && until > Date.now();
                    } catch (e) {}

                    this.refresh();
                    window.addEventListener('pwa-installable', function () { self.refresh(); });
                    window.addEventListener('pwa-installed', function () { self.refresh(); });
                },

                refresh: function () {
                    var s = window.pwaState();
                    this.ios = s.ios && !s.canPrompt;
                    this.show = !s.standalone && !this.dismissed && (s.canPrompt || s.ios);
                },

                install: function () {
                    var self = this;
                    var prompt = window.__installPrompt;

                    if (!prompt) { return; }

                    prompt.prompt();
                    prompt.userChoice.then(function () {
                        window.__installPrompt = null;
                        self.refresh();
                    });
                },

                dismiss: function () {
                    try { localStorage.setItem('pwa-dismissed', String(Date.now() + 14 * 86400000)); } catch (e) {}
                    this.dismissed = true;
                    this.show = false;
                }
            };
        };
    })();
</script>