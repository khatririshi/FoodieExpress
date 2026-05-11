import os
import subprocess
from typing import List, Dict, Any

class AutomationEngine:
    """
    Executes various automation tasks like file management, 
    system commands, and app control.
    """
    def __init__(self):
        pass

    def execute_command(self, command: str) -> str:
        """
        Executes a shell command and returns the output.
        """
        try:
            result = subprocess.run(command, shell=True, capture_output=True, text=True)
            return result.stdout if result.returncode == 0 else result.stderr
        except Exception as e:
            return f"Error executing command: {str(e)}"

    def manage_files(self, action: str, path: str, destination: str = None):
        """
        Performs file management actions.
        """
        if action == "create_dir":
            os.makedirs(path, exist_ok=True)
        elif action == "delete":
            if os.path.isfile(path):
                os.remove(path)
            elif os.path.isdir(path):
                os.rmdir(path)
        elif action == "move" and destination:
            os.rename(path, destination)
        # Add more actions as needed

if __name__ == "__main__":
    engine = AutomationEngine()
    print(engine.execute_command("ls -l"))
