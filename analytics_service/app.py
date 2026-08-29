"""
analytics_service/app.py
Python Flask Analytics Microservice for Santa Fe Beach Club
Replicates all endpoints from analytics_api.php
Run with: python app.py
"""

from flask import Flask, jsonify
from flask_cors import CORS
import mysql.connector
from datetime import date, timedelta, datetime
import calendar

app = Flask(__name__)
CORS(app)  # Allow PHP proxy to call this service

# ── Database Configuration ─────────────────────────────────────────────────────
DB_CONFIG = {
    'host': 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com',
    'port': 4000,
    'user': '3QXoXQuJo9As2Sx.root',
    'password': 'ZCu3jpuVMLl4B2Vf',
    'database': 'test',
    'use_pure': True,
    'autocommit': True,
}

LOCAL_DB_CONFIG = {
    'host': '127.0.0.1',
    'port': 3307,
    'user': 'root',
    'password': '',
    'database': 'santafe_beach_club',
    'autocommit': True,
}

def get_db():
    try:
        return mysql.connector.connect(**DB_CONFIG)
    except Exception:
        return mysql.connector.connect(**LOCAL_DB_CONFIG)

def fetchone(cursor, query, params=()):
    cursor.execute(query, params)
    return cursor.fetchone()


# ── Helper: live room occupancy ───────────────────────────────────────────────
def get_checked_in_count(cursor):
    return fetchone(cursor,
        "SELECT COUNT(DISTINCT room_id) FROM bookings WHERE status='Checked In' AND room_id IS NOT NULL"
    )[0]

def get_reserved_count(cursor):
    return fetchone(cursor,
        "SELECT COUNT(DISTINCT room_id) FROM bookings WHERE status='Pending' AND room_id IS NOT NULL"
    )[0]


