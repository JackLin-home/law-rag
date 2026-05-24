import os
import json
import dashscope
from pathlib import Path
from dotenv import load_dotenv

load_dotenv()
from fastapi import FastAPI, Request
from fastapi.responses import StreamingResponse, JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

# LangChain 组件
from langchain_community.chat_models.tongyi import ChatTongyi
from langchain_neo4j import Neo4jGraph
from langchain_chroma import Chroma
from langchain_community.embeddings.dashscope import DashScopeEmbeddings
from langchain_core.messages import AIMessage, HumanMessage
from langchain_community.chat_message_histories import FileChatMessageHistory

# =====================================================================
# ---------------------- 1. 基础配置与组件初始化 ----------------------
# =====================================================================
DASHSCOPE_API_KEY = os.getenv("DASHSCOPE_API_KEY", "")
if not DASHSCOPE_API_KEY:
    raise RuntimeError("请在 agent_demo2/.env 中设置 DASHSCOPE_API_KEY")
os.environ["DASHSCOPE_API_KEY"] = DASHSCOPE_API_KEY
dashscope.api_key = DASHSCOPE_API_KEY

# 初始化向量库 (ChromaDB)
embeddings = DashScopeEmbeddings(model="text-embedding-v2")
vectorstore = Chroma(persist_directory="./chroma_db", embedding_function=embeddings)

# 初始化图数据库 (Neo4j - 本次仅做读取查询)
NEO4J_DATABASE = os.getenv("NEO4J_DATABASE", "")
graph = Neo4jGraph(
    url=os.getenv("NEO4J_URL", ""),
    username=os.getenv("NEO4J_USERNAME", ""),
    password=os.getenv("NEO4J_PASSWORD", ""),
    database=NEO4J_DATABASE,
)

# 本地演示版本的 Meilisearch 替代器
LOCAL_JSONL_PATH = Path("database/政民互动.jsonl")
LOCAL_MEILI_PATH = Path("database/local_meili_demo.json")


def build_local_meili_data(limit=20):
    docs = []
    if LOCAL_MEILI_PATH.exists():
        try:
            with LOCAL_MEILI_PATH.open('r', encoding='utf-8') as f:
                docs = json.load(f)
        except Exception:
            docs = []
    if docs:
        return docs

    if not LOCAL_JSONL_PATH.exists():
        return docs

    with LOCAL_JSONL_PATH.open('r', encoding='utf-8') as f:
        for i, line in enumerate(f):
            if i >= limit:
                break
            line = line.strip()
            if not line:
                continue
            try:
                item = json.loads(line)
            except Exception:
                continue
            title = str(item.get('title', '')).strip()
            question = str(item.get('question', '')).strip()
            answer = str(item.get('answer', '')).strip()
            contents = f"{title}\n{question}\n{answer}".strip()
            docs.append({
                'id': item.get('consult_id', f'local_{i}') or f'local_{i}',
                'doc_uuid': item.get('consult_id', f'local_{i}') or f'local_{i}',
                'title': title or '政民互动',
                'chunk_text': contents,
                'question': question,
                'answer': answer,
            })

    try:
        LOCAL_MEILI_PATH.parent.mkdir(parents=True, exist_ok=True)
        with LOCAL_MEILI_PATH.open('w', encoding='utf-8') as f:
            json.dump(docs, f, ensure_ascii=False, indent=2)
    except Exception:
        pass

    return docs


class LocalMeiliIndex:
    def __init__(self):
        self.docs = build_local_meili_data(limit=20)

    def update_settings(self, settings):
        return {}

    def search(self, q, params=None):
        if params is None:
            params = {}
        limit = params.get('limit', 10)
        query = str(q or '').strip()
        tokens = [t.strip() for t in query.replace('　', ' ').split() if t.strip()]

        results = []
        for doc in self.docs:
            text = ' '.join([doc.get('title', ''), doc.get('chunk_text', ''), doc.get('question', ''), doc.get('answer', '')])
            score = 0
            if query and query in text:
                score += 5
            for token in tokens:
                if token in doc.get('title', ''):
                    score += 4
                if token in doc.get('question', ''):
                    score += 2
                if token in doc.get('answer', ''):
                    score += 1
            if score > 0:
                results.append((score, doc))

        results.sort(key=lambda x: x[0], reverse=True)
        hits = [doc for _, doc in results[:limit]]
        return {'hits': hits}

    def add_documents(self, docs):
        for doc in docs:
            if not any(existing['id'] == doc.get('id') for existing in self.docs):
                self.docs.append(doc)
        try:
            with LOCAL_MEILI_PATH.open('w', encoding='utf-8') as f:
                json.dump(self.docs, f, ensure_ascii=False, indent=2)
        except Exception:
            pass
        return {'status': 'ok'}

    def delete_document(self, doc_id):
        self.docs = [doc for doc in self.docs if doc.get('id') != doc_id]
        try:
            with LOCAL_MEILI_PATH.open('w', encoding='utf-8') as f:
                json.dump(self.docs, f, ensure_ascii=False, indent=2)
        except Exception:
            pass
        return {'status': 'ok'}


