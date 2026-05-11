from typing import Callable

class ActionVerifier:
    """
    Ensures that critical actions are confirmed by the user before execution.
    """
    def __init__(self):
        self.critical_actions = [
            "delete_file", "format_drive", "shutdown_system", 
            "send_email", "modify_registry", "admin_command"
        ]

    def verify(self, action: str, details: str, confirm_func: Callable[[str], bool]) -> bool:
        """
        Verifies if an action should proceed.
        """
        if action in self.critical_actions:
            print(f"CRITICAL ACTION DETECTED: {action} ({details})")
            return confirm_func(f"Do you want to proceed with: {action}?")
        return True

if __name__ == "__main__":
    verifier = ActionVerifier()
    
    def mock_confirm(prompt: str) -> bool:
        print(f"Prompt: {prompt}")
        return True # Simulate user saying yes

    print(f"Verification result: {verifier.verify('delete_file', 'important_doc.pdf', mock_confirm)}")
