<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注册 - 法律咨询助手</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f0f4f9] h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">创建账号</h1>
            <p class="text-gray-500 text-sm mt-2">加入法律 AI 社区，开启您的智能咨询之旅</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1 ml-1">真实姓名</label>
                <input type="text" name="name" :value="old('name')" required autofocus class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-2 focus:ring-[#F53003]/20 focus:border-[#F53003] transition">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1 ml-1">电子邮箱</label>
                <input type="email" name="email" required class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-2 focus:ring-[#F53003]/20 focus:border-[#F53003] transition">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1 ml-1">设置密码</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-2 focus:ring-[#F53003]/20 focus:border-[#F53003] transition">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1 ml-1">确认密码</label>
                <input type="password" name="password_confirmation" required class="w-full px-4 py-3 rounded-2xl bg-gray-50 border-transparent focus:bg-white focus:ring-2 focus:ring-[#F53003]/20 focus:border-[#F53003] transition">
            </div>

            <button type="submit" class="w-full py-4 bg-[#F53003] text-white font-semibold rounded-2xl hover:bg-[#d22702] transition shadow-lg shadow-red-200">
                注 册
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-400 hover:text-[#F53003] transition">
                已有账号？去登录
            </a>
        </div>
    </div>
</body>

</html>