<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>智律 WiseLaw | 权威法律 AI 平台</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@900&family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #fdfbf7;
            --text-main: #0f172a;
            --legal-gold: #c2a05e;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        .serif-bold {
            font-family: 'Noto Serif SC', serif;
            font-weight: 900;
        }

        .hero-title {
            font-size: clamp(3.5rem, 12vw, 9rem);
            line-height: 0.85;
            letter-spacing: -0.04em;
        }

        .btn-action {
            min-width: 400px;
            /* 进一步拉长，更显霸气 */
            background: var(--text-main);
            color: var(--bg-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-action:hover {
            transform: scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            background: var(--legal-gold);
        }
    </style>
</head>

<body class="relative">

    <nav class="relative z-10 flex justify-between items-center px-12 py-10">
        <div class="flex items-center gap-4">
            <span class="text-5xl">⚖️</span>
            <span class="serif-bold text-3xl tracking-tighter uppercase">WiseLaw</span>
        </div>

        <div class="flex items-center gap-6">
            @auth
            <a href="{{ route('chat.index') }}" class="flex items-center gap-3 group">
                <div class="text-right hidden md:block">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">当前登录</p>
                    <p class="text-xs font-black text-gray-800">{{ Auth::user()->name }}</p>
                </div>
                <div class="w-12 h-12 rounded-full border-2 border-gray-200 flex items-center justify-center bg-white group-hover:border-[#c2a05e] transition shadow-sm">
                    <svg class="w-6 h-6 text-gray-400 group-hover:text-[#c2a05e]" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>
            @else
            <a href="{{ route('login') }}" class="text-xs font-black uppercase tracking-[0.2em] hover:text-[#c2a05e] transition">登录 Login</a>
            <a href="{{ route('register') }}" class="px-8 py-3 border-2 border-black rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-black hover:text-white transition">注册 Join</a>
            @endauth
        </div>
    </nav>

    <main class="relative z-10 flex-1 flex flex-col items-center text-center px-6">

        <div style="height: 15vh;"></div>

        <div class="mb-10">
            <p class="text-[10px] font-black uppercase tracking-[0.6em] text-[#c2a05e]">
                WiseLaw / Next-Gen Legal Engine
            </p>
        </div>

        <h1 class="hero-title serif-bold text-slate-900">
            正义<br>
            <span class="text-gray-200">不再</span>迟到
        </h1>

        <div style="height: 150px;"></div>

        <div class="flex flex-col items-center gap-8">
            @auth
            <a href="{{ route('chat.index') }}" class="btn-action py-6 rounded-2xl text-sm font-black uppercase tracking-[0.3em] shadow-xl">
                进入法律咨询中心
            </a>
            @else
            <a href="{{ route('register') }}" class="btn-action py-6 rounded-2xl text-sm font-black uppercase tracking-[0.3em] shadow-xl">
                立即开启智能对话
            </a>
            @endauth
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest opacity-60">基于 1800+ 权威法律条文构建</p>
        </div>

        <div style="height: 10vh;"></div>
    </main>

    <footer class="relative z-10 mt-auto pb-16 flex flex-col items-center text-center px-6">
        <div class="max-w-2xl mb-8">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] leading-loose">
                智律法律 AI 平台 &middot; 深度 RAG 检索架构 &middot; 多维知识图谱关联 <br class="hidden md:block">
                数据实时同步国家法律法规数据库 &middot;
            </p>
        </div>
        <div class="flex items-center gap-8">
            <div class="h-[1px] w-16 bg-gray-200"></div>
            <span class="serif-bold text-2xl text-gray-200 tracking-widest">WISELAW 1.0</span>
            <div class="h-[1px] w-16 bg-gray-200"></div>
        </div>
    </footer>

</body>

</html>