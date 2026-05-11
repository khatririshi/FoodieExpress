# Mikey: Autonomous AI Desktop Assistant

![Mikey Logo](https://via.placeholder.com/400x200.png?text=Mikey+AI+Assistant)

## Introduction

"Mikey" is an advanced autonomous AI desktop assistant inspired by JARVIS from Avengers. It is designed to be a real intelligent operating-system companion, capable of understanding and executing a wide range of tasks dynamically using free and open-source technologies.

## Features

Mikey is built as a powerful AI agent capable of:

*   **Autonomous Reasoning**: Intelligently processes information and makes decisions.
*   **Action Execution**: Performs tasks across the operating system.
*   **Self-Planning**: Breaks down complex goals into actionable steps.
*   **Intelligent Automation**: Automates workflows and repetitive tasks.
*   **Adaptive Learning**: Continuously improves based on user interactions and feedback.
*   **Contextual Understanding**: Maintains context across conversations and tasks.
*   **Tool Selection**: Automatically selects and utilizes appropriate tools for tasks.
*   **Workflow Orchestration**: Manages and coordinates multi-step processes.

## Technology Stack

Mikey leverages a variety of free and open-source technologies:

*   **AI Brain**: Ollama, DeepSeek, Llama 3, Mistral, Phi, Qwen (local AI inference preferred).
*   **Voice Recognition**: Faster-Whisper, Vosk (offline speech-to-text).
*   **Wake Word Detection**: openWakeWord, Porcupine (free tier).
*   **Text-to-Speech**: pyttsx3, Piper TTS (high-quality offline voice).
*   **Automation & Device Control**: pyautogui, keyboard, mouse, psutil, watchdog, Playwright, Selenium, Win32 APIs, pycaw, pygetwindow.
*   **Backend**: Python, FastAPI, asyncio, WebSockets.
*   **Memory System**: SQLite, ChromaDB or FAISS (for vector memory).
*   **UI**: PyQt6 (futuristic JARVIS-style interface).
*   **Vision & Screen Understanding**: Tesseract (OCR).

## Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/khatririshi/FoodieExpress/mikey.git
    cd mikey
    ```

2.  **Install dependencies:**
    ```bash
    pip install -r requirements.txt
    ```

3.  **Run Mikey:**
    ```bash
    python main.py
    ```

## Usage

Once Mikey is running, it will listen for the wake word "Hey Mikey". Upon detection, you can issue commands such as:

*   "Hey Mikey, organize my desktop"
*   "Hey Mikey, summarize this PDF"
*   "Hey Mikey, clean unnecessary files"
*   "Hey Mikey, open Chrome and search AI news"

## Project Structure

```
mikey/
├── ai_core/
├── wakeword/
├── speech_engine/
├── automation/
├── planner/
├── memory/
├── device_control/
├── ui/
├── plugins/
├── security/
├── logs/
├── integrations/
├── vision/
├── developer_tools/
├── workflows/
├── diagnostics/
├── __init__.py
├── main.py
├── config.py
├── requirements.txt
└── README.md
```

## Contributing

We welcome contributions! Please see `CONTRIBUTING.md` (to be added) for details on how to contribute.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