# 初始化本地 Meilisearch 替代器
meili_index = LocalMeiliIndex()

# 初始化通义千问大模型
llm = ChatTongyi(model="qwen-plus", temperature=0.1)


# =====================================================================
# ---------------------- 2. 意图识别与查询理解重写 ----------------------
# =====================================================================
# =====================================================================
# ---------------------- 2. 意图识别与查询理解重写 ----------------------
# =====================================================================
def analyze_and_rewrite_query(user_query: str, history_text: str) -> dict:
    prompt = f"""
    分析用户意图并提炼搜索词：
    【历史对话】：{history_text}
    【当前问题】：{user_query}
    
    任务分类：
    1. 若用户进行日常问候、闲聊、或询问关于本次对话本身的记录，回复：INTENT: CHAT | QUERY: 原话。
    2. 若用户询问法律、办事指南等需要查知识库的问题：请结合上下文，提炼出最核心的 2 到 4 个关键词（用空格隔开）。绝对不要写成完整的长句子！
    
    示例输入：上海市对于那种用地沟油（也就是餐厨废弃油脂）来加工食用油的行为，有什么处罚依据？
    示例输出：INTENT: SEARCH | QUERY: 地沟油 餐厨废弃油脂 食用油 处罚
    
    只输出一行结果，格式必须为 INTENT: [类别] | QUERY: [关键词]。
    """
    response = llm.invoke(prompt).content.strip()
    
    intent = "SEARCH"
    rewritten_query = user_query
    
    try:
        parts = response.split("|")
        intent = parts[0].replace("INTENT:", "").strip()
        rewritten_query = parts[1].replace("QUERY:", "").strip()
    except Exception:
        pass 
        
    return {"intent": intent, "rewritten_query": rewritten_query}
# =====================================================================
# ---------------------- 3. 混合检索与 RRF 融合 ----------------------
# =====================================================================
def rrf_fusion(vector_hits, meili_hits, graph_hits, k=60):
    """使用 RRF (倒数排名融合) 算法对三路召回结果进行打分融合"""
    rrf_scores = {}
    docs_map = {} # uuid -> 具体的文档内容和元数据
    
    # 处理向量召回
    for rank, (doc, score) in enumerate(vector_hits):
        uuid = doc.metadata.get('doc_uuid', f"vec_{rank}")
        rrf_scores[uuid] = rrf_scores.get(uuid, 0.0) + 1.0 / (k + rank + 1)
        docs_map[uuid] = {
            "content": doc.page_content,
            "title": doc.metadata.get('title', '未知'),
            "question": "",
            "answer": doc.page_content,
            "source": "ChromaDB"
        }

    # 处理 Meilisearch 关键词召回
    for rank, hit in enumerate(meili_hits):
        uuid = hit.get('doc_uuid', f"mei_{rank}")
        rrf_scores[uuid] = rrf_scores.get(uuid, 0.0) + 1.0 / (k + rank + 1)
        docs_map[uuid] = {
            "content": hit.get('chunk_text', ''),
            "title": hit.get('title', '未知'),
            "question": hit.get('question', ''),
            "answer": hit.get('answer', hit.get('chunk_text', '')),
            "source": "local_meili_demo.json"
        }
        
    # 处理 Neo4j 图数据库召回
    for rank, hit in enumerate(graph_hits):
        uuid = hit.get('uuid', f"neo_{rank}")
        rrf_scores[uuid] = rrf_scores.get(uuid, 0.0) + 1.0 / (k + rank + 1)
        docs_map[uuid] = {
            "content": hit.get('content', ''),
            "title": hit.get('title', '未知'),
            "question": hit.get('question', ''),
            "answer": hit.get('answer', hit.get('content', '')),
            "source": "Neo4j"
        }

    # 按 RRF 分数排序，截取 Top 15 进入重排
    sorted_uuids = sorted(rrf_scores.keys(), key=lambda x: rrf_scores[x], reverse=True)
    return [docs_map[uid] for uid in sorted_uuids[:15]]


