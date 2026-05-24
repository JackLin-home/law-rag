from langchain_community.llms.tongyi import Tongyi
from langchain_core.messages import HumanMessage,AIMessage,SystemMessage
import os 
from dotenv import load_dotenv
from langchain_core.prompts import PromptTemplate,FewShotPromptTemplate
load_dotenv()
api_key = os.getenv("qianwen_key")
model = Tongyi(dashscope_api_key=api_key,model="qwen3.5-plus")



#zero-shot 思想与链式输出

# my_template = PromptTemplate.from_template(
#     "我的猫叫{name}，刚生了{num}只小猫，你帮我给这几只小猫取个名字，简单一点。"
# )
# prompt_text = my_template.format(name="Jack",num="2")
# chain = my_template | model
# res = chain.invoke(input={"name":"jack","num":"2"})
# print(res)

#few-shot 思想与链式输出
# from langchain_core.prompts import PromptTemplate,FewShotPromptTemplate
my_template = PromptTemplate.from_template(
    "我的猫叫{name}，刚生了1只小猫，你帮我给这个小猫取个名字{name_cat}."
)

examples = [
        {"name":"Jack","name_cat":"Jack_cat"},
        {"name":"Tom","name_cat":"Tom_cat"}
    ]
few_shot_prompt = FewShotPromptTemplate(
        example_prompt=my_template,     #示例数据的模板
        examples=examples,              #示例数据 （用来注入到模板中的list内套字典）
        prefix="下面是一些关于猫的例子：",#示例之前的提示词（few-shot思想）
        suffix="请根据上面的例子，帮我给{name}的小猫取个名字",#示例之后的提示词（few-shot思想）
        input_variables=["name"]   #声明在前缀或后缀中需要注入的变量名
    )

prompt_text = few_shot_prompt.invoke(input={"name":"Lily"})
model_res = model.invoke(prompt_text)
print(model_res)