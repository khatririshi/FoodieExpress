import json
import asyncio
from typing import List, Dict, Any
from mikey.ai_core.brain import MikeyBrain

class TaskPlanner:
    """
    Breaks down complex tasks into a sequence of executable steps.
    """
    def __init__(self, brain: MikeyBrain):
        self.brain = brain

    async def plan_task(self, task_description: str) -> List[Dict[str, Any]]:
        """
        Generates a plan for a given task description.
        """
        prompt = (
            f"Create a step-by-step plan to execute the following task: '{task_description}'\n"
            "Each step should include a 'description' and a 'tool' to be used (if applicable).\n"
            "Return the plan as a JSON list of objects."
        )
        
        response_text = await self.brain.think(prompt)
        try:
            start = response_text.find('[')
            end = response_text.rfind(']') + 1
            return json.loads(response_text[start:end])
        except:
            return [{"description": "Analyze task further", "tool": "brain"}]

if __name__ == "__main__":
    # Quick test
    brain = MikeyBrain()
    planner = TaskPlanner(brain)
    async def test():
        plan = await planner.plan_task("Organize my desktop and backup important documents.")
        print(json.dumps(plan, indent=2))
    asyncio.run(test())
