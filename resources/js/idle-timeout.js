// Komponen Alpine: peringatan + auto-logout saat tidak aktif (spec 5.3).
const CHANNEL = 'jogjatrip-idle';
const EVENTS = ['mousemove', 'keydown', 'pointerdown', 'scroll', 'touchstart'];
const PING_EVERY = 60; // detik

export default function idleTimeout() {
    return {
        open: false,
        remaining: 0,
        lastActivity: Date.now(),
        lastPing: Date.now(),
        lastSent: 0,
        dirty: false,
        loggingOut: false,
        channel: null,

        init() {
            const d = this.$el.dataset;
            this.timeout = Number(d.timeout);
            this.warn = Math.min(60, Math.floor(this.timeout / 2));
            this.pingUrl = d.pingUrl;
            this.loginUrl = d.loginUrl;
            this.csrf = d.csrf;

            EVENTS.forEach((e) => window.addEventListener(e, () => this.onActivity(), { passive: true }));
            this.listen();
            this.patchFetch();
            this.patchAxios();
            setInterval(() => this.tick(), 1000);
        },

        onActivity() {
            if (this.open) return; // saat modal tampil, hanya tombol yang memperpanjang
            this.touch(Date.now());
            this.broadcastActivity();
        },

        // Siaran dibatasi agar mousemove/scroll tidak membangunkan semua tab terus-menerus.
        broadcastActivity(force = false) {
            if (!force && Date.now() - this.lastSent < 2000) return;
            this.lastSent = Date.now();
            this.send({ type: 'activity', at: this.lastActivity });
        },

        touch(at) {
            this.lastActivity = Math.max(this.lastActivity, at);
            this.dirty = true;
            this.open = false;
        },

        tick() {
            if (this.loggingOut) return;
            const idle = (Date.now() - this.lastActivity) / 1000;
            if (idle >= this.timeout) return this.logout();
            const show = idle >= this.timeout - this.warn;
            if (show && !this.open) this.$nextTick(() => this.$refs.stayButton?.focus());
            this.open = show;
            this.remaining = Math.max(0, Math.ceil(this.timeout - idle));
            if (this.dirty && (Date.now() - this.lastPing) / 1000 >= PING_EVERY) this.ping();
        },

        stay() {
            this.open = false;
            this.touch(Date.now());
            this.broadcastActivity(true);
            this.ping();
        },

        ping() {
            this.dirty = false;
            this.lastPing = Date.now();
            fetch(this.pingUrl, {
                method: 'POST',
                headers: this.headers(),
                credentials: 'same-origin',
            }).catch(() => {});
        },

        headers() {
            return {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrf,
            };
        },

        logout() {
            if (this.loggingOut) return;
            this.loggingOut = true;
            this.send({ type: 'logout' });
            this.$refs.logoutForm.submit();
        },

        // Tab penerima menunggu sebentar agar request logout selesai lebih dulu.
        onLogout() {
            if (this.loggingOut) return;
            this.loggingOut = true;
            setTimeout(() => (window.location = this.loginUrl), 1000);
        },

        // Siaran antar tab: BroadcastChannel, fallback localStorage + event storage.
        listen() {
            const handle = (m) => (m.type === 'logout' ? this.onLogout() : this.touch(m.at));
            if ('BroadcastChannel' in window) {
                this.channel = new BroadcastChannel(CHANNEL);
                this.channel.onmessage = (e) => handle(e.data);
                return;
            }
            window.addEventListener('storage', (e) => {
                if (e.key !== CHANNEL || !e.newValue) return;
                try { handle(JSON.parse(e.newValue)); } catch (_) {}
            });
        },

        send(message) {
            if (this.channel) return this.channel.postMessage(message);
            try { localStorage.setItem(CHANNEL, JSON.stringify({ ...message, n: Math.random() })); } catch (_) {}
        },

        isIdleBody(body) {
            return body && body.reason === 'idle_timeout';
        },

        patchFetch() {
            if (window.__idleFetchPatched) return;
            window.__idleFetchPatched = true;
            const original = window.fetch.bind(window);
            window.fetch = async (...args) => {
                const res = await original(...args);
                if (res.status === 401) {
                    res.clone().json()
                        .then((body) => this.isIdleBody(body) && (window.location = this.loginUrl))
                        .catch(() => {});
                }
                return res;
            };
        },

        patchAxios() {
            if (!window.axios || window.__idleAxiosPatched) return;
            window.__idleAxiosPatched = true;
            window.axios.interceptors.response.use(null, (error) => {
                if (error.response?.status === 401 && this.isIdleBody(error.response.data)) {
                    window.location = this.loginUrl;
                }
                return Promise.reject(error);
            });
        },
    };
}
