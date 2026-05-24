import os
import pymysql
import meilisearch
import chromadb
import dashscope
from dotenv import load_dotenv

load_dotenv()
from chromadb.api.types import Documents, EmbeddingFunction, Embeddings
from langchain_text_splitters import RecursiveCharacterTextSplitter
import uuid

# ==========================================
# 1. 核心配置区（请替换为你自己的配置）
# ==========================================
MYSQL_CONFIG = {
    'host': os.getenv('MYSQL_HOST', '127.0.0.1'),
    'user': os.getenv('MYSQL_USER', 'root'),
    'password': os.getenv('MYSQL_PASSWORD', ''),
    'database': os.getenv('MYSQL_DATABASE', 'legal_db'),
    'cursorclass': pymysql.cursors.DictCursor
}

MEILI_URL = os.getenv('MEILI_URL', 'http://127.0.0.1:7700')
MEILI_KEY = os.getenv('MEILI_KEY', '')

DASHSCOPE_API_KEY = os.getenv('DASHSCOPE_API_KEY', '')
dashscope.api_key = DASHSCOPE_API_KEY

# ==========================================
# 2. 定制千问向量模型 (接入 ChromaDB)
# ==========================================
# ==========================================
# 2. 定制千问向量模型 (接入 ChromaDB并处理分批)
# ==========================================
class QwenEmbeddingFunction(EmbeddingFunction):
    def __init__(self):
        # 顺手解决控制台的 DeprecationWarning 警告
        pass 
        
    def __call__(self, input: Documents) -> Embeddings:
        # 阿里千问 API 限制单次最大并发量为 25
        batch_size = 25 
        all_embeddings = []
        
        # 将超长文档的 Chunks 切片成多个 batch (如 30 个块会被切成 [25, 5])
        for i in range(0, len(input), batch_size):
            batch = input[i:i + batch_size]
            
            resp = dashscope.TextEmbedding.call(
                model=dashscope.TextEmbedding.Models.text_embedding_v2,
                input=batch
            )
            
            if resp.status_code == 200:
                all_embeddings.extend([item['embedding'] for item in resp.output['embeddings']])
            else:
                raise Exception(f"千问 Embedding 失败: {resp.code} - {resp.message}")
                
        return all_embeddings

# ==========================================
# 3. 7 张数据表的特征清洗与缝合逻辑
# ==========================================
def safe_str(val):
    """安全转换空值"""
    return str(val).strip() if val is not None else ""

def clean_legal_articles(row):
    return f"【法规名称】：{safe_str(row['title'])}\n【发布日期】：{safe_str(row['publish_date'])}\n【法律全文】：{safe_str(row['content'])}"

def clean_service_guides(row):
    return (f"【指南标题】：{safe_str(row['title'])}\n"
            f"【办理事项】：{safe_str(row['item_name'])} - {safe_str(row['subitem_name'])}\n"
            f"【办理流程】：{safe_str(row['handling_procedures'])}\n"
            f"【申请材料】：{safe_str(row['application_materials'])}")

def clean_complaint_publicities(row):
    return (f"【涉事企业】：{safe_str(row['enterprise_name'])}\n"
            f"【问题类型】：{safe_str(row['case_type'])} - {safe_str(row['issue_type'])}\n"
            f"【处理结果】：{safe_str(row['process_result'])}")

def clean_penalty_decisions(row):
    return (f"【涉事企业】：{safe_str(row['party_name'])}\n"
            f"【处罚机关】：{safe_str(row['penalty_authority'])}\n"
            f"【处罚依据】：{safe_str(row['penalty_basis'])}")

def clean_policy_insights(row):
    return f"【政策标题】：{safe_str(row['title'])}\n【政策解读】：{safe_str(row['content'])}"

def clean_public_interactions(row):
    return (f"【咨询标题】：{safe_str(row['title'])}\n"
            f"【群众提问】：{safe_str(row['question'])}\n"
            f"【官方答复】：{safe_str(row['answer'])}")

def clean_consult_faqs(row):
    return f"【咨询标题】：{safe_str(row['title'])}\n【问答详情】：{safe_str(row['content'])}"

# 映射字典：表名 -> 清洗函数
# 仅导入法律条文库（legal_articles），其余表暂不入库
TABLE_MAP = {
    "legal_articles": clean_legal_articles,
}

