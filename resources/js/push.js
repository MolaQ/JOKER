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

    const json = (method, url, body) => fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(body),
    }).then((response) => (response.ok ? response.json() : null));

    // Wybór kibica (tematy, drużyny, obserwowane drużyny z turniejów) zapisany przy jego przeglądarce na serwerze.
    const preferences = async (changes = {}) => {
        const subscription = await currentSubscription();

        return subscription ? json('POST', config.dataset.preferencesUrl, { endpoint: subscription.endpoint, ...changes }) : null;
    };

    // Okienko dzwonka: włączenie, wybór tematów i drużyn, wyłączenie.
    const panel = document.querySelector('[data-push-panel]');
    const allTopics = (panel?.dataset.allTopics || '').split(',').filter(Boolean);
    const topicBoxes = () => [...(panel?.querySelectorAll('[data-push-topic]') || [])];
    const teamBoxes = () => [...(panel?.querySelectorAll('[data-push-team]') || [])];
    let saved = null;

    const showPanel = async () => {
        if (!panel) {
            return;
        }

        const on = Boolean(await currentSubscription());
        saved = on ? await preferences() : null;
        const topics = saved?.topics ?? allTopics;
        topicBoxes().forEach((box) => (box.checked = topics.includes(box.value)));
        teamBoxes().forEach((box) => (box.checked = (saved?.teams ?? []).includes(Number(box.value))));
        panel.querySelectorAll('[data-push-when-on]').forEach((element) => element.classList.toggle('hidden', !on));
        panel.querySelectorAll('[data-push-when-off]').forEach((element) => element.classList.toggle('hidden', on));
        panel.classList.remove('hidden');
    };

    const panelChoices = () => {
        // Tematy wyłączone teraz przez administratora nie są w okienku, ale wybór kibica dla nich zostaje.
        const topics = new Set(saved?.topics ?? allTopics);
        topicBoxes().forEach((box) => (box.checked ? topics.add(box.value) : topics.delete(box.value)));
        const teams = teamBoxes().filter((box) => box.checked).map((box) => Number(box.value));

        return {
            topics: allTopics.every((topic) => topics.has(topic)) ? null : [...topics],
            teams: teams.length ? teams : null,
        };
    };

    panel?.querySelector('[data-push-close]')?.addEventListener('click', () => panel.classList.add('hidden'));
    panel?.querySelectorAll('[data-push-save]').forEach((button) => button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            if (!(await currentSubscription()) && !(await subscribe())) {
                setState(false);

                return;
            }
            setState(true);
            await preferences(panelChoices());
            panel.classList.add('hidden');
        } catch (error) {
            setState(false);
        } finally {
            button.disabled = false;
        }
    }));
    panel?.querySelector('[data-push-disable]')?.addEventListener('click', async () => {
        await unsubscribe();
        setState(false);
        panel.classList.add('hidden');
    });

    // Strona turnieju: „Powiadamiaj mnie o meczach drużyny”.
    document.querySelectorAll('[data-push-follow]').forEach((box) => {
        box.hidden = false;
        const select = box.querySelector('select');
        const list = box.querySelector('[data-push-followed]');
        const names = Object.fromEntries([...select.options].map((option) => [option.value, option.textContent]));

        const render = (ids) => {
            list.replaceChildren(...ids.filter((id) => names[id]).map((id) => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-900 hover:bg-sky-200';
                chip.textContent = `🔔 ${names[id]} ×`;
                chip.title = box.dataset.unfollowLabel;
                chip.addEventListener('click', async () => render((await preferences({ participants: ids.filter((other) => other !== id) }))?.participants ?? []));

                return chip;
            }));
        };

        preferences().then((current) => render(current?.participants ?? [])).catch(() => {});

        box.querySelector('[data-push-follow-add]').addEventListener('click', async () => {
            const id = Number(select.value);
            if (!id) {
                return;
            }
            if (!(await currentSubscription()) && !(await subscribe())) {
                return;
            }
            setState(true);
            const current = (await preferences())?.participants ?? [];
            render((await preferences({ participants: [...new Set([...current, id])] }))?.participants ?? []);
        });
    });

    currentSubscription().then((subscription) => setState(Boolean(subscription))).catch(() => setState(false));

    toggles.forEach((button) => button.addEventListener('click', async () => {
        if (panel) {
            showPanel();

            return;
        }

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
                const on = await subscribe();
                setState(on);
                // Od razu okienko z wyborem tematów i drużyn (domyślnie wszystko).
                if (on) {
                    showPanel();
                }
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
