import sqlite3
import os
from typing import List, Dict, Any, Optional

class SemanticMemory:
    """
    Manages long-term semantic memory using SQLite.
    """
    def __init__(self, db_path: str = "mikey_memory.db"):
        self.db_path = db_path
        self._init_db()

    def _init_db(self):
        with sqlite3.connect(self.db_path) as conn:
            cursor = conn.cursor()
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS memory (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key TEXT UNIQUE,
                    value TEXT,
                    category TEXT,
                    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ''')
            conn.commit()

    def store(self, key: str, value: str, category: str = "general"):
        with sqlite3.connect(self.db_path) as conn:
            cursor = conn.cursor()
            cursor.execute('''
                INSERT OR REPLACE INTO memory (key, value, category)
                VALUES (?, ?, ?)
            ''', (key, value, category))
            conn.commit()

    def retrieve(self, key: str) -> Optional[str]:
        with sqlite3.connect(self.db_path) as conn:
            cursor = conn.cursor()
            cursor.execute('SELECT value FROM memory WHERE key = ?', (key,))
            result = cursor.fetchone()
            return result[0] if result else None

    def search_by_category(self, category: str) -> List[Dict[str, Any]]:
        with sqlite3.connect(self.db_path) as conn:
            cursor = conn.cursor()
            cursor.execute('SELECT key, value, timestamp FROM memory WHERE category = ?', (category,))
            rows = cursor.fetchall()
            return [{"key": row[0], "value": row[1], "timestamp": row[2]} for row in rows]

if __name__ == "__main__":
    # Quick test
    memory = SemanticMemory()
    memory.store("user_name", "Alice")
    print(f"Stored user_name: {memory.retrieve('user_name')}")
