// Powiadomienia push: przycisk dzwonka w menu i karta z zaproszeniem przy drugiej wizycie.
const DISMISS_KEY = 'joker-push-dismissed';
const VISITS_KEY = 'joker-visits';

function store(key, value) {
    try {
        if (value === undefined) {
            return window.localStorage.getItem(key);
        }
        window.localStorage.setItem(key, value);
    } catch (error) {
        return null;
    }

    return null;
}

function urlBase64ToUint8Array(base64) {
    const padding = '='.repeat((4 - (base64.length % 4)) % 4);
    const raw = window.atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));

    return Uint8Array.from([...raw].map((char) => char.charCodeAt(0)));
}

function isIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
}

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

export function initPush() {
    const config = document.querySelector('[data-push-config]');
    if (!config) {
        return;
    }

    const toggles = document.querySelectorAll('[data-push-toggle]');
    const prompt = document.querySelector('[data-push-prompt]');
    const iosHint = document.querySelector('[data-push-ios-hint]');
    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

    if (!supported) {
        // iPhone pozwala na powiadomienia dopiero po dodaniu strony do ekranu początkowego.
        if (isIos() && !isStandalone()) {
            toggles.forEach((button) => {
                button.hidden = false;
                button.addEventListener('click', () => iosHint?.classList.remove('hidden'));
            });
        }

        return;
    }

    const registration = navigator.serviceWorker.register(config.dataset.sw, { scope: config.dataset.scope });

    const setState = (on) => {
        toggles.forEach((button) => {
            button.hidden = false;
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
            button.title = on ? button.dataset.labelOn : button.dataset.labelOff;
            button.querySelector('[data-push-on]')?.classList.toggle('hidden', !on);
            button.querySelector('[data-push-off]')?.classList.toggle('hidden', on);
        });
    };

    const currentSubscription = async () => (await registration).pushManager.getSubscription();

    const subscribe = async () => {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            return false;
        }

        const { publicKey } = await (await fetch(config.dataset.keyUrl, { headers: { Accept: 'application/json' } })).json();
        const subscription = await (await registration).pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(publicKey),
        });
        const json = subscription.toJSON();
        const encoding = (window.PushManager.supportedContentEncodings || ['aes128gcm'])[0];

        await fetch(config.dataset.subscribeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ endpoint: json.endpoint, keys: json.keys, contentEncoding: encoding }),
        });

        return true;
    };

    const unsubscribe = async () => {
        const subscription = await currentSubscription();
        if (!subscription) {
            return;
        }

        await fetch(config.dataset.subscribeUrl, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ endpoint: subscription.endpoint }),
        });
        await subscription.unsubscribe();
    };

    currentSubscription().then((subscription) => setState(Boolean(subscription))).catch(() => setState(false));

    toggles.forEach((button) => button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            if (await currentSubscription()) {
                await unsubscribe();
                setState(false);
            } else {
                setState(await subscribe());
            }
        } catch (error) {
            setState(false);
        } finally {
            button.disabled = false;
        }
    }));

    // Zaproszenie: przy drugiej wizycie, gdy kibic jeszcze nie zdecydował i nie zamknął karty w ostatnim miesiącu.
    const visits = Number(store(VISITS_KEY) || 0) + 1;
    store(VISITS_KEY, String(visits));
    const dismissedAt = Number(store(DISMISS_KEY) || 0);

    if (prompt && visits >= 2 && Notification.permission === 'default' && Date.now() - dismissedAt > 30 * 86400000) {
        window.setTimeout(() => prompt.classList.remove('hidden'), 4000);

        prompt.querySelector('[data-push-accept]')?.addEventListener('click', async () => {
            prompt.classList.add('hidden');
            try {
                setState(await subscribe());
            } catch (error) {
                setState(false);
            }
        });
        prompt.querySelector('[data-push-dismiss]')?.addEventListener('click', () => {
            prompt.classList.add('hidden');
            store(DISMISS_KEY, String(Date.now()));
        });
    }
}
