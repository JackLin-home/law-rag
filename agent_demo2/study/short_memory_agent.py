from langchain_community.chat_models.tongyi import ChatTongyi
import os 
from dotenv import load_dotenv
from langchain_core.prompts import PromptTemplate
from langchain_core.output_parsers import StrOutputParser
from langchain_core.runnables.history import RunnableWithMessageHistory
from langchain_core.chat_history import InMemoryChatMessageHistory
load_dotenv()
api_key = os.getenv("qianwen_key")
model = ChatTongyi(dashscope_api_key=api_key,model="qwen-plus")
prompt = PromptTemplate.from_template(
    "请根据以下对话历史，继续进行对话：{chat_history}，现在用户说：{input}，请你接着回复"
    )

str_parser = StrOutputParser()
base_chain = prompt | model | str_parser

store={}

def get_history(session_id):
    if session_id not in store:
        store[session_id] = InMemoryChatMessageHistory()
    return store[session_id]
    
    
coversation_chain = RunnableWithMessageHistory(
    base_chain,
    get_history,
    input_messages_key="input",
    history_messages_key="chat_history"
) 


if __name__ == "__main__":
    config = {"configurable": {"session_id": "user1"}}
    res1 = coversation_chain.invoke(input={"input":"我叫jack，请你记住"}, config=config)
    print(res1)
    res2 = coversation_chain.invoke(input={"input":"我今年30岁，请你记住"}, config=config)
    print(res2)
    res3 = coversation_chain.invoke(input={"input":"请你简单介绍一下我"}, config=config)
    print(res3)