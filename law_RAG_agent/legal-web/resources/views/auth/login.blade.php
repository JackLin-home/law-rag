<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登录 - 法律咨询助手</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f0f4f9] h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">欢迎回来</h1>
            <p class="text-gray-500 text-sm mt-2">请登录您的账号以同步法律咨询历史</p>
        </div>

        @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-xl">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1 ml-1">电子邮箱</label>
                <input type="email" name="email" required autofocus class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-2 focus:ring-[#F53003]/20 focus:border-[#F53003] transition duration-200 outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1 ml-1">登录密码</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-2 focus:ring-[#F53003]/20 focus:border-[#F53003] transition duration-200 outline-none">
            </div>

            <button type="submit" class="w-full py-4 bg-[#F53003] text-white font-semibold rounded-2xl hover:bg-[#d22702] transform active:scale-[0.98] transition duration-200 shadow-lg shadow-red-200">
                立即登录
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col gap-3">
            <p class="text-center text-sm text-gray-500">还没有账号？</p>
            <a href="{{ route('register') }}" class="w-full py-4 bg-white border-2 border-gray-100 text-gray-700 font-semibold rounded-2xl hover:bg-gray-50 text-center transition">
                创建新账号 (注册)
            </a>
        </div>
    </div>
</body>

</html>