def hybrid_retrieval(query: str) -> list:
    print(f"\n🔍 [检索开始] 核心搜索词: {query}")
    
    # 1. 向量库召回 (Chroma)
    vector_results = vectorstore.similarity_search_with_score(query, k=10)
    print(f"   🟢 ChromaDB 语义召回数量: {len(vector_results)} 条")
    
    # 2. 关键词库召回 (Meilisearch)
    meili_results = []
    try:
        search_res = meili_index.search(query, {'limit': 10})
        meili_results = search_res.get('hits', [])
        print(f"   🟡 Meilisearch 关键词召回: {len(meili_results)} 条")
    except Exception as e:
        print(f"   🟡 Meilisearch 检索异常: {e}")
        
    # 3. 跳过知识图谱召回（按用户要求），只使用向量与关键词检索
    graph_results = []
    print("   🟣 已跳过 Neo4j 图谱实体召回（按配置）。")

    # 4. RRF 融合
    fused_docs = rrf_fusion(vector_results, meili_results, graph_results)
    print(f"   🤝 RRF 融合去重后，剩余候选文档: {len(fused_docs)} 条")
    return fused_docs


# =====================================================================
# ---------------------- 4. 重排模型 (Rerank) 与截断 ----------------------
# =====================================================================
def rerank_and_truncate(query: str, fused_docs: list, top_n: int = 3) -> str:
    """使用阿里 gte-rerank 模型进行精准重排"""
    if not fused_docs:
        return ""
        
    documents = [doc["content"] for doc in fused_docs]
    
    try:
        resp = dashscope.TextReRank.call(
            model=dashscope.TextReRank.Models.gte_rerank,
            query=query,
            documents=documents,
            top_n=top_n,
            return_documents=True
        )
        
        if resp.status_code == 200:
            final_context = ""
            # 获取重排后的最佳结果，并附加上原文引用的 metadata
            for item in resp.output.results:
                original_idx = item['index']
                matched_doc = fused_docs[original_idx]
                title = matched_doc['title']
                text = matched_doc['content']
                final_context += f"\n\n【引用来源: {title}】\n{text}"
            return final_context.strip()
            
    except Exception as e:
        print(f"⚠️ Rerank 失败，退回直接截断: {e}")
        
    # 兜底逻辑：如果 Rerank 接口挂了，直接取 RRF 的前 Top N
    final_context = ""
    for doc in fused_docs[:top_n]:
        final_context += f"\n\n【引用来源: {doc['title']}】\n{doc['content']}"
    return final_context.strip()


def build_direct_search_reply(query: str, docs: list, max_docs: int = 3) -> str:
    if not docs:
        return "未在本地知识库（local_meili_demo.json）中检索到相关内容。"

    # 基于 query 做简单过滤：要求 query 中的任一 token 出现在 question 或 answer 中
    q = str(query or "").strip()
    tokens = [t.lower() for t in q.replace('　', ' ').split() if t.strip()]

    def contains_token(text: str) -> bool:
        if not text:
            return False
        lt = text.lower()
        return any(tok in lt for tok in tokens) if tokens else True

    matched = [d for d in docs if contains_token(d.get('question', '')) or contains_token(d.get('answer', ''))]
    if not matched:
        # 如果没有匹配项，则保留原始结果（避免空返回），但标注可能不相关
        matched = docs

    def excerpt(text: str, limit: int = 300) -> str:
        if not text:
            return ""
        s = ' '.join(str(text).split())
        return s if len(s) <= limit else s[:limit].rstrip() + '...'

    lines = [f"根据本地知识库检索到 {min(len(matched), max_docs)} 条相关结果："]
    for idx, doc in enumerate(matched[:max_docs], start=1):
        title = doc.get("title", "本地知识库")
        question = str(doc.get('question', '')).strip()
        answer = str(doc.get("answer", doc.get("chunk_text", ""))).strip()
        source = doc.get("source", "local_meili_demo.json")

        q_ex = excerpt(question, limit=240)
        a_ex = excerpt(answer, limit=800)

        lines.append(f"\n{idx}. 来源：{title}")
        if q_ex:
            lines.append(f"问（节选）：{q_ex}")
        if a_ex:
            lines.append(f"答：{a_ex}")
        lines.append(f"引用：{source}")

    return "\n".join(lines)


def local_search(query: str, limit: int = 3) -> list:
    try:
        search_res = meili_index.search(query, {'limit': limit})
        hits = search_res.get('hits', [])
        for hit in hits:
            hit['source'] = hit.get('source', 'local_meili_demo.json')
            hit['answer'] = str(hit.get('answer', hit.get('chunk_text', ''))).strip()
        return hits
    except Exception:
        return []


