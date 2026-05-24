from langchain_community.llms.tongyi import Tongyi
import os 
from dotenv import load_dotenv
load_dotenv()
api_key = os.getenv("qianwen_key")
model = Tongyi(model="qwen3.5-plus",
               dashscope_api_key=api_key)
res = model.stream(input="你是谁呀,回答你的名字就行")
for chunk in res:
    print(chunk, end="", flush=True) 

