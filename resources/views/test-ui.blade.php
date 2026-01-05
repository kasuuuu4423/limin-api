<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Limin API Test UI</title>
    <style>
        :root {
            --bg-primary: #0f0f0f;
            --bg-secondary: #1a1a1a;
            --bg-card: #232323;
            --border: #333;
            --text-primary: #e8e8e8;
            --text-secondary: #888;
            --accent-blue: #3b82f6;
            --accent-blue-hover: #2563eb;
            --accent-green: #22c55e;
            --accent-green-hover: #16a34a;
            --accent-red: #ef4444;
            --accent-red-hover: #dc2626;
            --accent-orange: #f59e0b;
            --accent-purple: #a855f7;
            --code-bg: #0d0d0d;
            --code-text: #7ee787;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'SF Pro Text', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--bg-card);
            border-radius: 9999px;
            font-size: 0.875rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-red);
        }

        .status-dot.active {
            background: var(--accent-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
        }

        .card-header strong {
            font-size: 0.9375rem;
            font-weight: 600;
        }

        .card-header .endpoint {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-family: 'SF Mono', monospace;
        }

        .card-body {
            padding: 1.25rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.375rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 0.625rem 0.875rem;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--accent-blue);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-blue-hover);
        }

        .btn-success {
            background: var(--accent-green);
            color: white;
        }

        .btn-success:hover {
            background: var(--accent-green-hover);
        }

        .btn-danger {
            background: var(--accent-red);
            color: white;
        }

        .btn-danger:hover {
            background: var(--accent-red-hover);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--bg-card);
        }

        .btn-outline {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .btn-outline:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        .token-display {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: var(--bg-primary);
            border-radius: 8px;
            font-size: 0.75rem;
            font-family: 'SF Mono', monospace;
            color: var(--text-secondary);
            word-break: break-all;
        }

        .response-panel {
            grid-column: 1 / -1;
        }

        .response-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        pre {
            background: var(--code-bg);
            color: var(--code-text);
            padding: 1rem;
            border-radius: 8px;
            font-family: 'SF Mono', Consolas, monospace;
            font-size: 0.8125rem;
            max-height: 300px;
            overflow: auto;
            line-height: 1.6;
        }

        .next-item-display {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .next-item-display.interrupt {
            border-color: var(--accent-orange);
            background: rgba(245, 158, 11, 0.05);
        }

        .next-item-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .next-item-action {
            font-size: 1.125rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .next-item-meta {
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }

        .interrupt-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            background: rgba(245, 158, 11, 0.2);
            color: var(--accent-orange);
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .next-item-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--accent-blue);
        }

        .session-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--bg-primary);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .session-info-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .session-info-value {
            font-size: 0.875rem;
            font-family: 'SF Mono', monospace;
        }

        .hidden {
            display: none !important;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 14px;
            background: var(--accent-blue);
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Limin API Test UI</h1>
            <div class="status-badge">
                <span class="status-dot" id="authStatusDot"></span>
                <span id="authStatusText">未認証</span>
            </div>
        </header>

        <div class="grid">
            <!-- 認証 -->
            <div class="card">
                <div class="card-header">
                    <strong>認証</strong>
                    <span class="endpoint">POST /auth/token</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="email" value="test@example.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="password" value="password">
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="getToken()">トークン取得</button>
                        <button class="btn btn-secondary" onclick="logout()">ログアウト</button>
                    </div>
                    <div class="token-display" id="currentToken">未取得</div>
                </div>
            </div>

            <!-- セッション管理 -->
            <div class="card">
                <div class="card-header">
                    <strong>セッション管理</strong>
                    <span class="endpoint">POST /session/*</span>
                </div>
                <div class="card-body">
                    <div class="session-info">
                        <div>
                            <div class="session-info-label">現在のセッション</div>
                            <div class="session-info-value" id="currentSessionId">なし</div>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-success" onclick="startSession()">セッション開始</button>
                        <button class="btn btn-secondary" onclick="stopSession()">セッション終了</button>
                    </div>
                </div>
            </div>

            <!-- 次の1件 -->
            <div class="card">
                <div class="card-header">
                    <strong>次の1件</strong>
                    <span class="endpoint">GET /next</span>
                </div>
                <div class="card-body">
                    <div class="next-item-display hidden" id="nextItemDisplay">
                        <div class="interrupt-badge hidden" id="interruptBadge">締切割り込み</div>
                        <div class="next-item-label">タスク名</div>
                        <div class="next-item-title" id="nextItemTitle">-</div>
                        <div class="next-item-label">次の一手</div>
                        <div class="next-item-action" id="nextItemAction">-</div>
                        <div class="next-item-meta" id="nextItemMeta"></div>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="getNext()">次の1件を取得</button>
                    </div>
                    <div class="btn-group hidden" id="interruptActions">
                        <button class="btn btn-success" onclick="acceptInterrupt()">これにする</button>
                        <button class="btn btn-secondary" onclick="rejectInterrupt()">別のにする</button>
                    </div>
                </div>
            </div>

            <!-- Capture -->
            <div class="card">
                <div class="card-header">
                    <strong>Capture（1行登録）</strong>
                    <span class="endpoint">POST /capture</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Text</label>
                        <input type="text" id="captureText" placeholder="明日までに企画書を送る">
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="capture()">登録</button>
                    </div>
                </div>
            </div>

            <!-- Item削除 -->
            <div class="card">
                <div class="card-header">
                    <strong>Item削除</strong>
                    <span class="endpoint">DELETE /item/{id}</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Item ID</label>
                        <input type="text" id="deleteItemId" placeholder="UUID">
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-danger" onclick="deleteItem()">削除</button>
                    </div>
                </div>
            </div>

            <!-- Phase 5: 完了 -->
            <div class="card">
                <div class="card-header">
                    <strong>完了・継続</strong>
                    <span class="endpoint">POST /item/{id}/complete, /continue</span>
                </div>
                <div class="card-body">
                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem;">
                        タスク完了 or 次の一手を設定して継続
                    </p>
                    <div class="form-group">
                        <label>Item ID</label>
                        <input type="text" id="completeItemId" placeholder="UUID">
                    </div>
                    <div class="form-group">
                        <label>次の一手（継続の場合）</label>
                        <input type="text" id="continueNextAction" placeholder="次にやること">
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-success" onclick="completeItem()">タスク完了</button>
                        <button class="btn btn-primary" onclick="continueItem()">継続（次の一手を設定）</button>
                    </div>
                </div>
            </div>

            <!-- Phase 5: 先送り（今は無理） -->
            <div class="card">
                <div class="card-header">
                    <strong>先送り（今は無理）</strong>
                    <span class="endpoint">POST /item/{id}/defer</span>
                </div>
                <div class="card-body">
                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem;">
                        このItemについて今は気力がない → LATER に変更
                    </p>
                    <div class="form-group">
                        <label>Item ID</label>
                        <input type="text" id="deferItemId" placeholder="UUID">
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-secondary" onclick="deferItem()">先送り</button>
                    </div>
                </div>
            </div>

            <!-- レスポンス -->
            <div class="card response-panel">
                <div class="card-header response-header">
                    <strong>Response</strong>
                    <button class="btn btn-outline btn-sm" onclick="clearLog()">Clear</button>
                </div>
                <div class="card-body">
                    <pre id="response">// ここにレスポンスが表示されます</pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        let token = localStorage.getItem('limin_token') || '';
        let currentSessionId = localStorage.getItem('limin_session_id') || null;
        let currentItemId = null;
        let isInterruptPending = false;

        // 初期状態を反映
        updateAuthStatus();
        updateSessionStatus();

        function updateAuthStatus() {
            const dot = document.getElementById('authStatusDot');
            const text = document.getElementById('authStatusText');
            
            if (token) {
                dot.classList.add('active');
                text.textContent = '認証済み';
                document.getElementById('currentToken').textContent = token.substring(0, 40) + '...';
            } else {
                dot.classList.remove('active');
                text.textContent = '未認証';
                document.getElementById('currentToken').textContent = '未取得';
            }
        }

        function updateSessionStatus() {
            const sessionEl = document.getElementById('currentSessionId');
            if (currentSessionId) {
                sessionEl.textContent = currentSessionId.substring(0, 8) + '...';
            } else {
                sessionEl.textContent = 'なし';
            }
        }

        async function getCsrfCookie() {
            await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
        }

        function getCsrfToken() {
            const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
            return match ? decodeURIComponent(match[1]) : '';
        }

        function log(method, url, status, data) {
            const time = new Date().toLocaleTimeString();
            const pre = document.getElementById('response');
            const statusColor = status >= 200 && status < 300 ? '✓' : '✗';
            const entry = `[${time}] ${statusColor} ${method} ${url}\nStatus: ${status}\n${JSON.stringify(data, null, 2)}\n${'─'.repeat(60)}\n`;
            pre.textContent = entry + pre.textContent;
        }

        function clearLog() {
            document.getElementById('response').textContent = '// ここにレスポンスが表示されます';
        }

        function showNextItem(data) {
            const display = document.getElementById('nextItemDisplay');
            const interruptBadge = document.getElementById('interruptBadge');
            const title = document.getElementById('nextItemTitle');
            const action = document.getElementById('nextItemAction');
            const meta = document.getElementById('nextItemMeta');
            const interruptActions = document.getElementById('interruptActions');

            currentItemId = data.id;
            isInterruptPending = data.is_interrupt === true;

            display.classList.remove('hidden', 'interrupt');
            title.textContent = data.title || '-';
            action.textContent = data.next_action || '-';

            let metaText = [];
            if (data.type) metaText.push(`type: ${data.type}`);
            if (data.timebox) metaText.push(`timebox: ${data.timebox}分`);
            if (data.due_at) metaText.push(`due: ${new Date(data.due_at).toLocaleString()}`);
            meta.textContent = metaText.join(' | ');

            // Item IDをフォームに反映
            document.getElementById('deleteItemId').value = data.id;
            document.getElementById('completeItemId').value = data.id;
            document.getElementById('deferItemId').value = data.id;

            if (isInterruptPending) {
                display.classList.add('interrupt');
                interruptBadge.classList.remove('hidden');
                interruptActions.classList.remove('hidden');
            } else {
                interruptBadge.classList.add('hidden');
                interruptActions.classList.add('hidden');
            }
        }

        function hideNextItem() {
            document.getElementById('nextItemDisplay').classList.add('hidden');
            document.getElementById('interruptActions').classList.add('hidden');
            currentItemId = null;
            isInterruptPending = false;
        }

        async function getToken() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                await getCsrfCookie();
                const res = await fetch('/api/auth/token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ email, password, device_name: 'test-ui' })
                });
                const data = await res.json();
                log('POST', '/api/auth/token', res.status, data);

                if (data.token) {
                    token = data.token;
                    localStorage.setItem('limin_token', token);
                    updateAuthStatus();
                }
            } catch (e) {
                log('POST', '/api/auth/token', 'ERROR', { error: e.message });
            }
        }

        async function logout() {
            try {
                await getCsrfCookie();
                const res = await fetch('/api/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });
                log('POST', '/api/auth/logout', res.status, res.status === 204 ? 'Success' : await res.json());

                if (res.status === 204) {
                    token = '';
                    localStorage.removeItem('limin_token');
                    updateAuthStatus();
                }
            } catch (e) {
                log('POST', '/api/auth/logout', 'ERROR', { error: e.message });
            }
        }

        async function startSession() {
            try {
                await getCsrfCookie();
                const res = await fetch('/api/session/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ device_id: 'test-ui' })
                });
                const data = await res.json();
                log('POST', '/api/session/start', res.status, data);

                if (data.session_id) {
                    currentSessionId = data.session_id;
                    localStorage.setItem('limin_session_id', currentSessionId);
                    updateSessionStatus();
                }
            } catch (e) {
                log('POST', '/api/session/start', 'ERROR', { error: e.message });
            }
        }

        async function stopSession() {
            try {
                await getCsrfCookie();
                const res = await fetch('/api/session/stop', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });

                if (res.status === 204) {
                    log('POST', '/api/session/stop', res.status, 'Success');
                    currentSessionId = null;
                    localStorage.removeItem('limin_session_id');
                    updateSessionStatus();
                    hideNextItem();
                } else {
                    const data = await res.json();
                    log('POST', '/api/session/stop', res.status, data);
                }
            } catch (e) {
                log('POST', '/api/session/stop', 'ERROR', { error: e.message });
            }
        }

        async function getNext() {
            try {
                await getCsrfCookie();
                const res = await fetch('/api/next', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });

                if (res.status === 204) {
                    log('GET', '/api/next', res.status, 'No items available');
                    hideNextItem();
                } else {
                    const data = await res.json();
                    log('GET', '/api/next', res.status, data);
                    
                    if (res.status === 200) {
                        showNextItem(data);
                    }
                }
            } catch (e) {
                log('GET', '/api/next', 'ERROR', { error: e.message });
            }
        }

        async function acceptInterrupt() {
            try {
                await getCsrfCookie();
                const res = await fetch('/api/next/interrupt/accept', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });

                if (res.status === 204) {
                    log('POST', '/api/next/interrupt/accept', res.status, 'Accepted');
                    isInterruptPending = false;
                    document.getElementById('interruptActions').classList.add('hidden');
                    document.getElementById('interruptBadge').classList.add('hidden');
                    document.getElementById('nextItemDisplay').classList.remove('interrupt');
                } else {
                    const data = await res.json();
                    log('POST', '/api/next/interrupt/accept', res.status, data);
                }
            } catch (e) {
                log('POST', '/api/next/interrupt/accept', 'ERROR', { error: e.message });
            }
        }

        async function rejectInterrupt() {
            try {
                await getCsrfCookie();
                const res = await fetch('/api/next/interrupt/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });

                if (res.status === 204) {
                    log('POST', '/api/next/interrupt/reject', res.status, 'Rejected, no items available');
                    hideNextItem();
                } else {
                    const data = await res.json();
                    log('POST', '/api/next/interrupt/reject', res.status, data);
                    
                    if (res.status === 200) {
                        showNextItem(data);
                    }
                }
            } catch (e) {
                log('POST', '/api/next/interrupt/reject', 'ERROR', { error: e.message });
            }
        }

        async function capture() {
            const text = document.getElementById('captureText').value;

            try {
                await getCsrfCookie();
                const res = await fetch('/api/capture', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ text })
                });
                const data = await res.json();
                log('POST', '/api/capture', res.status, data);

                if (data.id) {
                    document.getElementById('deleteItemId').value = data.id;
                    document.getElementById('completeItemId').value = data.id;
                    document.getElementById('deferItemId').value = data.id;
                    document.getElementById('captureText').value = '';
                }
            } catch (e) {
                log('POST', '/api/capture', 'ERROR', { error: e.message });
            }
        }

        async function deleteItem() {
            const id = document.getElementById('deleteItemId').value;

            try {
                await getCsrfCookie();
                const res = await fetch(`/api/item/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });

                if (res.status === 204) {
                    log('DELETE', `/api/item/${id}`, res.status, 'Success');
                } else {
                    const data = await res.json();
                    log('DELETE', `/api/item/${id}`, res.status, data);
                }
            } catch (e) {
                log('DELETE', `/api/item/${id}`, 'ERROR', { error: e.message });
            }
        }

        async function completeItem() {
            const id = document.getElementById('completeItemId').value;

            try {
                await getCsrfCookie();
                const res = await fetch(`/api/item/${id}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });

                if (res.status === 204) {
                    log('POST', `/api/item/${id}/complete`, res.status, 'Success - Item completed');
                    hideNextItem();
                } else {
                    const data = await res.json();
                    log('POST', `/api/item/${id}/complete`, res.status, data);
                }
            } catch (e) {
                log('POST', `/api/item/${id}/complete`, 'ERROR', { error: e.message });
            }
        }

        async function deferItem() {
            const id = document.getElementById('deferItemId').value;

            try {
                await getCsrfCookie();
                const res = await fetch(`/api/item/${id}/defer`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });

                if (res.status === 204) {
                    log('POST', `/api/item/${id}/defer`, res.status, 'Success - Item deferred to LATER');
                    hideNextItem();
                } else {
                    const data = await res.json();
                    log('POST', `/api/item/${id}/defer`, res.status, data);
                }
            } catch (e) {
                log('POST', `/api/item/${id}/defer`, 'ERROR', { error: e.message });
            }
        }

        async function continueItem() {
            const id = document.getElementById('completeItemId').value;
            const nextAction = document.getElementById('continueNextAction').value;

            if (!nextAction) {
                log('POST', `/api/item/${id}/continue`, 'ERROR', { error: 'Next action is required for continue' });
                return;
            }

            try {
                await getCsrfCookie();
                const res = await fetch(`/api/item/${id}/continue`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-XSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ next_action: nextAction })
                });

                if (res.status === 204) {
                    log('POST', `/api/item/${id}/continue`, res.status, 'Success - Item continued with new action');
                    document.getElementById('continueNextAction').value = '';
                    hideNextItem();
                } else {
                    const data = await res.json();
                    log('POST', `/api/item/${id}/continue`, res.status, data);
                }
            } catch (e) {
                log('POST', `/api/item/${id}/continue`, 'ERROR', { error: e.message });
            }
        }
    </script>
</body>
</html>

