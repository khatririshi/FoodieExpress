import sys
from PyQt6.QtWidgets import QApplication, QMainWindow, QVBoxLayout, QWidget, QLabel, QTextEdit, QPushButton
from PyQt6.QtCore import Qt, QTimer
from PyQt6.QtGui import QColor, QPalette, QFont

class MikeyUI(QMainWindow):
    """
    The main user interface for Mikey, featuring a futuristic JARVIS-style design.
    """
    def __init__(self):
        super().__init__()
        self.init_ui()

    def init_ui(self):
        self.setWindowTitle("Mikey - AI Assistant")
        self.setFixedSize(400, 600)
        
        # Set dark theme
        palette = self.palette()
        palette.setColor(QPalette.ColorRole.Window, QColor(10, 10, 10))
        palette.setColor(QPalette.ColorRole.WindowText, QColor(0, 255, 255))
        self.setPalette(palette)

        layout = QVBoxLayout()

        # Glowing Orb Placeholder
        self.orb_label = QLabel("●")
        self.orb_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.orb_label.setStyleSheet("color: #00ffff; font-size: 100px; text-shadow: 0 0 20px #00ffff;")
        layout.addWidget(self.orb_label)

        # Status Label
        self.status_label = QLabel("System Online")
        self.status_label.setAlignment(Qt.AlignmentFlag.AlignCenter)
        self.status_label.setStyleSheet("color: #00ffff; font-family: 'Courier New'; font-size: 18px;")
        layout.addWidget(self.status_label)

        # Chat Display
        self.chat_display = QTextEdit()
        self.chat_display.setReadOnly(True)
        self.chat_display.setStyleSheet(
            "background-color: rgba(20, 20, 20, 150); color: #00ffff; "
            "border: 1px solid #00ffff; font-family: 'Courier New';"
        )
        layout.addWidget(self.chat_display)

        # Action Button
        self.action_button = QPushButton("Awaiting Command...")
        self.action_button.setStyleSheet(
            "background-color: #00ffff; color: #000000; font-weight: bold; "
            "border-radius: 5px; padding: 10px;"
        )
        layout.addWidget(self.action_button)

        central_widget = QWidget()
        central_widget.setLayout(layout)
        self.setCentralWidget(central_widget)

        # Animation Timer
        self.timer = QTimer()
        self.timer.timeout.connect(self.animate_orb)
        self.timer.start(500)

    def animate_orb(self):
        # Simple pulse animation
        current_style = self.orb_label.styleSheet()
        if "font-size: 100px" in current_style:
            self.orb_label.setStyleSheet("color: #00ffff; font-size: 110px; text-shadow: 0 0 30px #00ffff;")
        else:
            self.orb_label.setStyleSheet("color: #00ffff; font-size: 100px; text-shadow: 0 0 20px #00ffff;")

    def update_chat(self, sender: str, message: str):
        self.chat_display.append(f"<b>{sender}:</b> {message}")

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MikeyUI()
    window.show()
    sys.exit(app.exec())
