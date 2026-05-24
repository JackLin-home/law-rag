<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index($id = null)
    {
        $history = Conversation::where('user_id', Auth::id())->latest()->get();
        $currentConversation = null;
        if ($id) {
            $currentConversation = Conversation::where('user_id', Auth::id())
                ->with(['messages' => function ($query) {
                    $query->orderBy('created_at', 'asc');
                }])
                ->findOrFail($id);
        }
        return view('chat', compact('history', 'currentConversation'));
    }

    public function create()
    {
        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => '新咨询 ' . date('H:i')
        ]);
        return redirect()->route('chat.show', $conversation->id);
    }

    public function destroy($id)
    {
        $conversation = Conversation::where('user_id', Auth::id())->findOrFail($id);
        $conversation->delete();
        return redirect()->route('chat.index');
    }

    // 🌟 新增：接收前端传来的完整 AI 文本存入数据库
    public function saveMessage(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string',
            'content' => 'required|string'
        ]);

        $conversation = Conversation::where('user_id', Auth::id())->findOrFail($id);

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => $request->input('role'),
            'content' => $request->input('content')
        ]);

        return response()->json(['status' => 'success']);
    }

    public function stream(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);
        $conversation = Conversation::where('user_id', Auth::id())->findOrFail($id);
        $userInput = $request->input('message');

        // 存入用户的提问
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userInput
        ]);

        if (str_contains($conversation->title, '新咨询')) {
            $conversation->update(['title' => mb_substr($userInput, 0, 15) . '...']);
        }

        return new StreamedResponse(function () use ($userInput, $conversation) {
            // 关键：禁用 PHP 的输出缓冲
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', 1);
            }
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);

            $ch = curl_init(env('AGENT_API_URL', 'http://127.0.0.1:8001/api/chat'));

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'user_input' => $userInput,
                'session_id' => 'conv_' . $conversation->id,
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            // 关键：流式回调函数（精简版：只负责光速转发，不再做任何多余的字符串切割与数据库操作）
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
                echo $data; // 直接原样转发 SSE 格式给前端浏览器

                // 强制刷出到浏览器
                if (ob_get_level() > 0) ob_flush();
                flush();
                return strlen($data);
            });

            // 禁用 cURL 内部缓冲
            curl_setopt($ch, CURLOPT_BUFFERSIZE, 1);
            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // 禁用 Nginx 缓存
        ]);
    }
}
