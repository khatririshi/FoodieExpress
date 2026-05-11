import psutil
from typing import Dict, Any

class SystemDiagnostics:
    """
    Monitors system health and performance.
    """
    def __init__(self):
        pass

    def get_system_stats(self) -> Dict[str, Any]:
        """
        Returns current CPU, memory, and battery statistics.
        """
        stats = {
            "cpu_usage": psutil.cpu_percent(interval=1),
            "memory_usage": psutil.virtual_memory().percent,
            "disk_usage": psutil.disk_usage('/').percent,
        }
        
        if hasattr(psutil, "sensors_battery"):
            battery = psutil.sensors_battery()
            if battery:
                stats["battery_percent"] = battery.percent
                stats["power_plugged"] = battery.power_plugged
        
        return stats

if __name__ == "__main__":
    diagnostics = SystemDiagnostics()
    print(diagnostics.get_system_stats())
