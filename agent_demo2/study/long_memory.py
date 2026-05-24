from langchain_community.chat_models.tongyi import ChatTongyi

from langchain_core.messages import message_to_dict, messages_from_dict
from langchain_core.chat_history import BaseChatMessageHistory
from langchain_core.prompts import PromptTemplate
from langchain_core.output_parsers import StrOutputParser
from langchain_core.runnables.history import RunnableWithMessageHistory
from langchain_core.chat_history import InMemoryChatMessageHistory
import os, json
from dotenv import load_dotenv
load_dotenv()
api_key = os.getenv("qianwen_key")
model = ChatTongyi(dashscope_api_key=api_key,model="qwen-plus")

class FileChatMessageHistory(BaseChatMessageHistory):
    def __init__(self, session_id, storage_path):
        self.session_id = session_id
        self.storage_path = storage_path
        self.file_path = os.path.join(storage_path, self.session_id)

        os.makedirs(storage_path, exist_ok=True)

    def add_messages(self, messages)-> None:
        all_messages = list(self.messages)
        all_messages.extend(messages)
        new_messages = [message_to_dict(message) for message in all_messages]  # 获取新增的消息
        
        with open(self.file_path, 'w', encoding='utf-8') as f:
            json.dump(new_messages, f)
    @property
    def messages(self)->list:
        try:
            with open(self.file_path, 'r', encoding='utf-8') as f:
                messages_data = json.load(f)
                return messages_from_dict(messages_data)
        except FileNotFoundError:
            return []
    
    def clear(self):
        with open(self.file_path, 'w', encoding='utf-8') as f:
            json.dump([], f)


prompt = PromptTemplate.from_template(
    "请根据以下对话历史，继续进行对话：{chat_history}，现在用户说：{input}，请你接着回复"
    )
str_parser = StrOutputParser()
base_chain = prompt | model | str_parser


def get_history(session_id):
    return FileChatMessageHistory(session_id, storage_path="./chat_history")
    
    
coversation_chain = RunnableWithMessageHistory(
    base_chain,
    get_history,
    input_messages_key="input",
    history_messages_key="chat_history"
) 


if __name__ == "__main__":
    config = {"configurable": {"session_id": "user1"}}
    # res1 = coversation_chain.invoke(input={"input":"我叫jack，请你记住"}, config=config)
    # print(res1)
    # res2 = coversation_chain.invoke(input={"input":"我今年30岁，请你记住"}, config=config)
    # print(res2)
    res3 = coversation_chain.invoke(input={"input":"请你简单介绍一下我"}, config=config)
    print(res3) 