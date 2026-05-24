import os
from neo4j import GraphDatabase
from dotenv import load_dotenv

load_dotenv()

if os.getenv("HTTP_PROXY"):
    os.environ["HTTP_PROXY"] = os.getenv("HTTP_PROXY")
if os.getenv("HTTPS_PROXY"):
    os.environ["HTTPS_PROXY"] = os.getenv("HTTPS_PROXY")

URI = os.getenv("NEO4J_URL", "")
USER = os.getenv("NEO4J_USERNAME", "")
PWD = os.getenv("NEO4J_PASSWORD", "")

def test():
    print(f"正在尝试握手: {URI}...")
    # 建立驱动
    driver = GraphDatabase.driver(URI, auth=(USER, PWD))
    try:
        with driver.session() as session:
            # 执行一个极简查询
            result = session.run("RETURN '连接成功！' AS msg").single()
            print(f"【🎉 恭喜】服务器反馈：{result['msg']}")
    except Exception as e:
        print(f"【❌ 失败】原因：{e}")
        print("\n如果依然提示 Unauthorized，请去 Neo4j Aura 网页重置一次密码，并直接点击复制按钮。")
    finally:
        driver.close()

if __name__ == "__main__":
    test()