# # from openai import OpenAI
# import os 
# from dotenv import load_dotenv
# x = load_dotenv()
# # print(x)
# # qianwen_key = os.getenv("qianwen_key")
# # print(qianwen_key)
# client = OpenAI(
#     api_key=os.getenv("DASHSCOPE_API_KEY"),
#     base_url="https://dashscope.aliyuncs.com/compatible-mode/v1"
# )

# completion = client.chat.completions.create(
#     model="qwen3.5-plus",
#     messages=[
#         {"role":"system","content":"你是一个Python编程专家，并且不说废话简单回答"},
#         {"role":"assistant","content":"好的，我是编程专家，并且话不多，你要问什么?"},
#         {"role":"user","content":"输出1-10的数字，使用python代码"}
#     ],
#     stream=True
# )



 
# for chunk in completion:
#     print(chunk.choices[0].delta.content,end="",flush=True)