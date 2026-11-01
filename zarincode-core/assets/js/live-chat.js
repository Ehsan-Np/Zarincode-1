/* Zarincode live chat — launcher status, pre-chat gate (name+phone*), thread with
   photo/video/voice uploads, polling, and proactive «online» notifications. */
(() => {
    'use strict';
    if (!window.ZarincodeChat) return;
    const C = window.ZarincodeChat;
    const S = C.strings || {};
    const ID_KEY = 'zc_chat_identity';
    const OUT_KEY = 'zc_chat_last_out';
    const GREET_KEY = 'zc_chat_online_greeted';
    const SND_KEY = 'zc_chat_sound';
    const UNREAD_KEY = 'zc_chat_unread';

    const state = {
        contact: 0, token: '', since: 0, adminOnline: false,
        pollTimer: null, statusTimer: null, sending: false, recording: null,
        unread: 0,
    };

    const store = {
        get() { try { return JSON.parse(localStorage.getItem(ID_KEY) || 'null'); } catch (e) { return null; } },
        set(v) { try { localStorage.setItem(ID_KEY, JSON.stringify(v)); } catch (e) {} },
        lastOut() { return Number(localStorage.getItem(OUT_KEY) || 0); },
        setLastOut(id) { try { localStorage.setItem(OUT_KEY, String(id)); } catch (e) {} },
    };

    const launcher = () => document.querySelector('.zc-chat-launcher');
    const esc = (t) => { const d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; };

    /* ------------------------------------------------------------- sound */
    let actx = null;
    const origTitle = document.title;
    let titleTimer = null;

    function soundEnabled() {
        const stored = localStorage.getItem(SND_KEY);
        if (stored === '0') return false;
        if (stored === '1') return true;
        return C.sound !== false;
    }

    function unlockAudio() {
        try {
            if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)();
            if (actx.state === 'suspended') actx.resume();
        } catch (e) { /* autoplay policy */ }
    }

    function playDing() {
        if (!soundEnabled()) return;
        try {
            unlockAudio();
            if (!actx) return;
            const t = actx.currentTime;
            [[880, 0], [1174.7, 0.14]].forEach((pair) => {
                const f = pair[0]; const off = pair[1];
                const o = actx.createOscillator(); const g = actx.createGain();
                o.type = 'sine'; o.frequency.value = f;
                g.gain.setValueAtTime(0.0001, t + off);
                g.gain.exponentialRampToValueAtTime(0.18, t + off + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, t + off + 0.22);
                o.connect(g); g.connect(actx.destination);
                o.start(t + off); o.stop(t + off + 0.24);
            });
        } catch (e) { /* ignore */ }
    }

    function setUnread(n) {
        state.unread = Math.max(0, n | 0);
        try { localStorage.setItem(UNREAD_KEY, String(state.unread)); } catch (e) {}
        const btn = launcher();
        if (!btn) return;
        let b = btn.querySelector('.zc-chat-unread');
        if (!state.unread) { if (b) b.remove(); return; }
        if (!b) {
            b = document.createElement('span');
            b.className = 'zc-chat-unread';
            b.setAttribute('aria-hidden', 'true');
            btn.appendChild(b);
        }
        b.textContent = state.unread > 9 ? '9+' : String(state.unread);
    }

    function flashTitle() {
        if (titleTimer) return;
        let on = false;
        titleTimer = setInterval(() => {
            on = !on;
            document.title = on ? '(' + state.unread + ') ' + origTitle : origTitle;
        }, 1500);
    }

    function stopTitleFlash() {
        if (titleTimer) { clearInterval(titleTimer); titleTimer = null; }
        document.title = origTitle;
    }

    function syncSoundUI() {
        const btn = document.querySelector('[data-sound-toggle]');
        if (!btn) return;
        const on = soundEnabled();
        btn.classList.toggle('is-muted', !on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        btn.setAttribute('title', on ? 'بی‌صدا کردن اعلان‌ها' : 'فعال کردن صدای اعلان');
        const i = btn.querySelector('i');
        if (i) i.className = on ? 'fa-regular fa-bell' : 'fa-regular fa-bell-slash';
    }

    /* ------------------------------------------------------------ DOM */
    function buildPanel() {
        if (document.getElementById('zc-chat-panel')) return;
        const root = document.createElement('div');
        root.id = 'zc-chat-panel';
        root.className = 'zc-chat-panel';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'false');
        root.setAttribute('aria-label', S.title || 'گفتگوی آنلاین');
        root.hidden = true;
        root.innerHTML = `
            <header class="zc-chat-head">
                <div class="zc-chat-head-title">
                    <strong>${esc(S.title || 'گفتگوی آنلاین زرین کد')}</strong>
                    <span class="zc-chat-status" data-live-status><i></i><em>…</em></span>
                </div>
                <div class="zc-chat-head-actions">
                    <button type="button" class="zc-chat-sound" data-sound-toggle aria-pressed="true" aria-label="صدای اعلان پیام جدید" title="صدای اعلان پیام جدید"><i class="fa-regular fa-bell"></i></button>
                    <button type="button" class="zc-chat-close" aria-label="بستن گفتگو">&times;</button>
                </div>
            </header>
            <section class="zc-chat-body">
                <form class="zc-chat-prechat" novalidate>
                    <p class="zc-chat-welcome">${esc(S.welcome || '')}</p>
                    ${(S.fields && S.fields.name === false) ? '' : `<label class="zc-chat-field"><span>${esc(S.name || 'نام *')}</span>
                        <input type="text" name="name" required maxlength="80" placeholder="مثلاً: علی محمدی" autocomplete="name"></label>`}
                    ${(S.fields && S.fields.phone === false) ? '' : `<label class="zc-chat-field"><span>${esc(S.phone || 'شماره موبایل *')}</span>
                        <input type="tel" name="phone" required maxlength="20" placeholder="0912XXXXXXX" dir="ltr" inputmode="tel" autocomplete="tel"></label>`}
                    ${(S.fields && S.fields.website === true) ? `<label class="zc-chat-field"><span>${esc(S.website || 'وبسایت (اختیاری)')}</span>
                        <input type="text" name="website" maxlength="160" placeholder="example.com" dir="ltr"></label>` : ''}
                    <p class="zc-chat-error" data-chat-error hidden></p>
                    <button type="submit" class="zc-chat-start">${esc(S.start || 'شروع گفتگو')}</button>
                </form>
                <div class="zc-chat-thread" data-chat-thread hidden aria-live="polite"></div>
            </section>
            <footer class="zc-chat-composer" hidden data-chat-composer>
                <div class="zc-chat-tools">
                    <button type="button" class="zc-tool" data-tool="photo" title="ارسال عکس" aria-label="ارسال عکس"><i class="fa-regular fa-image"></i></button>
                    <button type="button" class="zc-tool" data-tool="video" title="ارسال ویدیو" aria-label="ارسال ویدیو"><i class="fa-regular fa-video"></i></button>
                    <button type="button" class="zc-tool zc-tool-voice" data-tool="voice" title="ضبط و ارسال ویس" aria-label="ضبط و ارسال ویس"><i class="fa-solid fa-microphone"></i></button>
                    <input type="file" data-file="photo" accept="image/*" hidden>
                    <input type="file" data-file="video" accept="video/*" capture="environment" hidden>
                </div>
                <form class="zc-chat-input" data-chat-form>
                    <input type="text" data-chat-text maxlength="2000" placeholder="${esc(S.placeholder || 'پیام…')}" autocomplete="off">
                    <button type="submit" class="zc-chat-send" aria-label="ارسال"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            </footer>`;
        document.body.appendChild(root);

        root.querySelector('.zc-chat-close').addEventListener('click', closePanel);
        const sndBtn = root.querySelector('[data-sound-toggle]');
        if (sndBtn) {
            syncSoundUI();
            sndBtn.addEventListener('click', () => {
                const next = !soundEnabled();
                try { localStorage.setItem(SND_KEY, next ? '1' : '0'); } catch (e) {}
                syncSoundUI();
                if (next) playDing();
            });
        }
        root.querySelector('.zc-chat-prechat').addEventListener('submit', onPrechat);
        root.querySelector('[data-chat-form]').addEventListener('submit', (e) => { e.preventDefault(); sendText(); });
        root.querySelectorAll('.zc-tool').forEach((btn) => btn.addEventListener('click', () => onTool(btn.dataset.tool)));
        if (!navigator.mediaDevices || !window.MediaRecorder) {
            const v = root.querySelector('.zc-tool-voice');
            if (v) v.hidden = true;
        }
        if (S.allows) {
            ['photo', 'video', 'voice'].forEach((t) => {
                if (!S.allows[t]) {
                    const b = root.querySelector('.zc-tool[data-tool="' + t + '"]');
                    if (b) b.hidden = true;
                }
            });
        }
        if (S.fileLimit) {
            const hint = document.createElement('p');
            hint.className = 'zc-chat-filehint';
            hint.textContent = S.fileLimit;
            root.querySelector('.zc-chat-tools').appendChild(hint);
        }
    }

    /* -------------------------------------------------------- presence UI */
    function setStatusUI(online, opts) {
        state.adminOnline = !!online;
        const dot = launcher() && launcher().querySelector('.zc-chat-launcher-dot');
        const ribbon = launcher() && launcher().querySelector('.zc-chat-launcher-ribbon');
        const status = document.querySelector('#zc-chat-panel [data-live-status]');
        if (dot) dot.classList.toggle('is-online', !!online);
        if (ribbon) {
            ribbon.hidden = !online;
            ribbon.textContent = online ? (S.online || 'آنلاینیم') : '';
        }
        if (launcher()) launcher().setAttribute('aria-label', online ? (S.online || 'پشتیبانی آنلاین است') : (S.offline || 'گفتگوی زرین کد'));
        if (status) {
            status.classList.toggle('is-online', !!online);
            const em = status.querySelector('em');
            if (em) em.textContent = online ? (S.online || 'پشتیبانی آنلاین است') : (S.offline || 'پشتیبانی آفلاین است؛ پیام بگذارید');
        }
        if (opts && opts.notify && online && !sessionStorage.getItem(GREET_KEY)) {
            try { sessionStorage.setItem(GREET_KEY, '1'); } catch (e) {}
            toast(S.onlineToast || 'کارشناس زرین کد اکنون آنلاین است.');
        }
        if (opts && opts.flip && online && opts.panelWasOpen) {
            systemLine(S.cameOnline || 'کارشناس آنلاین شد.');
        }
    }

    function toast(text) {
        let el = document.getElementById('zc-chat-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'zc-chat-toast';
            el.className = 'zc-chat-toast';
            el.setAttribute('role', 'status');
            document.body.appendChild(el);
        }
        el.innerHTML = '<i class="fa-regular fa-bell"></i> ' + esc(text);
        el.classList.add('is-show');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('is-show'), 6000);
    }

    function systemLine(text) {
        const th = document.querySelector('[data-chat-thread]');
        if (!th) return;
        const line = document.createElement('div');
        line.className = 'zc-chat-system';
        line.innerHTML = '<span>' + esc(text) + '</span>';
        th.appendChild(line);
        th.scrollTop = th.scrollHeight;
    }

    /* ------------------------------------------------------------ panel */
    function openPanel() {
        buildPanel();
        const panel = document.getElementById('zc-chat-panel');
        panel.hidden = false;
        requestAnimationFrame(() => panel.classList.add('is-open'));
        launcher() && launcher().classList.add('is-active');
        setUnread(0);
        stopTitleFlash();
        const id = store.get();
        if (id && id.contact && id.token) {
            state.contact = id.contact;
            state.token = id.token;
            showThread();
            pollNow();
        } else {
            showPrechat();
        }
        fetchStatus(false);
    }

    function closePanel() {
        const panel = document.getElementById('zc-chat-panel');
        if (!panel) return;
        panel.classList.remove('is-open');
        panel.hidden = true;
        launcher() && launcher().classList.remove('is-active');
    }

    function showPrechat() {
        document.querySelector('#zc-chat-panel .zc-chat-prechat').hidden = false;
        document.querySelector('[data-chat-thread]').hidden = true;
        document.querySelector('[data-chat-composer]').hidden = true;
    }

    function showThread() {
        document.querySelector('#zc-chat-panel .zc-chat-prechat').hidden = true;
        document.querySelector('[data-chat-thread]').hidden = false;
        document.querySelector('[data-chat-composer]').hidden = false;
    }

    function chatError(msg) {
        const el = document.querySelector('[data-chat-error]');
        if (!el) return;
        el.hidden = !msg;
        el.textContent = msg || '';
    }

    /* ------------------------------------------------------------ calls */
    async function api(action, data) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('zc_chat_nonce', C.nonce || '');
        if (action !== 'zc_chat_start' && action !== 'zc_chat_status' && state.contact) {
            fd.append('contact', state.contact);
            fd.append('token', state.token || '');
        }
        Object.keys(data || {}).forEach((k) => {
            const v = data[k];
            if (v !== undefined && v !== null) fd.append(k, v);
        });
        const res = await fetch(C.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
        return res.json();
    }

    async function fetchStatus(notify) {
        try {
            const data = await api('zc_chat_status', { contact: state.contact || 0, token: state.token || '' });
            if (data && data.success) setStatusUI(data.data.adminOnline, { notify: notify !== false, flip: false });
        } catch (e) { /* keep last state */ }
    }

    async function onPrechat(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('.zc-chat-start');
        const payload = {
            name: form.name.value.trim(),
            phone: form.phone ? form.phone.value.trim() : '',
            website: form.website ? form.website.value.trim() : '',
        };
        chatError('');
        if (form.name && payload.name.length < 2) { chatError(S.nameRequired || 'نام شما الزامی است.'); form.name.focus(); return; }
        if (form.phone && !/^(?:\+98|0098|98|0)?9\d{9}$/.test(payload.phone.replace(/[\s\-()]/g, ''))) { chatError(S.phoneRequired || 'شماره موبایل معتبر وارد کنید (مثال: ۰۹۱۲۳۴۵۶۷۸۹).'); form.phone.focus(); return; }
        if (btn) { btn.disabled = true; btn.textContent = S.sending || 'در حال شروع…'; }
        try {
            const data = await api('zc_chat_start', payload);
            if (!data.success) { chatError((data.data && data.data.message) || S.error); return; }
            state.contact = data.data.contact;
            state.token = data.data.token;
            store.set({ contact: state.contact, token: state.token, name: data.data.name });
            const th = document.querySelector('[data-chat-thread]');
            th.innerHTML = '';
            (data.data.history || []).forEach(appendMessage);
            bumpSince(data.data.history || []);
            showThread();
            setStatusUI(data.data.adminOnline, { notify: true, flip: false });
            systemLine(data.data.adminOnline ? (S.onlineToast || 'کارشناس زرین کد آنلاین است.') : (S.offline || 'پشتیبانی آفلاین است؛ پیام شما ثبت می‌شود.'));
            startPolling();
        } catch (err) {
            chatError(S.error || 'خطا در ارتباط.');
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = S.start || 'شروع گفتگو'; }
        }
    }

    function appendMessage(m) {
        const th = document.querySelector('[data-chat-thread]');
        if (!th || !m) return null;
        const mine = m.dir === 'in'; // visitor messages are theirs
        const el = document.createElement('div');
        el.className = 'zc-msg ' + (mine ? 'is-mine' : 'is-theirs');
        el.dataset.id = m.id;
        let inner = '';
        if (m.type && m.type !== 'text' && m.file) {
            if (m.type === 'photo') inner += '<img src="' + esc(m.file) + '" alt="" loading="lazy">';
            else if (m.type === 'voice') inner += '<audio controls preload="metadata" src="' + esc(m.file) + '"></audio>';
            else inner += '<video controls preload="metadata" src="' + esc(m.file) + '"></video>';
        }
        if (m.text) inner += '<span class="zc-msg-text">' + esc(m.text).replace(/\n/g, '<br>') + '</span>';
        inner += '<time>' + esc(m.at || '') + '</time>';
        el.innerHTML = inner;
        th.appendChild(el);
        th.scrollTop = th.scrollHeight;
        if (m.dir === 'out') store.setLastOut(Math.max(store.lastOut(), m.id));
        return el;
    }

    function bumpSince(list) {
        (list || []).forEach((m) => { if (m && m.id > state.since) state.since = m.id; });
    }

    function hasMessage(id) { return !!document.querySelector('[data-chat-thread] .zc-msg[data-id="' + id + '"]'); }

    function startPolling() {
        if (state.pollTimer) return;
        state.pollTimer = setInterval(pollNow, C.pollMs || 5000);
        pollNow();
    }

    async function pollNow() {
        if (!state.contact) return;
        try {
            const data = await api('zc_chat_poll', { contact: state.contact, token: state.token, since: state.since });
            if (!data.success) return;
            const wasOnline = state.adminOnline;
            const panel = document.getElementById('zc-chat-panel');
            const panelOpen = panel && !panel.hidden;
            (data.data.messages || []).forEach((m) => {
                const isNew = !hasMessage(m.id);
                if (isNew) appendMessage(m);
                if (isNew && m.dir === 'out') {
                    playDing();
                    if (!panelOpen) {
                        setUnread(state.unread + 1);
                        if (document.hidden) flashTitle();
                        toast('پاسخ جدید پشتیبانی دریافت شد.');
                    }
                }
                if (m.id > state.since) state.since = m.id;
            });
            setStatusUI(data.data.adminOnline, { notify: true, flip: !wasOnline && data.data.adminOnline, panelWasOpen: true });
        } catch (e) { /* transient */ }
    }

    async function sendText() {
        const input = document.querySelector('[data-chat-text]');
        if (!input) return;
        const text = input.value.trim();
        if (!text || state.sending) return;
        state.sending = true;
        try {
            const data = await api('zc_chat_send', { type: 'text', text: text });
            if (data.success && data.data.message) {
                input.value = '';
                const m = data.data.message;
                if (!hasMessage(m.id)) appendMessage(m);
                if (m.id > state.since) state.since = m.id;
            } else {
                toast((data.data && data.data.message) || S.error);
            }
        } catch (e) { toast(S.error); }
        finally { state.sending = false; input.focus(); }
    }

    function onTool(tool) {
        if (tool === 'voice') return toggleVoice();
        const picker = document.querySelector('[data-file="' + tool + '"]');
        if (!picker) return;
        picker.onchange = () => { if (picker.files && picker.files[0]) uploadFile(picker.files[0], tool); picker.value = ''; };
        picker.click();
    }

    async function uploadFile(file, type) {
        if (state.sending) return;
        if (file.size > 24 * 1024 * 1024) { toast('حجم فایل باید کمتر از ۲۴ مگابایت باشد.'); return; }
        state.sending = true;
        const fd = new FormData();
        fd.append('action', 'zc_chat_send');
        fd.append('zc_chat_nonce', C.nonce || '');
        fd.append('contact', state.contact);
        fd.append('token', state.token);
        fd.append('type', type);
        fd.append('text', '');
        fd.append('hc_file', file, file.name || ('zc-' + type));
        try {
            const res = await fetch(C.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await res.json();
            if (data.success && data.data.message) {
                const m = data.data.message;
                if (!hasMessage(m.id)) appendMessage(m);
                if (m.id > state.since) state.since = m.id;
            } else {
                toast((data.data && data.data.message) || S.error);
            }
        } catch (e) { toast(S.error); }
        finally { state.sending = false; }
    }

    /* ------------------------------------------------------- voice notes */
    function toggleVoice() {
        const btn = document.querySelector('.zc-tool-voice');
        if (state.recording) {
            state.recording.stop();
            return;
        }
        if (!navigator.mediaDevices || !window.MediaRecorder) { toast('ضبط ویس در این مرورگر در دسترس نیست.'); return; }
        navigator.mediaDevices.getUserMedia({ audio: true }).then((stream) => {
            const mime = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : (MediaRecorder.isTypeSupported('audio/ogg') ? 'audio/ogg' : '');
            const rec = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
            const chunks = [];
            rec.ondataavailable = (e) => { if (e.data && e.data.size) chunks.push(e.data); };
            rec.onstop = () => {
                stream.getTracks().forEach((t) => t.stop());
                state.recording = null;
                if (btn) { btn.classList.remove('is-recording'); btn.setAttribute('aria-label', 'ضبط و ارسال ویس'); }
                const blob = new Blob(chunks, { type: mime || 'audio/webm' });
                if (blob.size) uploadFile(new File([blob], 'voice.webm', { type: blob.type }), 'voice');
            };
            state.recording = rec;
            rec.start();
            if (btn) { btn.classList.add('is-recording'); btn.setAttribute('aria-label', 'پایان ضبط'); }
            toast('در حال ضبط ویس… دوباره بزنید تا ارسال شود.');
        }).catch(() => toast('دسترسی به میکروفون داده نشد.'));
    }

    /* ------------------------------------------------------------- boot */
    document.addEventListener('DOMContentLoaded', () => {
        const btn = launcher();
        if (!btn) return;
        buildPanel();
        try { setUnread(Number(localStorage.getItem(UNREAD_KEY) || 0)); } catch (e) { setUnread(0); }
        document.addEventListener('pointerdown', unlockAudio, { once: true });
        btn.addEventListener('click', () => {
            const panel = document.getElementById('zc-chat-panel');
            if (panel && !panel.hidden) closePanel();
            else openPanel();
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePanel(); });
        fetchStatus(true);
        state.statusTimer = setInterval(() => {
            if (state.contact && state.pollTimer) return; // poll already carries presence
            fetchStatus(true);
        }, C.statusMs || 20000);
        const id = store.get();
        if (id && id.contact && id.token) startPolling();
        if (location.hash === '#zc-chat') setTimeout(openPanel, 400);
    });
})();
