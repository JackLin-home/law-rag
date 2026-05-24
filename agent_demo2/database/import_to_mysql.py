import json
import os
import uuid
import pymysql

# ==========================================
# 1. 数据库配置区（请根据你的实际情况修改）
# ==========================================
DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",          # 你的 MySQL 用户名
    "password": "",  # 你的 MySQL 密码
    "database": "legal_db",  # 目标数据库
    "charset": "utf8mb4",    # 必须使用 utf8mb4 避免特殊字符报错
    "autocommit": False      # 关闭自动提交，改用事务批量提交，大幅提升效率
}

# ==========================================
# 2. 核心映射配置（文件名 -> 数据库表名 & 字段列表）
# ==========================================
DATA_MAPPER = {
    "办事指南.jsonl": {
        "table": "service_guides",
        "fields": [
            "title", "item_name", "subitem_name", "st_id", "guide_id",
            "application_materials", "rights_obligations", "handling_procedures",
            "establishment_basis", "faq", "approved_documents", "quantitative_restriction"
        ]
    },
    "法律条文.jsonl": {
        "table": "legal_articles",
        "fields": [
            "url", "title", "publish_date", "content", 
            "attachments", "crawled_at", "data_type", "source_module"
        ]
    },
    "投诉举报公示.jsonl": {
        "table": "complaint_publicities",
        "fields": [
            "enterprise_name", "city_code", "issue_type", "case_type", 
            "accept_dept", "reg_time", "end_time", "public_time", "process_result"
        ]
    },
    "行政处罚决定书.jsonl": {
        "table": "penalty_decisions",
        "fields": ["docid", "party_name", "penalty_authority", "penalty_type", "penalty_basis"]
    },
    "政策资讯.jsonl": {
        "table": "policy_insights",
        "fields": ["title", "content"]
    },
    "政民互动.jsonl": {
        "table": "public_interactions",
        "fields": [
            "title", "consult_id", "consult_category", "consult_time", 
            "reply_unit", "reply_time", "question", "answer"
        ]
    },
    "咨询问答.jsonl": {
        "table": "consult_faqs",
        "fields": ["title", "content", "consult_category"]
    }
}

def import_jsonl_to_mysql():
    # 连接数据库
    try:
        connection = pymysql.connect(**DB_CONFIG)
        cursor = connection.cursor()
        print("🚀 MySQL 数据库连接成功，开始导入数据...\n")
    except Exception as e:
        print(f"❌ 数据库连接失败，请检查配置! 错误信息: {e}")
        return

    # 遍历配置的 7 个文件进行清洗导入
    for filename, config in DATA_MAPPER.items():
        if not os.path.exists(filename):
            print(f"⚠️ 未找到文件 [{filename}]，已跳过。")
            continue

        table_name = config["table"]
        fields = config["fields"]
        
        # 动态拼接 SQL 语句
        # 每一张表都需要带上自动生成的 doc_uuid 字段
        all_columns = ["doc_uuid"] + fields
        placeholders = ", ".join(["%s"] * len(all_columns))
        sql = f"INSERT INTO {table_name} ({', '.join(all_columns)}) VALUES ({placeholders})"

        print(f"🔄 正在处理文件: {filename} -> 目标表: {table_name}")
        
        success_count = 0
        error_count = 0

        with open(filename, "r", encoding="utf-8") as f:
            for line_idx, line in enumerate(f, 1):
                line = line.strip()
                if not line:
                    continue
                
                try:
                    data = json.loads(line)
                    
                    # 1. 核心步骤：为当前行数据生成唯一的 RAG 检索 UUID
                    doc_uuid = str(uuid.uuid4()).replace("-", "")
                    
                    # 2. 提取字段值，若字段不存在则填充 None
                    row_values = [doc_uuid]
                    for field in fields:
                        val = data.get(field, None)
                        
                        # 3. 特殊处理：法律条文中的 attachments 字段是 list/dict，需要转成 JSON 字符串存储
                        if field == "attachments" and isinstance(val, (list, dict)):
                            val = json.dumps(val, ensure_ascii=False)
                            
                        row_values.append(val)

                    # 4. 执行单条写入
                    cursor.execute(sql, row_values)
                    success_count += 1

                except json.JSONDecodeError:
                    print(f"  ❌ 第 {line_idx} 行 JSON 格式损坏，跳过该行。")
                    error_count += 1
                except Exception as e:
                    print(f"  ❌ 第 {line_idx} 行写入数据库失败: {e}")
                    error_count += 1

        # 5. 整个文件处理完后，一次性提交事务（大幅提升磁盘写入效率）
        try:
            connection.commit()
            print(f"  ✅ {filename} 导入完成！成功: {success_count} 条, 失败: {error_count} 条\n")
        except Exception as e:
            connection.rollback()
            print(f"  ❌ {filename} 提交事务失败，已回滚！错误: {e}\n")

    # 关闭连接
    cursor.close()
    connection.close()
    print("🏁 所有数据集处理完毕，数据库连接已安全关闭。")

if __name__ == "__main__":
    import_jsonl_to_mysql()