# ── 1. Executive Stats & KPIs ─────────────────────────────────────────────────
@app.route('/api/executive-stats')
def executive_stats():
    conn = get_db()
    cursor = conn.cursor()

    daily_rev    = fetchone(cursor, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND DATE(paid_at)=CURDATE()")[0]
    weekly_rev   = fetchone(cursor, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND paid_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY)")[0]
    total_rooms  = fetchone(cursor, "SELECT COUNT(*) FROM rooms")[0]
    occupied     = get_checked_in_count(cursor)
    reserved     = get_reserved_count(cursor)
    occ_rate     = f"{round((occupied/total_rooms)*100,1)}%" if total_rooms > 0 else "0%"
    total_bk     = fetchone(cursor, "SELECT COUNT(*) FROM bookings")[0]
    pending_bk   = fetchone(cursor, "SELECT COUNT(*) FROM bookings WHERE status='Pending'")[0]
    pending_pay  = fetchone(cursor, "SELECT COUNT(*) FROM payments WHERE status='pending'")[0]
    cin_today    = fetchone(cursor, "SELECT COUNT(*) FROM bookings WHERE status='Pending' AND DATE(check_in)=CURDATE()")[0]
    cout_today   = fetchone(cursor, "SELECT COUNT(*) FROM bookings WHERE status='Checked In' AND DATE(check_out)=CURDATE()")[0]

    cursor.close(); conn.close()
    return jsonify({
        'daily_revenue':    float(daily_rev),
        'weekly_revenue':   float(weekly_rev),
        'occupancy_rate':   occ_rate,
        'occupied_rooms':   occupied,
        'total_rooms':      total_rooms,
        'total_bookings':   total_bk,
        'pending_bookings': pending_bk,
        'reserved_rooms':   reserved,
        'pending_payments': pending_pay,
        'checkins_today':   cin_today,
        'checkouts_today':  cout_today,
    })


# ── 2. Weekly Revenue Trajectory ─────────────────────────────────────────────
@app.route('/api/weekly-revenue-trajectory')
def weekly_revenue_trajectory():
    conn = get_db()
    cursor = conn.cursor()
    labels, data = [], []
    for i in range(6, -1, -1):
        d = date.today() - timedelta(days=i)
        labels.append(d.strftime('%a'))
        row = fetchone(cursor,
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND DATE(paid_at)=%s",
            (d.isoformat(),))
        data.append(float(row[0]))
    cursor.close(); conn.close()
    return jsonify({'labels': labels, 'data': data})


# ── 3. Status Breakdown ───────────────────────────────────────────────────────
@app.route('/api/status-breakdown')
def status_breakdown():
    conn = get_db()
    cursor = conn.cursor()
    breakdown = {'Checked In': 0, 'Checked Out': 0, 'Pending': 0, 'Cancelled': 0}
    cursor.execute("SELECT status, COUNT(*) FROM bookings GROUP BY status")
    for status, count in cursor.fetchall():
        if status in breakdown:
            breakdown[status] = count
    cursor.close(); conn.close()
    return jsonify(breakdown)


# ── 4. Room Type Occupancy ────────────────────────────────────────────────────
@app.route('/api/room-type-occupancy')
def room_type_occupancy():
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT name, total_rooms FROM room_types ORDER BY id ASC")
    room_types = cursor.fetchall()
    labels, data = [], []
    for (type_name, total) in room_types:
        occ = fetchone(cursor,
            "SELECT COUNT(*) FROM bookings b JOIN rooms r ON b.room_id=r.id WHERE b.status='Checked In' AND r.type=%s",
            (type_name,))[0]
        pct = round((occ / total) * 100) if total > 0 else 0
        labels.append(type_name.replace('_', ' ').title())
        data.append(pct)
    cursor.close(); conn.close()
    return jsonify({'labels': labels, 'data': data})


# ── 5. Recent Bookings ────────────────────────────────────────────────────────
@app.route('/api/recent-bookings')
def recent_bookings():
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT id, guest_name, accommodation_name, check_in, status FROM bookings ORDER BY id DESC LIMIT 6")
    rows = cursor.fetchall()
    for row in rows:
        if isinstance(row.get('check_in'), date):
            row['check_in'] = row['check_in'].isoformat()
    cursor.close(); conn.close()
    return jsonify(rows)


# ── 6. Recent Logs Feed ───────────────────────────────────────────────────────
@app.route('/api/recent-logs')
def recent_logs():
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT admin_username, action, details, created_at FROM activity_logs ORDER BY id DESC LIMIT 6")
    rows = cursor.fetchall()
    for row in rows:
        if isinstance(row.get('created_at'), datetime):
            row['created_at'] = row['created_at'].strftime('%Y-%m-%d %H:%M:%S')
    cursor.close(); conn.close()
    return jsonify(rows)


# ── 7. Daily Summary Widget ───────────────────────────────────────────────────
@app.route('/api/daily-summary')
def daily_summary():
    conn = get_db()
    cursor = conn.cursor()
    today = date.today().isoformat()
    b_today  = fetchone(cursor, "SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE()")[0]
    pay_row  = fetchone(cursor, "SELECT COUNT(*), COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND DATE(paid_at)=CURDATE()")
    cin      = fetchone(cursor, "SELECT COUNT(*) FROM bookings WHERE status='Checked In' AND DATE(check_in)=CURDATE()")[0]
    cout     = fetchone(cursor, "SELECT COUNT(*) FROM bookings WHERE status='Checked Out' AND DATE(check_out)=CURDATE()")[0]
    canc     = fetchone(cursor, "SELECT COUNT(*) FROM bookings WHERE status='Cancelled' AND DATE(cancelled_at)=CURDATE()")[0]
    cursor.close(); conn.close()
    return jsonify({
        'date': today,
        'bookings_today': b_today,
        'payments_today_count': pay_row[0],
        'payments_today_amount': float(pay_row[1]),
        'checkins_today': cin,
        'checkouts_today': cout,
        'cancellations_today': canc,
    })


# ── 8. Monthly Revenue (Reports Page) ─────────────────────────────────────────
@app.route('/api/monthly-revenue')
def monthly_revenue():
    conn = get_db()
    cursor = conn.cursor()
    labels, revenue = [], []
    today = date.today()
    for i in range(5, -1, -1):
        month_date = date(today.year, today.month, 1) - timedelta(days=i * 30)
        first_day  = date(month_date.year, month_date.month, 1)
        last_day   = date(month_date.year, month_date.month, calendar.monthrange(month_date.year, month_date.month)[1])
        labels.append(first_day.strftime('%b %Y'))
        row = fetchone(cursor,
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified' AND DATE(paid_at) BETWEEN %s AND %s",
            (first_day.isoformat(), last_day.isoformat()))
        revenue.append(float(row[0]))
    cursor.close(); conn.close()
    return jsonify({'labels': labels, 'revenue': revenue})


# ── 9. Dashboard Stats (Reports Page KPIs) ───────────────────────────────────
@app.route('/api/dashboard-stats')
def dashboard_stats():
    conn = get_db()
    cursor = conn.cursor()
    total_rev  = fetchone(cursor, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified'")[0]
    total_bk   = fetchone(cursor, "SELECT COUNT(*) FROM bookings")[0]
    pending    = fetchone(cursor, "SELECT COUNT(*) FROM bookings WHERE status='Pending'")[0]
    guests     = fetchone(cursor, "SELECT COUNT(DISTINCT guest_email) FROM bookings WHERE guest_email IS NOT NULL")[0]
    avg_row    = fetchone(cursor, "SELECT AVG(DATEDIFF(check_out, check_in)) FROM bookings WHERE status IN ('Checked In','Checked Out')")
    avg_stay   = f"{round(float(avg_row[0] or 0), 1)} nights"
    cursor.close(); conn.close()
    return jsonify({
        'total_revenue': float(total_rev),
        'total_bookings': total_bk,
        'pending_bookings': pending,
        'total_guests': guests,
        'avg_stay': avg_stay,
    })


# ── 10. Payment Methods (Reports Page) ───────────────────────────────────────
@app.route('/api/payment-methods')
def payment_methods():
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute(
        "SELECT payment_method, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total "
        "FROM payments WHERE status='verified' GROUP BY payment_method ORDER BY total DESC"
    )
    rows = cursor.fetchall()
    for row in rows:
        row['total'] = float(row['total'])
    cursor.close(); conn.close()
    return jsonify(rows)


# ── 11. Top Accommodations (Reports Page) ────────────────────────────────────
@app.route('/api/top-accommodations')
def top_accommodations():
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute(
        "SELECT accommodation_name, COUNT(*) AS cnt, COUNT(DISTINCT guest_email) AS guests "
        "FROM bookings GROUP BY accommodation_name ORDER BY cnt DESC LIMIT 10"
    )
    rows = cursor.fetchall()
    cursor.close(); conn.close()
    return jsonify(rows)


if __name__ == '__main__':
    print("=" * 55)
    print("  Santa Fe Beach Club - Python Analytics Service")
    print("  Running on http://127.0.0.1:5000")
    print("  Press CTRL+C to stop.")
    print("=" * 55)
    app.run(host='127.0.0.1', port=5000, debug=False)
