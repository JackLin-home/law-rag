from langchain_chroma import Chroma
from langchain_community.embeddings import DashScopeEmbeddings
from langchain_community.document_loaders import CSVLoader
import os, json
from langchain_community.chat_models.tongyi import ChatTongyi
from dotenv import load_dotenv
load_dotenv()
api_key = os.getenv("qianwen_key")
#Chroma 向量数据库（轻量级的）
#确保 langchain-chroma chromadb 这两个库安装了的，没有的话请pip install

vector_store = Chroma(
    collection_name="test",     # 当前向量存储起个名字，类似数据库的表名称
    embedding_function=DashScopeEmbeddings(dashscope_api_key=api_key),       # 嵌入模型
    persist_directory="./chroma_db"     # 指定数据存放的文件夹
)


loader = CSVLoader(
    file_path="C:/Users/蔺起源/Desktop/agent/agent_demo2/info.csv",
    encoding="utf-8",
    source_column="source",     # 指定本条数据的来源是哪里
)

documents = loader.load()
# id1 id2 id3 id4 ...
# 向量存储的 新增、删除、检索
vector_store.add_documents(
    documents=documents,        # 被添加的文档，类型：list[Document]
    ids=["id"+str(i) for i in range(1, len(documents)+1)] # 给添加的文档提供id（字符串）  list[str]
)

# 删除  传入[id, id...]
vector_store.delete(["id1", "id2"])

# 检索 返回类型list[Document]
result = vector_store.similarity_search(
    "Python是不是简单易学呀",
    3,        # 检索的结果要几个
    filter={"source": "黑马程序员"}
)

# print(result)






model = ChatTongyi(dashscope_api_key=api_key,model="qwen-plus")

def generate_rag_answer(query, result):
    # 1. 提取所有检索到的内容并拼接
    context = "\n".join([doc.page_content for doc in result])
    
    # 2. 构建针对性的 Prompt
    rag_prompt = f"""
    你是一个专业的助手。请根据以下提供的【背景资料】来回答用户的【问题】。
    如果背景资料里没有相关信息，请直接说“我不确定”，不要瞎编。

    【背景资料】：
    {context}

    【问题】：
    {query}
    
    请给出你的回复：
    """
    
    # 3. 调用你之前的 model 对象进行回答
    # 注意：这里的 model 是你之前定义的 ChatTongyi 对象
    response = model.invoke(rag_prompt)
    return response.content

# 模拟运行
query = "学习Python的时候需要注意什么？"
answer = generate_rag_answer(query, result) # retrieved_docs 就是你刚才发给我的列表
print(f"AI 的回答：\n{answer}")