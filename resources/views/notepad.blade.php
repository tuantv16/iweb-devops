@extends('layouts.site')

@section('title', config('app.name').' - Notepad')
@section('body_class', 'page-app')
@section('main_class', 'app')
@section('hide_footer', '1')

@section('content')
<div class="app-head">
    <h1><span class="grad">Notepad</span></h1>
    <span class="hint">Tự động lưu trong trình duyệt này — không cần bấm Save</span>
</div>
<div id="warn" class="warn" role="alert" hidden>Không thể lưu vào trình duyệt (bộ nhớ đầy hoặc đang ở chế độ ẩn danh). Dữ liệu sẽ mất khi đóng trang.</div>
<div class="editor">
    <div class="tabbar" id="tabbar"></div>
    <textarea id="text" spellcheck="false" placeholder="Dán hoặc gõ nội dung vào đây..." aria-label="Nội dung"></textarea>
    <div class="statusbar">
        <span id="pos">Ln 1, Col 1</span>
        <span id="len">0 ký tự</span>
        <span class="right" id="saved"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var KEY = 'notepad.v1';
    var tabbar = document.getElementById('tabbar');
    var text = document.getElementById('text');
    var warn = document.getElementById('warn');
    var saved = document.getElementById('saved');
    var state = load();
    var saveTimer = null;

    function newTab(n) { return { id: 't' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6), title: 'new ' + n, content: '' }; }

    function load() {
        try {
            var s = JSON.parse(localStorage.getItem(KEY));
            if (s && Array.isArray(s.tabs) && s.tabs.length) {
                if (!s.tabs.some(function (t) { return t.id === s.active; })) s.active = s.tabs[0].id;
                s.counter = s.counter || s.tabs.length;
                return s;
            }
        } catch (e) { /* dữ liệu hỏng hoặc không có storage -> tạo mới */ }
        var t = newTab(1);
        return { tabs: [t], active: t.id, counter: 1 };
    }

    function persist() {
        clearTimeout(saveTimer);
        try {
            localStorage.setItem(KEY, JSON.stringify(state));
            warn.hidden = true;
            saved.textContent = 'Đã lưu ' + new Date().toLocaleTimeString();
        } catch (e) {
            warn.hidden = false;
            saved.textContent = 'Chưa lưu được';
        }
    }

    function scheduleSave() {
        saved.textContent = 'Đang lưu...';
        clearTimeout(saveTimer);
        saveTimer = setTimeout(persist, 300);
    }

    function current() { return state.tabs.filter(function (t) { return t.id === state.active; })[0]; }

    function updateStatus() {
        var v = text.value, p = text.selectionStart;
        var before = v.slice(0, p).split('\n');
        document.getElementById('pos').textContent = 'Ln ' + before.length + ', Col ' + (before[before.length - 1].length + 1);
        document.getElementById('len').textContent = v.length.toLocaleString() + ' ký tự · ' + v.split('\n').length.toLocaleString() + ' dòng';
    }

    function render() {
        tabbar.textContent = '';
        state.tabs.forEach(function (t) {
            var el = document.createElement('div');
            el.className = 'tab' + (t.id === state.active ? ' active' : '');
            el.title = 'Nhấp đúp để đổi tên';
            var title = document.createElement('span');
            title.className = 'tab-title';
            title.textContent = t.title;
            var close = document.createElement('button');
            close.className = 'tab-close';
            close.type = 'button';
            close.setAttribute('aria-label', 'Đóng tab ' + t.title);
            close.textContent = '×';
            el.appendChild(title);
            el.appendChild(close);

            el.addEventListener('click', function () { if (state.active !== t.id) switchTo(t.id); });
            el.addEventListener('dblclick', function () { rename(t, el, title); });
            close.addEventListener('click', function (e) { e.stopPropagation(); closeTab(t); });
            tabbar.appendChild(el);
        });
        var add = document.createElement('button');
        add.className = 'tab-add';
        add.type = 'button';
        add.title = 'Tab mới (Alt+N)';
        add.setAttribute('aria-label', 'Thêm tab mới');
        add.textContent = '+';
        add.addEventListener('click', addTab);
        tabbar.appendChild(add);

        text.value = current().content;
        updateStatus();
    }

    function switchTo(id) {
        state.active = id;
        scheduleSave();
        render();
        text.focus();
    }

    function addTab() {
        state.counter += 1;
        var t = newTab(state.counter);
        state.tabs.push(t);
        state.active = t.id;
        persist();
        render();
        text.focus();
    }

    function closeTab(t) {
        if (t.content.trim() && !confirm('Đóng "' + t.title + '"? Nội dung của tab sẽ bị xóa.')) return;
        var i = state.tabs.indexOf(t);
        state.tabs.splice(i, 1);
        if (!state.tabs.length) {
            state.counter += 1;
            state.tabs.push(newTab(state.counter));
        }
        if (state.active === t.id) state.active = state.tabs[Math.min(i, state.tabs.length - 1)].id;
        persist();
        render();
    }

    function rename(t, el, titleEl) {
        var input = document.createElement('input');
        input.className = 'tab-rename';
        input.maxLength = 40;
        input.value = t.title;
        el.replaceChild(input, titleEl);
        input.focus();
        input.select();
        var done = false;
        function finish(ok) {
            if (done) return;
            done = true;
            if (ok && input.value.trim()) { t.title = input.value.trim(); persist(); }
            render();
            text.focus();
        }
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') finish(true);
            else if (e.key === 'Escape') finish(false);
        });
        input.addEventListener('blur', function () { finish(true); });
        input.addEventListener('click', function (e) { e.stopPropagation(); });
        input.addEventListener('dblclick', function (e) { e.stopPropagation(); });
    }

    text.addEventListener('input', function () {
        current().content = text.value;
        updateStatus();
        scheduleSave();
    });
    ['keyup', 'click', 'select'].forEach(function (ev) { text.addEventListener(ev, updateStatus); });

    // Phím Tab chèn ký tự tab thay vì nhảy focus ra ngoài (giống Notepad++); Esc rồi Tab để thoát bằng bàn phím.
    var escaped = false;
    text.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { escaped = true; return; }
        if (e.key === 'Tab' && !e.shiftKey && !escaped) {
            e.preventDefault();
            document.execCommand('insertText', false, '\t');
        }
        escaped = false;
    });

    document.addEventListener('keydown', function (e) {
        if (e.altKey && (e.key === 'n' || e.key === 'N')) { e.preventDefault(); addTab(); }
    });

    // Lưu ngay khi rời trang để không mất ký tự cuối đang chờ debounce.
    window.addEventListener('pagehide', persist);
    document.addEventListener('visibilitychange', function () { if (document.hidden) persist(); });

    // Đồng bộ khi mở trang ở nhiều cửa sổ/tab trình duyệt.
    window.addEventListener('storage', function (e) {
        if (e.key !== KEY || document.activeElement === text) return;
        state = load();
        render();
    });

    render();
    text.focus();
})();
</script>
@endpush