# ==========================================
# 4. 主干逻辑：切块与双路入库
# ==========================================
def main():
    print("🚀 启动 WiseLaw 知识库构建流水线...")

    # 初始化数据库连接
    db = pymysql.connect(**MYSQL_CONFIG)
    cursor = db.cursor()
    
    # 初始化 Meilisearch (关键词库)
    meili_client = meilisearch.Client(MEILI_URL, MEILI_KEY)
    meili_index = meili_client.index('wise_law_chunks')
    # 设置 Meilisearch 的主键和可搜索字段
    meili_index.update_settings({
        'searchableAttributes': ['chunk_text', 'title']
    })

    # 初始化 ChromaDB (向量库) - 数据持久化存放在本地 chroma_data 文件夹
    chroma_client = chromadb.PersistentClient(path="./chroma_data")
    qwen_ef = QwenEmbeddingFunction()
    chroma_collection = chroma_client.get_or_create_collection(
        name="wise_law_vectors",
        embedding_function=qwen_ef
    )

    # 初始化文本切块器 (中文友好的分隔符)
    text_splitter = RecursiveCharacterTextSplitter(
        chunk_size=600,       # 每个切块大约 600 字
        chunk_overlap=100,    # 上下文重叠 100 字防截断
        separators=["\n\n", "\n", "。", "！", "？", "；", "，", " "]
    )

    total_chunks = 0

    # 遍历 7 张表
    for table_name, cleaner_func in TABLE_MAP.items():
        print(f"\n📚 正在处理数据表: {table_name}")
        cursor.execute(f"SELECT * FROM {table_name}")
        rows = cursor.fetchall()

        for row in rows:
            doc_uuid = row.get('doc_uuid')
            title = safe_str(row.get('title') or row.get('enterprise_name') or row.get('party_name'))
            
            # 1. 清洗并缝合文本
            raw_text = cleaner_func(row)
            if not raw_text.strip():
                continue
            
            # 2. 切块
            chunks = text_splitter.split_text(raw_text)
            
            # 准备批量入库的容器
            meili_batch = []
            chroma_ids = []
            chroma_docs = []
            chroma_metas = []

            for i, chunk_text in enumerate(chunks):
                # 生成唯一的 chunk_id (Meilisearch 必须)
                chunk_id = f"{doc_uuid}_{i}"
                
                # 统一的元数据
                metadata = {
                    "doc_uuid": doc_uuid,
                    "source_table": table_name,
                    "title": title,
                    "chunk_index": i
                }

                # 组装 Meilisearch 数据
                meili_batch.append({
                    "id": chunk_id, # Meilisearch 主键只能是字母、数字、连字符或下划线
                    "chunk_text": chunk_text,
                    **metadata
                })

                # 组装 ChromaDB 数据
                chroma_ids.append(chunk_id)
                chroma_docs.append(chunk_text)
                chroma_metas.append(metadata)

            # 3. 双路入库
            if meili_batch:
                # 写入关键词库（带确认与日志）
                try:
                    resp = meili_index.add_documents(meili_batch)
                    print(f"   ℹ️ Meilisearch 写入响应: {resp}")

                    # 简短轮询索引状态以确认写入是否被处理（最多等待 10s）
                    import time
                    waited = 0
                    indexed = False
                    while waited < 10:
                        try:
                            stats = meili_index.get_stats()
                            if stats.get('numberOfDocuments', 0) > 0:
                                indexed = True
                                break
                        except Exception:
                            pass
                        time.sleep(1)
                        waited += 1

                    if not indexed:
                        print("   ⚠️ 注意：Meilisearch 写入已发出，但短时间内未检测到索引文档数变化，可能为异步处理或写入目标不同实例。")
                except Exception as e:
                    print(f"   ❌ Meilisearch 写入异常: {e}")

                # 写入向量库 (ChromaDB 会自动调用千问 API 生成向量并存储)
                try:
                    chroma_collection.add(
                        ids=chroma_ids,
                        documents=chroma_docs,
                        metadatas=chroma_metas
                    )
                except Exception as e:
                    print(f"   ❌ ChromaDB 写入异常: {e}")

                total_chunks += len(meili_batch)
                print(f"   ✅ [UUID: {doc_uuid[:8]}...] 切分为 {len(meili_batch)} 个 Chunk，已尝试双路入库。")

    db.close()
    print(f"\n🎉 知识库构建完成！共成功切块并入库 {total_chunks} 个知识片段。")

if __name__ == "__main__":
    main()