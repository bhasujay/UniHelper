import mysql.connector
from mysql.connector import Error

class DatabaseConfig:
    @staticmethod
    def get_config():
        return {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'UniHelper'
        }
    
    @staticmethod
    def get_connection():
        try:
            config = DatabaseConfig.get_config()
            conn = mysql.connector.connect(**config)
            return conn
        except Error as e:
            print(f"Database connection error: {e}")
            raise

    @staticmethod
    def test_connection():
        try:
            conn = DatabaseConfig.get_connection()

            if conn.is_connected():
                cursor = conn.cursor() # SQL handler this is where sql queries execute and listen to results 
                cursor.execute("SELECT DATABASE();")
                db_name = cursor.fetchone() # get the database name

                cursor.close()
                conn.close()

                print(f"Connected to database: {db_name[0]}")
                return True
            else:
                print("Connection failed")
                return False
        except Error as e:
            print(f"Connection test failed: {e}")
            return False
    
    @staticmethod
    def close_connection(conn):
        try:
            if conn.is_connected():
                conn.close()
                # print("Database connection closed")  # Commented out to not corrupt JSON output
        except Error as e:
            # print(f"Error closing connection: {e}")
            pass

    @staticmethod
    def get_cursor(dictionary=True): # the results of the sql query get in to a dictionary 
        try:
            conn = DatabaseConfig.get_connection()
            cursor = conn.cursor(dictionary=dictionary)
            return conn, cursor
        except Error as e:
            print(f"Error getting cursor: {e}")
            raise

if __name__ == "__main__":
    config = DatabaseConfig.get_config()
    print(f"Database connection test result: {config}") 

    DatabaseConfig.test_connection()
    
    try:
        conn = DatabaseConfig.get_connection()
        cursor = conn.cursor(dictionary=True)

        cursor.execute("SHOW TABLES LIKE 'z_score_data'")
        result = cursor.fetchone()

        if result:
            cursor.execute("SELECT COUNT(*) AS count FROM z_score_data")
            count_result = cursor.fetchone()
            print(f"Table 'z_score_data' exists with {count_result['count']} records.")
        else:
            print("Table 'z_score_data' does not exist.")
        cursor.close()
        DatabaseConfig.close_connection(conn)

    except Error as e:
        print(f"Error during database query: {e}")