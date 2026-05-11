import time
from typing import Callable, Optional

class WakeWordDetector:
    """
    Listens for the wake word "Hey Mikey" using openWakeWord or Porcupine.
    (Placeholder implementation for the sandbox environment)
    """
    def __init__(self, wake_word: str = "Hey Mikey"):
        self.wake_word = wake_word
        self.is_listening = False

    def start_listening(self, callback: Callable[[], None]):
        """
        Starts listening for the wake word in a loop.
        """
        self.is_listening = True
        print(f"Listening for wake word: '{self.wake_word}'...")
        
        # In a real implementation, this would be a continuous loop 
        # monitoring audio input.
        while self.is_listening:
            # Simulate detection for demonstration purposes
            # time.sleep(10) 
            # callback()
            break # Exit loop for placeholder

    def stop_listening(self):
        """
        Stops listening for the wake word.
        """
        self.is_listening = False
        print("Stopped listening for wake word.")

if __name__ == "__main__":
    def on_wake():
        print("Wake word detected!")

    detector = WakeWordDetector()
    detector.start_listening(on_wake)
