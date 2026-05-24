import os
import requests
import json
from dotenv import load_dotenv

load_dotenv()

def test_fastapi_stream():
    base_url = os.getenv("AGENT_API_URL", "http://127.0.0.1:8001")
    url = f"{base_url.rstrip('/')}/api/chat"
    payload = {
        "user_input": "你好，请问劳动法关于加班的规定是什么？",
        "session_id": "test_debug_001"
    }
    
    print("🚀 开始请求 FastAPI (8001)...")
    
    # stream=True 是关键
    response = requests.post(url, json=payload, stream=True)
    
    if response.status_code != 200:
        print(f"❌ 请求失败，状态码: {response.status_code}")
        return

    print("📖 正在接收流式输出：\n" + "-"*30)
    
    for line in response.iter_lines():
        if line:
            decoded_line = line.decode('utf-8')
            if decoded_line.startswith('data: '):
                content = decoded_line[6:]
                if content == "[DONE]":
                    print("\n" + "-"*30 + "\n✅ 接收完成")
                    break
                print(content, end="", flush=True)

if __name__ == "__main__":
    test_fastapi_stream()