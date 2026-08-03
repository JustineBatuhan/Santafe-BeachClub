from flask import Flask, jsonify
from flask_cors import CORS
import mysql.connector

app = Flask(__name__)
# CORS allows your PHP frontend (e.g., localhost:80) to request data from Python (localhost:5000)
CORS(app) 

# Update these details with your actual database credentials
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'santafe_beach_club',  # your actual database name
    'port': 3307                       # your MySQL port
}

def get_db_connection():
    return mysql.connector.connect(**DB_CONFIG)

@app.route('/api/accommodation-popularity')
def accommodation_popularity():
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # Pulling the same booking counts by accommodation name used in your PHP file
        cursor.execute("""
            SELECT accommodation_name, COUNT(id) as booking_count 
            FROM bookings 
            GROUP BY accommodation_name 
            ORDER BY booking_count DESC
        """)
        
        data = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return jsonify(data)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    # Runs the Python API on port 5000
    app.run(port=5000, debug=True)