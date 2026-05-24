# Law RAG — 法律智能问答系统

基于 **Laravel** 前端 + **FastAPI / LangChain** AI 后端的法律 RAG（检索增强生成）项目。  
支持向量检索（ChromaDB）、关键词检索（Meilisearch / 本地 JSON）、图数据库（Neo4j）多路召回，并通过 RRF 融合后由通义千问/千问大模型生成回答。

---

## 项目结构

```
law_rag/
├── agent_demo2/              # Python AI 后端（FastAPI）
│   ├── legal_agent.py        # 主服务：聊天 API、知识库同步
│   ├── build_knowledge_base.py   # 从 MySQL 构建 Chroma + Meilisearch 索引
│   ├── build_public_interactions.py
│   ├── database/             # 原始 JSONL 数据 & 导入脚本
│   ├── requirements.txt
│   └── .env.example          # 环境变量模板（复制为 .env 后填写）
│
└── law_RAG_agent/
    └── legal-web/            # Laravel 前端 + Filament 后台
        ├── app/
        ├── routes/
        ├── database/migrations/
        └── .env.example
```

---

## 环境要求

| 组件 | 版本建议 | 用途 |
|------|----------|------|
| PHP | 8.2+ | Laravel 网站 |
| Composer | 最新 | PHP 依赖 |
| Node.js | 18+ | 前端资源编译（可选） |
| Python | 3.10+ | AI 后端 |
| MySQL | 8.0+ | 业务数据库 `legal_db` |
| Meilisearch | 1.x | 全文检索（构建索引时使用，可选） |
| Neo4j Aura | 云实例 | 知识图谱检索（可选） |

---

## 一、首次安装

### 1. 克隆仓库

```bash
git clone https://github.com/<你的用户名>/law-rag.git
cd law-rag
```

### 2. 配置 Python 后端

```bash
cd agent_demo2
copy .env.example .env        # Windows
# cp .env.example .env        # macOS / Linux

# 编辑 .env，至少填写：
#   DASHSCOPE_API_KEY=你的阿里云百炼密钥
#   NEO4J_* 相关配置（如使用图数据库）

python -m venv venv
venv\Scripts\activate         # Windows
# source venv/bin/activate    # macOS / Linux

pip install -r requirements.txt
```

### 3. 配置 Laravel 前端

```bash
cd ../law_RAG_agent/legal-web
copy .env.example .env

# 编辑 .env，配置 MySQL：
#   DB_CONNECTION=mysql
#   DB_HOST=127.0.0.1
#   DB_PORT=3306
#   DB_DATABASE=legal_db
#   DB_USERNAME=root
#   DB_PASSWORD=你的密码
#
# AI 后端地址（默认即可）：
#   AGENT_API_URL=http://127.0.0.1:8001/api/chat

composer install
php artisan key:generate
php artisan migrate
npm install && npm run build   # 如需编译前端资源
```

### 4. 准备 MySQL 数据库

```sql
CREATE DATABASE legal_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

导入 JSONL 数据（可选，首次构建知识库前执行）：

```bash
cd agent_demo2
venv\Scripts\activate
python database/import_to_mysql.py
```

### 5. 构建向量 / 搜索索引（可选，首次或数据更新后）

确保 Meilisearch 已启动（见下方「启动服务」），然后：

```bash
python build_knowledge_base.py
python build_public_interactions.py
```

---

## 二、每次启动项目

需要 **同时运行多个服务**，建议开 3～4 个终端窗口：

### 终端 1 — MySQL

确保 MySQL 服务已启动，数据库 `legal_db` 可连接。

### 终端 2 — Meilisearch（构建索引时需要；运行时 AI 服务有本地 JSON 降级方案）

```bash
# 下载 Meilisearch 后执行（Windows 示例）
meilisearch --master-key "你的密钥" --http-addr 127.0.0.1:7700
```

`.env` 中的 `MEILI_KEY` 需与 `--master-key` 一致。

### 终端 3 — Python AI 后端（必须）

```bash
cd agent_demo2
venv\Scripts\activate
python legal_agent.py
```

默认监听 `http://127.0.0.1:8001`。  
可用 `python test.api.py` 测试流式接口是否正常。

### 终端 4 — Laravel 网站（必须）

```bash
cd law_RAG_agent/legal-web
php artisan serve
```

浏览器访问 `http://127.0.0.1:8000`，注册账号后即可使用 AI 聊天。  
Filament 管理后台路径通常为 `/admin`（需管理员账号）。

---

## 三、服务端口一览

| 服务 | 默认端口 | 说明 |
|------|----------|------|
| Laravel | 8000 | 用户界面 |
| FastAPI (legal_agent) | 8001 | AI 聊天 API |
| Meilisearch | 7700 | 全文检索 |
| MySQL | 3306 | 业务数据库 |

端口可在各自 `.env` 中修改，修改后需同步更新 `AGENT_API_URL`。

---

## 四、环境变量说明

### agent_demo2/.env

| 变量 | 必填 | 说明 |
|------|------|------|
| `DASHSCOPE_API_KEY` | ✅ | 阿里云百炼 API Key |
| `NEO4J_URL` | 可选 | Neo4j 连接地址 |
| `NEO4J_USERNAME` | 可选 | Neo4j 用户名 |
| `NEO4J_PASSWORD` | 可选 | Neo4j 密码 |
| `NEO4J_DATABASE` | 可选 | Neo4j 数据库名 |
| `AGENT_PORT` | 可选 | FastAPI 端口，默认 8001 |
| `MYSQL_*` | 构建索引时 | MySQL 连接信息 |
| `MEILI_*` | 构建索引时 | Meilisearch 地址与密钥 |

### law_RAG_agent/legal-web/.env

| 变量 | 必填 | 说明 |
|------|------|------|
| `DB_*` | ✅ | MySQL 连接 |
| `AGENT_API_URL` | ✅ | FastAPI 聊天接口完整 URL |

> ⚠️ **切勿**将 `.env` 文件提交到 Git。仓库中仅包含 `.env.example` 模板。

---

## 五、常见问题

**Q: 聊天没有回复？**  
确认终端 3 中 `legal_agent.py` 正在运行，且 Laravel `.env` 中 `AGENT_API_URL` 指向正确。

**Q: 提示 DASHSCOPE_API_KEY 未设置？**  
检查 `agent_demo2/.env` 是否已创建并填写密钥。

**Q: 向量检索无结果？**  
首次需运行 `build_knowledge_base.py` 生成 `chroma_db/` 目录（该目录已在 `.gitignore` 中，需本地构建）。

**Q: composer install 很慢？**  
可配置国内 Composer 镜像：`composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/`

---

## 六、技术栈

- **前端**：Laravel 11、Filament、Blade、Alpine.js
- **AI 后端**：FastAPI、LangChain、ChromaDB、DashScope（通义千问）
- **检索**：ChromaDB 向量检索 + Meilisearch 关键词 + Neo4j 图查询 + RRF 融合

---

## License

本项目仅供学习与交流使用。
