<!DOCTYPE html>
<html lang="zh" class="h-full">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>智律 WiseLaw | 法律 AI 咨询中心</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify/dist/purify.min.js"></script>
    <style>
        :root {
            --bg-creamy: #fdfbf7;
            --legal-gold: #c2a05e;
            --text-navy: #0f172a;
        }

        body {
            background-color: var(--bg-creamy);
            color: var(--text-navy);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        aside {
            width: 320px;
            background-color: #f7f4ef;
            border-right: 1px solid #e5e1d8;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .history-list {
            flex: 1;
            overflow-y: auto;
            padding: 0 1rem;
        }

        .history-list::-webkit-scrollbar {
            display: none;
        }

        .sidebar-active {
            background-color: #eee9df;
            color: var(--text-navy);
            font-weight: 700;
        }

        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: relative;
        }

        #chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            scroll-behavior: smooth;
        }

        .input-container {
            padding: 2rem 0;
            background: linear-gradient(to top, var(--bg-creamy) 80%, transparent);
        }

        .input-wrapper {
            max-width: 850px;
            margin: 0 auto;
            position: relative;
            padding: 0 1.5rem;
        }

        /* 解决遮挡问题的核心样式 */
        #chat-input {
            width: 100% !important;
            background: #ffffff !important;
            border: 1px solid #e5e1d8 !important;
            border-radius: 30px !important;
            padding: 1.25rem 4.5rem 1.25rem 1.5rem !important;
            /* 右侧留足空间 */
            outline: none !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05) !important;
            font-size: 1rem !important;
            resize: none !important;
            line-height: 1.6 !important;
            display: block !important;
        }

        .bubble-user {
            background: #ffffff;
            border: 1px solid #e5e1d8;
            border-radius: 20px 20px 0 20px;
            padding: 0.8rem 1.25rem;
            max-width: 75%;
            margin-left: auto;
            margin-bottom: 2rem;
        }

        .bubble-ai {
            width: 100%;
            padding: 0.5rem 0;
            font-size: 0.95rem;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .markdown-content img {
            max-width: 100%;
        }
    </style>
</head>

<body>
    <aside>
        <div class="p-8">
            <div class="flex items-center gap-4 mb-10">
                <span class="text-5xl">⚖️</span>
                <h1 class="text-2xl font-black tracking-tighter uppercase">WiseLaw</h1>
            </div>
            <a href="{{ route('chat.create') }}" class="flex items-center justify-center gap-2 bg-[#eee9df] hover:bg-[#e5dfd0] transition-all py-4 rounded-2xl text-sm font-bold text-gray-700 shadow-sm">+ 新咨询</a>
        </div>

        <div class="history-list">
            <p class="px-4 text-[10px] font-black text-gray-400 mb-4 uppercase tracking-[0.2em]">历史记录</p>
            @foreach($history as $item)
            <div class="group relative mb-1">
                <a href="{{ route('chat.show', $item->id) }}" class="block px-4 py-3 text-sm text-gray-600 rounded-xl truncate {{ isset($currentConversation) && $currentConversation->id == $item->id ? 'sidebar-active shadow-sm' : 'hover:bg-[#eee9df]' }}">
                    {{ $item->title }}
                </a>
                <form action="{{ route('chat.destroy', $item->id) }}" method="POST" class="absolute right-2 top-2.5 opacity-0 group-hover:opacity-100 transition-opacity">
                    @csrf @method('DELETE')
                    <button class="p-1 text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg></button>
                </form>
            </div>
            @endforeach
        </div>

        <div class="mt-auto p-6 border-t border-[#e5e1d8]">
            <div class="flex items-center justify-between px-2">
                <div class="flex flex-col">
                    <span class="text-xs font-black text-gray-800">{{ Auth::user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button class="text-[10px] text-gray-400 hover:text-red-500 uppercase">退出登录</button></form>
                </div>
                @if(Auth::user()->isAdmin())<a href="/admin" class="text-xs">🛡️</a>@endif
            </div>
        </div>
    </aside>

    <main>
        @if(isset($currentConversation))
        <div id="chat-messages">
            <div class="max-w-3xl mx-auto space-y-10">
                @foreach($currentConversation->messages as $msg)
                <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="{{ $msg->role === 'user' ? 'bubble-user' : 'bubble-ai' }}">
                        <div class="markdown-content" data-role="{{ $msg->role }}" data-content="{{ $msg->content }}">
                            {!! $msg->role === 'assistant' ? '' : e($msg->content) !!}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="input-container">
            <div class="input-wrapper">
                <form id="chat-form" style="position: relative; display: flex; align-items: center;">
                    <textarea id="chat-input" rows="1" placeholder="在此输入法律咨询问题..." autocomplete="off"></textarea>
                    <button type="submit" style="position: absolute; right: 20px; color: var(--legal-gold); background: transparent; border: none; cursor: pointer;">
                        <svg class="w-8 h-8 rotate-90" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </button>
                </form>
                <p class="text-center text-[10px] text-gray-400 mt-4 tracking-widest font-bold">WISELAW AI &middot; 2026</p>
            </div>
        </div>
        @else
        <div class="flex-1 flex flex-col items-center justify-center text-center">
            <span class="text-9xl mb-8">⚖️</span>
            <h2 class="text-6xl font-black mb-8">您好，{{ Auth::user()->name }}</h2>

            <a href="{{ route('chat.create') }}"
                class="transition-all duration-300 shadow-md hover:opacity-90"
                style="background-color: var(--legal-gold); color: #ffffff; display: inline-block; min-width: 320px; padding: 14px 0px; border-radius: 9999px; font-size: 1.125rem; font-weight: 700; text-decoration: none; text-align: center;">
                + 开启新咨询
            </a>
        </div>
        @endif
    </main>

    <script>
        function renderMarkdown() {
            document.querySelectorAll('.markdown-content[data-role="assistant"]').forEach(div => {
                const raw = div.getAttribute('data-content');
                if (raw) div.innerHTML = DOMPurify.sanitize(marked.parse(raw));
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderMarkdown();
            const container = document.getElementById('chat-messages');
            if (container) container.scrollTop = container.scrollHeight;
        });

        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-input');
        const msgContainer = document.getElementById('chat-messages');

        if (form && input) {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const question = input.value.trim();
                if (!question) return;
                input.value = '';

                const wrap = document.querySelector('#chat-messages .max-w-3xl');

                // 插入用户消息
                const userDiv = document.createElement('div');
                userDiv.className = 'flex justify-end';
                userDiv.innerHTML = `<div class="bubble-user">${question}</div>`;
                wrap.appendChild(userDiv);

                // 准备 AI 消息球
                const aiDivWrap = document.createElement('div');
                aiDivWrap.className = 'flex justify-start';
                const aiDiv = document.createElement('div');
                aiDiv.className = 'bubble-ai markdown-content';
                aiDiv.innerHTML = '<span class="animate-pulse italic opacity-40">智律正在查阅法条...</span>';
                aiDivWrap.appendChild(aiDiv);
                wrap.appendChild(aiDivWrap);
                msgContainer.scrollTop = msgContainer.scrollHeight;

                let fullText = "";
                try {
                    const response = await fetch(`/chat/{{ $currentConversation->id ?? '' }}/stream`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            message: question
                        })
                    });

                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();

                    while (true) {
                        const {
                            done,
                            value
                        } = await reader.read();
                        if (done) break;

                        const chunk = decoder.decode(value, {
                            stream: true
                        });
                        const lines = chunk.split('\n');

                        for (const line of lines) {
                            if (line.startsWith('data: ')) {
                                const data = line.slice(6).trim();
                                // 🎯 替换开始：当接收到 [DONE] 信号时，触发保存逻辑
                                if (data === '[DONE]') {
                                    fetch(`/chat/{{ $currentConversation->id ?? '' }}/save-message`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            role: 'assistant',
                                            content: fullText // 自动把拼好的完整回复发给后台
                                        })
                                    }).catch(err => console.error("历史记录保存失败:", err));

                                    break;
                                }
                                // 🎯 替换结束
                                if (data) {
                                    fullText += data;
                                    aiDiv.innerHTML = DOMPurify.sanitize(marked.parse(fullText));
                                    msgContainer.scrollTop = msgContainer.scrollHeight;
                                }
                            }
                        }
                    }
                } catch (err) {
                    aiDiv.innerHTML = '<span class="text-red-500">连接中断，请重试。</span>';
                }
            });
        }
    </script>
</body>

</html>