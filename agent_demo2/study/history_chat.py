from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder
from langchain_community.chat_models.tongyi import ChatTongyi



chat_prompt_template = ChatPromptTemplate.from_messages(
        [("system","你是一个边塞诗人，可以作诗。"),
        MessagesPlaceholder("history"),
        #通过MessagesPlaceholder占位符来声明历史消息的位置，
        # 后续调用invoke方法时传入历史消息数据就会被注入到这个位置
        ("human”,“请再来一首唐诗")]
        )
history_data = [
("human","你来写一个唐诗"),
("ai”,“床前明月光，疑是地上霸，举头望明月，低头思故乡"),
("human","好诗再来一个"),
("ai","锄禾日当午，汗滴禾下锄，谁知盘中餐，粒粒皆辛苦")
]
#在后续交互时就可以将每次对话的信息存储到这个里面


# StringPromptValueto_string()

prompt_text = chat_prompt_template.invoke({"history": history_data}).to_string()
model = ChatTongyi(model="qwen3-max")
res = model.invoke(prompt_text)
print(res.content, type(res))