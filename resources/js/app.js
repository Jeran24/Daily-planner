const reminderBanner = document.querySelector('[data-planner-reminder-banner]');
const reminderMessage = document.querySelector('[data-planner-reminder-message]');
const dueUrl = document.querySelector('meta[name="planner-reminder-due-url"]')?.content;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
}

const showReminder = (name, time, id = 'test') => {
    if (reminderBanner && reminderMessage) {
        reminderMessage.textContent = time ? `${name} starts at ${time}.` : name;
        reminderBanner.hidden = false;
    }
    if ('Notification' in window && Notification.permission === 'granted') {
        navigator.serviceWorker?.ready.then((registration) => registration.showNotification('Daily Planner reminder', {
            body: time ? `${name} starts at ${time}.` : name,
            icon: '/icons/planner-192.png',
            tag: `task-reminder-${id}`,
        })).catch(() => {});
    }
    try {
        const audio = new AudioContext();
        const oscillator = audio.createOscillator();
        const gain = audio.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.setValueAtTime(0.0001, audio.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.12, audio.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, audio.currentTime + 0.35);
        oscillator.connect(gain);
        gain.connect(audio.destination);
        oscillator.start();
        oscillator.stop(audio.currentTime + 0.36);
        oscillator.onended = () => audio.close();
    } catch {}
};

document.querySelector('[data-dismiss-reminder]')?.addEventListener('click', () => {
    if (reminderBanner) reminderBanner.hidden = true;
});

const pollReminders = async () => {
    if (!dueUrl) return;
    try {
        const response = await fetch(dueUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) return;
        const { reminders } = await response.json();
        for (const reminder of reminders) {
            const seenKey = `planner-reminder-${reminder.id}`;
            if (sessionStorage.getItem(seenKey)) continue;
            sessionStorage.setItem(seenKey, '1');
            showReminder(reminder.name, reminder.start_time, reminder.id);
        }
    } catch {}
};

if (dueUrl) {
    pollReminders();
    window.setInterval(pollReminders, 60_000);
}

const reminderSettings = document.querySelector('[data-reminder-controls]');
const status = reminderSettings?.querySelector('[data-reminder-status]');
const setStatus = (message) => { if (status) status.textContent = message; };

reminderSettings?.querySelector('[data-enable-reminders]')?.addEventListener('click', async () => {
    if (!('Notification' in window)) {
        setStatus('This browser does not support notifications. The in-page banner will still work.');
        return;
    }
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
        setStatus('Notifications are blocked. Allow them in this site’s browser settings; the in-page banner remains available.');
        return;
    }
    localStorage.setItem('planner-browser-reminders-enabled', '1');
    if (!('PushManager' in window) || !reminderSettings.dataset.vapidKey) {
        setStatus('Browser notifications are enabled while the planner is open. Web Push is not configured on this server yet.');
        return;
    }
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: Uint8Array.from(atob(reminderSettings.dataset.vapidKey.replace(/-/g, '+').replace(/_/g, '/')), (char) => char.charCodeAt(0)),
        });
        const response = await fetch(reminderSettings.dataset.subscribeUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ ...subscription.toJSON(), device_label: navigator.userAgent.slice(0, 120) }),
        });
        if (!response.ok) throw new Error('Subscription could not be saved.');
        setStatus('Browser and closed-tab push reminders are enabled on this device.');
    } catch (error) {
        setStatus(`Open-tab notifications are on, but closed-tab push could not be enabled: ${error.message}`);
    }
});

reminderSettings?.querySelector('[data-test-reminder]')?.addEventListener('click', async () => {
    try {
        const response = await fetch(reminderSettings.dataset.testUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const reminder = await response.json();
        showReminder(reminder.body, '', 'test');
        setStatus('A test reminder was shown.');
    } catch {
        setStatus('The test reminder could not be sent.');
    }
});

reminderSettings?.querySelector('[data-disable-reminders]')?.addEventListener('click', async () => {
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        if (subscription) {
            await fetch(reminderSettings.dataset.unsubscribeUrl, {
                method: 'DELETE', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ endpoint: subscription.endpoint }),
            });
            await subscription.unsubscribe();
        }
        localStorage.removeItem('planner-browser-reminders-enabled');
        setStatus('Push reminders were turned off on this device.');
    } catch {
        setStatus('This device could not be unsubscribed.');
    }
});
