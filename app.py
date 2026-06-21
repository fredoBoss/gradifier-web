# from flask import Flask, render_template, request, redirect, url_for, session
# import mysql.connector

# app = Flask(__name__)
# app.secret_key = 'your_secret_key'

# # MySQL config
# db_config = {
#     "host": "localhost",
#     "user": "root",
#     "password": "",
#     "database": "grade"
# }

# @app.route("/", methods=["GET", "POST"])
# def login():
#     if request.method == "POST":
#         username = request.form["username"]
#         password = request.form["password"]

#         try:
#             conn = mysql.connector.connect(**db_config)
#             cursor = conn.cursor(dictionary=True)
#             cursor.execute("SELECT * FROM users WHERE username = %s AND password = %s", (username, password))
#             user = cursor.fetchone()

#             if user:
#                 session["user_id"] = user["id"]
#                 session["username"] = user["username"]
#                 return redirect(url_for("dashboard"))
#             else:
#                 return "Invalid username or password"

#         except mysql.connector.Error as err:
#             return f"Database error: {err}"
#         finally:
#             if conn.is_connected():
#                 cursor.close()
#                 conn.close()

#     return render_template("login.html")

# @app.route("/dashboard")
# def dashboard():
#     if "user_id" not in session:
#         return redirect(url_for("login"))
#     return render_template("dashboard.html", username=session["username"])

# if __name__ == "__main__":
#     app.run(debug=True)
