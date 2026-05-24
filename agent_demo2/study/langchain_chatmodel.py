from langchain_community.chat_models.tongyi import ChatTongyi
from langchain_core.messages import HumanMessage,AIMessage,SystemMessage
import os 
from dotenv import load_dotenv
load_dotenv()
api_key = os.getenv("qianwen_key")
model = ChatTongyi(api_key=api_key,model="qwen-plus")

# messages = [
#     SystemMessage("你是一位著名的唐朝诗人") ,
#     AIMessage(content="大漠孤烟直，长河落日圆"),
#     HumanMessage(content="写一句唐诗")
#     ]

#message的简写形式，不用导包，而且简写为动态模式，其中的
#的变量可以进行替换（变量占位），例如“你是一位著名的{dynasty}诗人”
messages = [
    ("system","你是一位著名的唐朝诗人"),
    ("ai","大漠孤烟直，长河落日圆"),
    ("human","仿照这个案例写一句唐诗"),
    ]

res = model.stream(input=messages)

for chunk in res:
    print(chunk.content,end="",flush=True)