# =====================================================================
# ---------------------- 5. Agent 核心执行流 ----------------------
# =====================================================================
class LegalRAGAgent:
    def __init__(self, llm):
        self.llm = llm

    async def astream(self, user_input: str, history_text: str = ""):
        # Step 1: 意图识别与查询重写
        analysis = analyze_and_rewrite_query(user_input, history_text)
        print(f"🧠 意图识别: {analysis}")

        if analysis["intent"] == "CHAT":
            yield (
                "我是本地知识库顾问，仅基于 local_meili_demo.json 中的检索结果提供回答。"
                "\n请直接输入您想查询的具体问题。"
            )
            return

        # Step 2: 仅使用本地 JSONL / local_meili_demo.json 检索结果（只取第一条）
        search_query = analysis["rewritten_query"]
        local_hits = local_search(search_query, limit=1)

        # 直接根据本地检索结果输出，且只返回第1条，避免模型生成幻觉内容
        reply_text = build_direct_search_reply(search_query, local_hits, max_docs=1)
        yield reply_text

agent = LegalRAGAgent(llm)


# =====================================================================
# ---------------------- 6. FastAPI 路由与前端 CRUD 同步 ----------------------
# =====================================================================
app = FastAPI()
app.add_middleware(CORSMiddleware, allow_origins=["*"], allow_methods=["*"], allow_headers=["*"])

class ChatRequest(BaseModel):
    user_input: str
    session_id: str

class IngestRequest(BaseModel):
    uuid: str
    title: str
    content: str

class DeleteRequest(BaseModel):
    uuid: str

@app.post("/api/chat")
async def chat(chat_request: ChatRequest):
    user_input = chat_request.user_input
    session_id = chat_request.session_id
    
    log_dir = "./chat_histories"
    if not os.path.exists(log_dir): os.makedirs(log_dir)
    history = FileChatMessageHistory(f"{log_dir}/history_{session_id}.json")
    
    # 提取最近3轮对话作为上下文
    recent_msgs = history.messages[-6:] if len(history.messages) > 6 else history.messages
    history_text = "\n".join([f"{'用户' if isinstance(m, HumanMessage) else 'AI'}: {m.content}" for m in recent_msgs])

    async def generate():
        full_res = ""
        async for chunk in agent.astream(user_input, history_text):
            if chunk:
                full_res += chunk
                # 按 SSE 规范逐行发送，避免前端只显示首行的问题
                for line in str(chunk).splitlines():
                    yield f"data: {line}\n"
                yield "\n"
        
        if full_res:
            history.add_message(HumanMessage(content=user_input))
            history.add_message(AIMessage(content=full_res))
        yield "data: [DONE]\n\n"

    return StreamingResponse(generate(), media_type="text-event-stream")

# --- 前端同步：新增/更新数据 ---
@app.post("/api/ingest")
async def ingest_article(request: IngestRequest):
    """前端新增或修改 MySQL 后，调用此接口同步至 ChromaDB 和 Meilisearch"""
    try:
        # 1. 存入 ChromaDB
        vectorstore.add_texts(
            texts=[request.content],
            metadatas=[{"title": request.title, "doc_uuid": request.uuid}],
            ids=[request.uuid]  # 以 uuid 为 ID 方便后续删除/覆盖
        )
        
        # 2. 存入 Meilisearch
        meili_index.add_documents([{
            "id": request.uuid.replace("-", ""), # Meili ID 不支持横杠
            "doc_uuid": request.uuid,
            "title": request.title,
            "chunk_text": request.content
        }])
        
        return {"status": "success", "message": "已同步至双路知识库."}
    except Exception as e:
        return JSONResponse(status_code=500, content={"status": "error", "message": str(e)})

# --- 前端同步：删除数据 ---
@app.post("/api/delete")
async def delete_article(request: DeleteRequest):
    """前端删除 MySQL 数据后，调用此接口从知识库中擦除"""
    try:
        # 1. 从 ChromaDB 删除
        vectorstore.delete(ids=[request.uuid])
        
        # 2. 从 Meilisearch 删除
        clean_uuid = request.uuid.replace("-", "")
        meili_index.delete_document(clean_uuid)
        
        return {"status": "success", "message": "已从知识库中彻底删除."}
    except Exception as e:
        return JSONResponse(status_code=500, content={"status": "error", "message": str(e)})

if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("AGENT_PORT", "8001"))
    uvicorn.run(app, host="0.0.0.0", port=port)