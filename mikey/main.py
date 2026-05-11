import asyncio
import sys
from PyQt6.QtWidgets import QApplication
from mikey.ai_core.brain import MikeyBrain
from mikey.planner.task_planner import TaskPlanner
from mikey.speech_engine.stt import SpeechToText
from mikey.speech_engine.tts import TextToSpeech
from mikey.wakeword.detector import WakeWordDetector
from mikey.ui.main_window import MikeyUI
from mikey.automation.engine import AutomationEngine
from mikey.device_control.keyboard_mouse import DeviceController
from mikey.memory.semantic import SemanticMemory
from mikey.security.verifier import ActionVerifier
from mikey.diagnostics.system_health import SystemDiagnostics

class MikeyAssistant:
    """
    The main coordinator for the Mikey AI Assistant.
    """
    def __init__(self):
        self.brain = MikeyBrain()
        self.planner = TaskPlanner(self.brain)
        self.stt = SpeechToText()
        self.tts = TextToSpeech()
        self.wakeword = WakeWordDetector()
        self.automation = AutomationEngine()
        self.controller = DeviceController()
        self.memory = SemanticMemory()
        self.verifier = ActionVerifier()
        self.diagnostics = SystemDiagnostics()
        
        self.app = QApplication(sys.argv)
        self.ui = MikeyUI()

    async def run(self):
        """
        Starts the assistant's main loop.
        """
        self.ui.show()
        self.ui.update_chat("Mikey", "System Online. Awaiting your command.")
        self.tts.speak("System Online. How can I help you today?")
        
        # Start wake word detection in the background
        # In a real app, this would be a separate thread or async task
        # self.wakeword.start_listening(self.handle_wake)
        
        # For demonstration, we'll just run the UI event loop
        sys.exit(self.app.exec())

    def handle_wake(self):
        """
        Triggered when the wake word is detected.
        """
        self.ui.update_chat("System", "Wake word detected. Listening...")
        text = self.stt.listen()
        if text:
            asyncio.create_task(self.process_command(text))

    async def process_command(self, command: str):
        """
        Processes a user command.
        """
        self.ui.update_chat("User", command)
        
        # 1. Plan the task
        plan = await self.planner.plan_task(command)
        
        # 2. Execute steps (simplified for demo)
        for step in plan:
            self.ui.update_chat("Mikey", f"Executing: {step['description']}")
            # Actual execution logic would go here
            
        # 3. Respond
        response = await self.brain.think(command)
        self.ui.update_chat("Mikey", response)
        self.tts.speak(response)

if __name__ == "__main__":
    assistant = MikeyAssistant()
    asyncio.run(assistant.run())
