import pytesseract
from PIL import Image
import pyautogui

class VisionEngine:
    """
    Handles OCR and screen analysis using Tesseract and PIL.
    """
    def __init__(self):
        # Tesseract path might need to be configured depending on the OS
        # pytesseract.pytesseract.tesseract_cmd = r'/usr/bin/tesseract'
        pass

    def capture_screen(self, region=None) -> Image:
        """
        Captures a screenshot of the entire screen or a specific region.
        """
        return pyautogui.screenshot(region=region)

    def extract_text(self, image: Image) -> str:
        """
        Extracts text from an image using OCR.
        """
        return pytesseract.image_to_string(image)

    def find_text_on_screen(self, text: str) -> bool:
        """
        Checks if specific text is present on the screen.
        """
        screenshot = self.capture_screen()
        extracted_text = self.extract_text(screenshot)
        return text.lower() in extracted_text.lower()

if __name__ == "__main__":
    vision = VisionEngine()
    # screenshot = vision.capture_screen()
    # print(vision.extract_text(screenshot))
