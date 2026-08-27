import cv2
from pyzbar.pyzbar import decode
import time
from urllib.parse import urlparse, parse_qs
import numpy as np
from http.server import BaseHTTPRequestHandler, HTTPServer
import threading
import json
import os

# Suppress OpenCV camera warnings
os.environ["OPENCV_LOG_LEVEL"] = "SILENT"

# --- Configuration ---
ADMIN_URL = "http://localhost/SantaBeachClub-BookingSystem/frontend/admin_checkin"
WINDOW_NAME = "Santa Fe Beach Club - QR Scanner"

# Global variable to hold the last scanned data
last_scanned_data = None
shutdown_requested = False

# --- Local Server so the website can talk to Python ---
class ScanHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        global last_scanned_data, shutdown_requested
        
        if self.path == '/shutdown':
            shutdown_requested = True
            self.send_response(200)
            self.send_header('Access-Control-Allow-Origin', '*')
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(b'{"status": "shutting down"}')
            return

        self.send_response(200)
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Content-type', 'application/json')
        self.end_headers()
        
        response = last_scanned_data if last_scanned_data else {}
        self.wfile.write(json.dumps(response).encode())
        
        # Clear after sending and request shutdown if we sent real data
        if last_scanned_data:
            last_scanned_data = None
            shutdown_requested = True
            
    def log_message(self, format, *args):
        pass # Hide server logs

def start_server():
    try:
        server = HTTPServer(('localhost', 8765), ScanHandler)
        server.serve_forever()
    except Exception as e:
        # Port might be in use if already running, that's okay
        pass

threading.Thread(target=start_server, daemon=True).start()

def find_working_camera():
    for cam_id in range(6):
        cap = cv2.VideoCapture(cam_id)
        if cap.isOpened():
            for _ in range(5):
                ret, frame = cap.read()
            if ret and frame is not None:
                if np.mean(frame) > 2.0:
                    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
                    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
                    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
                    # Enable autofocus (if camera supports it)
                    cap.set(cv2.CAP_PROP_AUTOFOCUS, 1)
                    return cap, cam_id
            cap.release()
    return None, 0

def setup_window(window_name):
    """Remove title bar and pin always-on-top (Windows only)."""
    try:
        import ctypes
        from ctypes import wintypes
        
        time.sleep(0.3)
        hwnd = ctypes.windll.user32.FindWindowW(None, window_name)
        if hwnd:
            # Remove title bar: strip WS_CAPTION and WS_THICKFRAME, add WS_POPUP
            GWL_STYLE = -16
            WS_POPUP = 0x80000000
            WS_VISIBLE = 0x10000000
            ctypes.windll.user32.SetWindowLongW(hwnd, GWL_STYLE, WS_POPUP | WS_VISIBLE)
            
            # Pin always on top and reposition
            screen_w = ctypes.windll.user32.GetSystemMetrics(0)
            screen_h = ctypes.windll.user32.GetSystemMetrics(1)
            win_w, win_h = 340, 260
            x = screen_w - win_w - 15
            y = screen_h - win_h - 50
            HWND_TOPMOST = -1
            SWP_SHOWWINDOW = 0x0040
            ctypes.windll.user32.SetWindowPos(hwnd, HWND_TOPMOST, x, y, win_w, win_h, SWP_SHOWWINDOW)
    except:
        pass

