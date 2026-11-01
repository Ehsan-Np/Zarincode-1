/* Zarincode — admin live-chat screen behaviour.
   list: activity poller (reload on change) + beep/notify/title flash.
   thread: AJAX reply & attachment, append-only live fetch, scroll UX. */
(function () {
    'use strict';
    if (!window.ZarincodeChatAdmin) return;
    var C = window.ZarincodeChatAdmin;
    var mode = C.mode || 'list';
    var lastSig = null;
    var lastUnread = 0;
    var actx = null;
    var origTitle = document.title;
    var titleTimer = null;

    /* ----------------------------------------------------------- utils */
    function api(action, data) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('nonce', C.nonce || '');
        Object.keys(data || {}).forEach(function (k) {
            var v = data[k];
            if (v !== undefined && v !== null) fd.append(k, v);
        });
        return fetch(C.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function beep() {
        try {
            if (!actx) actx = new (window.AudioContext || window.webkitAudioContext)();
            if (actx.state === 'suspended') actx.resume();
            var t = actx.currentTime;
            [[880, 0], [1174.7, 0.14]].forEach(function (pair) {
                var o = actx.createOscillator(); var g = actx.createGain();
                o.type = 'sine'; o.frequency.value = pair[0];
                g.gain.setValueAtTime(0.0001, t + pair[1]);
                g.gain.exponentialRampToValueAtTime(0.2, t + pair[1] + 0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, t + pair[1] + 0.22);
                o.connect(g); g.connect(actx.destination);
                o.start(t + pair[1]); o.stop(t + pair[1] + 0.24);
            });
        } catch (e) { /* ignore */ }
    }

    function notify(count) {
        try {
            if (!('Notification' in window)) return;
            if (Notification.permission === 'granted') {
                new Notification((C.strings && C.strings.title) || 'پیام جدید گفتگوی زنده', {
                    body: ((C.strings && C.strings.bodyNew) || 'پیام جدیدی ثبت شده است.') + ' (' + count + ' خوانده‌نشده)',
                    tag: 'zc-chat-admin'
                });
            } else if (Notification.permission === 'default') {
                Notification.requestPermission();
            }
        } catch (e) { /* ignore */ }
    }

    function flashTitle(count) {
        if (titleTimer) return;
        var on = false;
        titleTimer = setInterval(function () {
            on = !on;
            document.title = on ? '(' + count + ') ' + origTitle : origTitle;
        }, 1500);
    }

    function toast(msg) {
        var el = document.getElementById('zc-ladm-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'zc-ladm-toast';
            el.className = 'zc-ladm-toast';
            el.setAttribute('role', 'status');
            document.body.appendChild(el);
        }
        el.innerHTML = '<i class="fa-solid fa-circle-info"></i> ' + String(msg)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;');
        el.classList.add('is-show');
        clearTimeout(el._t);
        el._t = setTimeout(function () { el.classList.remove('is-show'); }, 3500);
    }

    function setDot(active) {
        var live = document.querySelector('.zc-ladm-live');
        if (live) live.classList.toggle('is-off', !active);
    }

    document.addEventListener('click', function once() {
        try {
            if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission();
        } catch (e) { /* ignore */ }
        document.removeEventListener('click', once);
    });

    /* ------------------------------------------------------- list mode */
    function listTick() {
        api('zc_chat_admin_poll', {}).then(function (j) {
            if (!j || !j.success || !j.data) return;
            var d = j.data;
            setDot(d.active);
            var sig = d.unread + '|' + d.lastId + '|' + d.lastContact;
            if (lastSig === null) { lastSig = sig; lastUnread = d.unread; return; }
            if (sig !== lastSig) {
                if (d.unread > lastUnread) { beep(); if (document.hidden) { notify(d.unread); flashTitle(d.unread); } }
                location.reload();
            }
        }).catch(function () { /* transient */ });
    }

    /* ----------------------------------------------------- thread mode */
    var thread = {
        contact: C.contact || 0,
        since: C.since || 0,
        sending: false,
        pendingFile: null
    };

    function msgsPane() { return document.getElementById('zc-ladm-msgs'); }
    function nearBottom() {
        var pane = msgsPane();
        if (!pane) return true;
        return pane.scrollHeight - pane.scrollTop - pane.clientHeight < 120;
    }
    function scrollBottom(force) {
        var pane = msgsPane();
        if (pane && (force || nearBottom())) pane.scrollTop = pane.scrollHeight;
    }

    function appendHtml(html) {
        var pane = msgsPane();
        if (!pane || !html) return;
        var tpl = document.createElement('template');
        tpl.innerHTML = html.trim();
        var node = tpl.content.firstElementChild;
        if (node) {
            pane.appendChild(node);
            return node;
        }
        return null;
    }

    function updateJump() {
        var btn = document.getElementById('zc-ladm-jump');
        if (!btn) return;
        btn.classList.toggle('is-show', !nearBottom());
    }

    function markRead() {
        api('zc_chat_admin_read', { contact: thread.contact }).catch(function () {});
    }

    function threadTick() {
        api('zc_chat_admin_poll', {}).then(function (j) {
            if (!j || !j.success || !j.data) return;
            setDot(j.data.active);
        }).catch(function () {});
        api('zc_chat_admin_fetch', { contact: thread.contact, since: thread.since }).then(function (j) {
            if (!j || !j.success || !j.data) return;
            var rows = j.data.rows || [];
            if (!rows.length) return;
            var stick = nearBottom();
            rows.forEach(function (r) {
                appendHtml(r.html);
                if (r.id > thread.since) thread.since = r.id;
            });
            markRead();
            if (rows.some(function (r) { return r.dir === 'in'; })) beep();
            if (stick) scrollBottom(true);
            else toast('پیام جدید دریافت شد — برای مشاهده به انتها بروید.');
        }).catch(function () { /* transient */ });
    }

    function autosize(ta) {
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight, 140) + 'px';
    }

    function sendReply() {
        var ta = document.getElementById('zc-ladm-text');
        if (!ta || thread.sending) return;
        var text = ta.value.trim();
        if (!text && !thread.pendingFile) { ta.focus(); return; }
        thread.sending = true;
        var btn = document.getElementById('zc-ladm-send');
        if (btn) btn.disabled = true;

        var fd = new FormData();
        fd.append('action', 'zc_chat_admin_send');
        fd.append('nonce', C.nonce || '');
        fd.append('contact', thread.contact);
        fd.append('text', text);
        if (thread.pendingFile) fd.append('hc_file', thread.pendingFile, thread.pendingFile.name || 'zc-file');

        fetch(C.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j || !j.success || !j.data) {
                    toast((j && j.data && j.data.message) || ((C.strings && C.strings.error) || 'ارسال ناموفق بود.'));
                    return;
                }
                var stick = nearBottom();
                var node = appendHtml(j.data.html);
                if (j.data.id > thread.since) thread.since = j.data.id;
                ta.value = '';
                autosize(ta);
                clearFile();
                if (node && stick !== false) scrollBottom(true);
                markRead();
            })
            .catch(function () { toast((C.strings && C.strings.error) || 'خطا در ارتباط.'); })
            .finally(function () {
                thread.sending = false;
                if (btn) btn.disabled = false;
                ta.focus();
            });
    }

    function clearFile() {
        thread.pendingFile = null;
        var chip = document.getElementById('zc-ladm-filechip');
        var name = document.getElementById('zc-ladm-filechip-name');
        var input = document.getElementById('zc-ladm-file');
        if (chip) chip.classList.remove('is-show');
        if (name) name.textContent = '';
        if (input) input.value = '';
    }

    function bindThread() {
        var ta = document.getElementById('zc-ladm-text');
        var form = document.getElementById('zc-ladm-composer-form');
        var file = document.getElementById('zc-ladm-file');
        var attach = document.getElementById('zc-ladm-attach');
        var jump = document.getElementById('zc-ladm-jump');
        var pane = msgsPane();

        if (form) {
            form.addEventListener('submit', function (e) { e.preventDefault(); sendReply(); });
        }
        if (ta) {
            autosize(ta);
            ta.addEventListener('input', function () { autosize(ta); });
            ta.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                    e.preventDefault();
                    sendReply();
                }
            });
        }
        if (attach && file) {
            attach.addEventListener('click', function () { file.click(); });
            file.addEventListener('change', function () {
                if (file.files && file.files[0]) {
                    thread.pendingFile = file.files[0];
                    var chip = document.getElementById('zc-ladm-filechip');
                    var name = document.getElementById('zc-ladm-filechip-name');
                    if (name) name.textContent = file.files[0].name;
                    if (chip) chip.classList.add('is-show');
                }
            });
        }
        var chipX = document.getElementById('zc-ladm-filechip-x');
        if (chipX) chipX.addEventListener('click', clearFile);

        if (jump) jump.addEventListener('click', function () { scrollBottom(true); });
        if (pane) {
            pane.addEventListener('scroll', updateJump);
            scrollBottom(true);
            updateJump();
        }
        var del = document.getElementById('zc-ladm-delete-form');
        if (del) {
            del.addEventListener('submit', function (e) {
                if (!confirm((C.strings && C.strings.confirmDelete) || 'کل گفتگو برای همیشه حذف شود؟')) e.preventDefault();
            });
        }
        markRead();
    }

    /* ------------------------------------------------------------- boot */
    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (e) {
            var t = e.target && e.target.closest ? e.target.closest('[data-zc-copy]') : null;
            if (!t) return;
            var val = t.getAttribute('data-zc-copy') || '';
            var done = function () { toast('کپی شد: ' + val); };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(val).then(done).catch(function () {});
            }
        });

        if (mode === 'thread') {
            bindThread();
            setInterval(threadTick, C.threadMs || 5000);
            threadTick();
        } else if (mode === 'list') {
            setInterval(listTick, C.pollMs || 8000);
            listTick();
        } else {
            setInterval(function () {
                api('zc_chat_admin_poll', {}).then(function (j) {
                    if (j && j.success && j.data) setDot(j.data.active);
                }).catch(function () {});
            }, 15000);
        }
    });
})();
