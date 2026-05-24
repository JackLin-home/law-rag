import os

from langchain_experimental.graph_transformers import LLMGraphTransformer
# 这是老牌的、兼容性最强的导入方式
from langchain_community.chat_models import ChatTongyi
from langchain_core.documents import Document


import sys
# 1. 使用最新的驱动
from langchain_neo4j import Neo4jGraph 


# ==========================================
# 1. 配置区域 - 请在此填写你的信息
# ==========================================

# 通义千问 API Key (从阿里云 DashScope 获取)
os.environ["DASHSCOPE_API_KEY"] = os.getenv("DASHSCOPE_API_KEY", "")

# Neo4j Aura 连接信息（请在 .env 中配置）
if os.getenv("HTTP_PROXY"):
    os.environ["HTTP_PROXY"] = os.getenv("HTTP_PROXY")
if os.getenv("HTTPS_PROXY"):
    os.environ["HTTPS_PROXY"] = os.getenv("HTTPS_PROXY")

URI = os.getenv("NEO4J_URL", "")
USER = os.getenv("NEO4J_USERNAME", "")
PWD = os.getenv("NEO4J_PASSWORD", "")



def main():
    print("--- 正在连接 Neo4j 云数据库 ---")
    
    try:
        # 建立连接
        graph = Neo4jGraph(
            url=URI, 
            username=USER, 
            password=PWD,
            database=os.getenv("NEO4J_DATABASE", "")
        )
        print("✔ Neo4j 云库连接成功！")
        
        # 初始化模型
        llm = ChatTongyi(model="qwen-max", temperature=0)
        transformer = LLMGraphTransformer(llm=llm)

        # 测试数据
        text = "张三在字节跳动工作。"
        documents = [Document(page_content=text)]

        print("--- 正在提取并写入 ---")
        graph_documents = transformer.convert_to_graph_documents(documents)
        graph.add_graph_documents(graph_documents)
        print("✔ 写入成功！")

    except Exception as e:
        print(f"✘ 依然失败。详细报错: {e}")
        print("\n调试建议：\n1. 确认 AirTCP 已连接且开启了【TUN模式】或【全局模式】\n2. 确认网页后台实例处于 Running 状态")
    # 6. 执行知识抽取
    print("--- 正在通过通义千问提取三元组 (这可能需要几秒钟) ---")
    try:
        graph_documents = transformer.convert_to_graph_documents(documents)
        
        # 打印一下提取到的内容预览
        print("\n提取结果预览:")
        for node in graph_documents[0].nodes:
            print(f"节点: [{node.type}] -> {node.id}")
        for rel in graph_documents[0].relationships:
            print(f"关系: {rel.source.id} --[{rel.type}]--> {rel.target.id}")

        # 7. 写入 Neo4j 云数据库
        print("\n--- 正在将数据写入 Neo4j Aura ---")
        graph.add_graph_documents(graph_documents)
        print("✔ 写入完成！请去 Neo4j 控制台查看你的小圆圈吧。")

    except Exception as e:
        print(f"✘ 提取或写入过程中出错: {e}")

if __name__ == "__main__":
    main()