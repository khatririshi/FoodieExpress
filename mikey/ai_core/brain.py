import os
import json
import asyncio
from typing import List, Dict, Any, Optional
from openai import OpenAI

class MikeyBrain:
    """
    The central AI brain for Mikey, handling reasoning, intent understanding, 
    and model management.
    """
    def __init__(self, model_name: str = "gpt-4.1-mini"):
        # Using pre-configured OpenAI client in the sandbox
        self.client = OpenAI()
        self.model_name = model_name
        self.system_prompt = (
            "You are Mikey, an advanced autonomous AI desktop assistant inspired by JARVIS. "
            "Your goal is to be a helpful, intelligent, and proactive companion. "
            "You can control the computer, manage files, browse the web, and assist with coding. "
            "Always think step-by-step and provide clear, concise, and professional responses. "
            "You have access to various tools and modules to execute tasks."
        )

    async def think(self, user_input: str, context: Optional[Dict[str, Any]] = None) -> str:
        """
        Processes user input and returns a reasoned response.
        """
        messages = [
            {"role": "system", "content": self.system_prompt},
            {"role": "user", "content": user_input}
        ]
        
        if context:
            messages.insert(1, {"role": "system", "content": f"Current Context: {json.dumps(context)}"})

        try:
            response = self.client.chat.completions.create(
                model=self.model_name,
                messages=messages,
                temperature=0.7
            )
            return response.choices[0].message.content
        except Exception as e:
            return f"Error in reasoning: {str(e)}"

    async def analyze_intent(self, user_input: str) -> Dict[str, Any]:
        """
        Analyzes the user's intent and extracts key parameters.
        """
        prompt = (
            f"Analyze the following user input and determine the intent and parameters: '{user_input}'\n"
            "Return the result as a JSON object with 'intent' and 'parameters' keys."
        )
        
        response_text = await self.think(prompt)
        try:
            # Basic JSON extraction from response
            start = response_text.find('{')
            end = response_text.rfind('}') + 1
            return json.loads(response_text[start:end])
        except:
            return {"intent": "unknown", "parameters": {}}

if __name__ == "__main__":
    # Quick test
    brain = MikeyBrain()
    async def test():
        res = await brain.think("Hello Mikey, who are you?")
        print(res)
    asyncio.run(test())
