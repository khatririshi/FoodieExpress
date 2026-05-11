import pyautogui
from typing import Tuple

class DeviceController:
    """
    Controls keyboard and mouse input using pyautogui.
    """
    def __init__(self):
        pyautogui.FAILSAFE = True

    def move_mouse(self, x: int, y: int):
        pyautogui.moveTo(x, y, duration=0.25)

    def click(self, x: int = None, y: int = None, button: str = 'left'):
        pyautogui.click(x=x, y=y, button=button)

    def type_text(self, text: str):
        pyautogui.write(text, interval=0.1)

    def press_key(self, key: str):
        pyautogui.press(key)

    def hotkey(self, *args):
        pyautogui.hotkey(*args)

    def get_screen_size(self) -> Tuple[int, int]:
        return pyautogui.size()

if __name__ == "__main__":
    controller = DeviceController()
    print(f"Screen size: {controller.get_screen_size()}")
    # controller.move_mouse(100, 100)