def draw_scan_overlay(frame):
    """Draw a stylish scanning frame overlay on the camera feed."""
    h, w = frame.shape[:2]
    
    # Semi-transparent dark border around edges
    overlay = frame.copy()
    border = 40
    cv2.rectangle(overlay, (0, 0), (w, border), (30, 30, 30), -1)          # top
    cv2.rectangle(overlay, (0, h - border), (w, h), (30, 30, 30), -1)      # bottom
    cv2.rectangle(overlay, (0, 0), (border, h), (30, 30, 30), -1)          # left
    cv2.rectangle(overlay, (w - border, 0), (w, h), (30, 30, 30), -1)      # right
    cv2.addWeighted(overlay, 0.5, frame, 0.5, 0, frame)
    
    # Corner brackets (green)
    color = (0, 220, 120)
    thickness = 3
    length = 40
    margin = border
    
    # Top-left
    cv2.line(frame, (margin, margin), (margin + length, margin), color, thickness)
    cv2.line(frame, (margin, margin), (margin, margin + length), color, thickness)
    # Top-right
    cv2.line(frame, (w - margin, margin), (w - margin - length, margin), color, thickness)
    cv2.line(frame, (w - margin, margin), (w - margin, margin + length), color, thickness)
    # Bottom-left
    cv2.line(frame, (margin, h - margin), (margin + length, h - margin), color, thickness)
    cv2.line(frame, (margin, h - margin), (margin, h - margin - length), color, thickness)
    # Bottom-right
    cv2.line(frame, (w - margin, h - margin), (w - margin - length, h - margin), color, thickness)
    cv2.line(frame, (w - margin, h - margin), (w - margin, h - margin - length), color, thickness)
    
    # Label at the bottom
    label = "Scan QR Code"
    font = cv2.FONT_HERSHEY_SIMPLEX
    font_scale = 0.5
    text_size = cv2.getTextSize(label, font, font_scale, 1)[0]
    text_x = (w - text_size[0]) // 2
    text_y = h - 12
    cv2.putText(frame, label, (text_x, text_y), font, font_scale, (255, 255, 255), 1, cv2.LINE_AA)
    
    return frame

def main():
    global last_scanned_data, shutdown_requested
    
    cap, current_cam_id = find_working_camera()
    
    if cap is None:
        return

    # Create window
    cv2.namedWindow(WINDOW_NAME, cv2.WINDOW_NORMAL)
    cv2.resizeWindow(WINDOW_NAME, 340, 260)
    
    # Move to bottom-right temporarily (setup_window will reposition properly)
    try:
        import ctypes
        screen_w = ctypes.windll.user32.GetSystemMetrics(0)
        screen_h = ctypes.windll.user32.GetSystemMetrics(1)
        cv2.moveWindow(WINDOW_NAME, screen_w - 360, screen_h - 330)
    except:
        cv2.moveWindow(WINDOW_NAME, 1580, 770)

    last_scan_time = 0
    frame_count = 0
    window_setup_done = False
    
    while True:
        ret, frame = cap.read()
        frame_count += 1
        
        if not ret or frame is None:
            time.sleep(0.1)
            continue
        
        # Draw the scan overlay on the frame
        frame = draw_scan_overlay(frame)
            
        # Show frame with overlay
        cv2.imshow(WINDOW_NAME, frame)
        key = cv2.waitKey(1) & 0xFF
        if key == ord('q'):
            break
        
        # Remove title bar and pin on top after first frame
        if not window_setup_done:
            window_setup_done = True
            threading.Thread(target=setup_window, args=(WINDOW_NAME,), daemon=True).start()
            
        # But only decode every other frame for speed
        if frame_count % 2 != 0:
            continue
            
        # Grayscale for faster pyzbar decoding
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        decoded_objects = decode(gray)
        
        for obj in decoded_objects:
            qr_data = obj.data.decode('utf-8')
            
            # Draw green box around QR on the preview
            points = obj.polygon
            if len(points) == 4:
                pts = np.array([tuple(p) for p in points], dtype=np.int32)
                cv2.polylines(frame, [pts], True, (0, 255, 0), 3)
                cv2.imshow(WINDOW_NAME, frame)
                cv2.waitKey(1)
            
            current_time = time.time()
            if current_time - last_scan_time > 3.0:
                last_scan_time = current_time
                try:
                    parsed = urlparse(qr_data)
                    query_params = parse_qs(parsed.query)
                    ref = query_params.get('ref', [''])[0]
                    token = query_params.get('token', [''])[0]
                    
                    if ref and token:
                        last_scanned_data = {"ref": ref, "token": token}
                except Exception as e:
                    pass
                    
        if shutdown_requested:
            time.sleep(0.5)
            break

    if cap: cap.release()
    cv2.destroyAllWindows()

if __name__ == '__main__':
    main()
