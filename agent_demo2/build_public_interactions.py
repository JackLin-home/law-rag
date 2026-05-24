import os
import pymysql
import meilisearch
import chromadb
import dashscope
from dotenv import load_dotenv
from chromadb.api.types import Documents, EmbeddingFunction, Embeddings
from langchain_text_splitters import RecursiveCharacterTextSplitter

load_dotenv()

# ==========================================
# 1. 核心配置
# ==========================================
MYSQL_CONFIG = {
    'host': os.getenv('MYSQL_HOST', '127.0.0.1'),
    'user': os.getenv('MYSQL_USER', 'root'),
    'password': os.getenv('MYSQL_PASSWORD', ''),
    'database': os.getenv('MYSQL_DATABASE', 'legal_db'),
    'cursorclass': pymysql.cursors.DictCursor,
}

MEILI_URL = os.getenv('MEILI_URL', 'http://127.0.0.1:7700')
MEILI_KEY = os.getenv('MEILI_KEY', '')

DASHSCOPE_API_KEY = os.getenv('DASHSCOPE_API_KEY', '')
dashscope.api_key = DASHSCOPE_API_KEY

# ==========================================
# 2. 嵌入模型定义
# ==========================================
class QwenEmbeddingFunction(EmbeddingFunction):
    def __init__(self):
        pass

    def __call__(self, input: Documents) -> Embeddings:
        batch_size = 25
        all_embeddings = []
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
# 3. 清洗规则
# ==========================================
def safe_str(val):
    return str(val).strip() if val is not None else ""


def clean_public_interaction(row):
    return (
        f"【咨询标题】：{safe_str(row['title'])}\n"
        f"【群众提问】：{safe_str(row['question'])}\n"
        f"【官方答复】：{safe_str(row['answer'])}"
    )

# ==========================================
# 4. 导入逻辑
# ==========================================
def main():
    print("🚀 启动 public_interactions 导入脚本...")

    db = pymysql.connect(**MYSQL_CONFIG)
    cursor = db.cursor()

    meili_client = meilisearch.Client(MEILI_URL, MEILI_KEY)
    meili_index = meili_client.index('wise_law_chunks')
    meili_index.update_settings({
        'searchableAttributes': ['chunk_text', 'title']
    })

    chroma_client = chromadb.PersistentClient(path='./chroma_data')
    qwen_ef = QwenEmbeddingFunction()
    chroma_collection = chroma_client.get_or_create_collection(
        name='wise_law_vectors',
        embedding_function=qwen_ef
    )

    text_splitter = RecursiveCharacterTextSplitter(
        chunk_size=600,
        chunk_overlap=100,
        separators=["\n\n", "\n", "。", "！", "？", "；", "，", " "]
    )

    cursor.execute('SELECT * FROM public_interactions')
    rows = cursor.fetchall()
    if not rows:
        print('⚠️ public_interactions 表没有返回任何数据。')
        db.close()
        return

    total_chunks = 0
    for row in rows:
        doc_uuid = row.get('doc_uuid') or row.get('id') or ''
        title = safe_str(row.get('title'))
        raw_text = clean_public_interaction(row)
        if not raw_text.strip():
            continue

        chunks = text_splitter.split_text(raw_text)
        meili_batch = []
        chroma_ids = []
        chroma_docs = []
        chroma_metas = []

        for i, chunk_text in enumerate(chunks):
            chunk_id = f"{doc_uuid}_{i}" if doc_uuid else f"pi_{hash(chunk_text)}_{i}"
            metadata = {
                'doc_uuid': doc_uuid,
                'title': title,
                'chunk_index': i,
            }
            meili_batch.append({
                'id': chunk_id,
                'chunk_text': chunk_text,
                'title': title,
                **metadata,
            })
            chroma_ids.append(chunk_id)
            chroma_docs.append(chunk_text)
            chroma_metas.append(metadata)

        if meili_batch:
            try:
                meili_index.add_documents(meili_batch)
                print(f"   ✅ 已写入 {len(meili_batch)} 个 chunk 到 Meilisearch，doc_uuid={doc_uuid[:8] if doc_uuid else 'unknown'}")
            except Exception as e:
                print(f"   ❌ Meilisearch 写入失败: {e}")

            try:
                chroma_collection.add(
                    ids=chroma_ids,
                    documents=chroma_docs,
                    metadatas=chroma_metas
                )
                print(f"   ✅ 已写入 {len(chroma_ids)} 个 chunk 到 ChromaDB。")
            except Exception as e:
                print(f"   ❌ ChromaDB 写入失败: {e}")

            total_chunks += len(meili_batch)

    db.close()
    print(f"🎉 导入完成，总计写入 {total_chunks} 个 chunk。")


if __name__ == '__main__':
    main()
