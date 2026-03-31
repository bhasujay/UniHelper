"""
Database Helper for Fetching Degree Programs
"""

import mysql.connector
from typing import List, Dict

class DatabaseHelper:
    def __init__(self, host='localhost', user='root', password='', database='UniHelper'):
        """Initialize database connection"""
        self.config = {
            'host': host,
            'user': user,
            'password': password,
            'database': database
        }
    
    def get_programs(self, limit=None) -> List[Dict]:
        """
        Fetch degree programs from database with university names
        
        Returns:
            [
                {
                    "program_id": 1,
                    "name": "MEDICINE",
                    "university": "University of Colombo",
                    "university_id": 5
                },
                ...
            ]
        """
        try:
            conn = mysql.connector.connect(**self.config)
            cursor = conn.cursor(dictionary=True)
            
            # JOIN degree_program with universities table
            query = """
                SELECT 
                    dp.program_id,
                    dp.name,
                    dp.university_id,
                    u.name as university
                FROM degree_program dp
                INNER JOIN universities u ON dp.university_id = u.id
                ORDER BY dp.program_id
            """
            
            if limit:
                query += f" LIMIT {limit}"
            
            cursor.execute(query)
            programs = cursor.fetchall()
            
            cursor.close()
            conn.close()
            
            return programs
            
        except mysql.connector.Error as e:
            print(f"❌ Database error: {e}")
            return []
    
    def get_program_ids(self, program_names: List[str]) -> Dict[str, int]:
        """
        Get program IDs for given program names
        Useful for mapping Excel data back to database
        
        Returns:
            {"MEDICINE (University of Colombo)": 1, ...}
        """
        try:
            conn = mysql.connector.connect(**self.config)
            cursor = conn.cursor(dictionary=True)
            
            # Build mapping
            mapping = {}
            for name in program_names:
                # Try to parse "PROGRAM (University)"
                if '(' in name and ')' in name:
                    program = name.split('(')[0].strip()
                    university = name.split('(')[1].replace(')', '').strip()
                    
                    query = """
                        SELECT id 
                        FROM degree_programs 
                        WHERE name = %s AND university_name = %s
                    """
                    cursor.execute(query, (program, university))
                    result = cursor.fetchone()
                    
                    if result:
                        mapping[name] = result['id']
            
            cursor.close()
            conn.close()
            
            return mapping
            
        except mysql.connector.Error as e:
            print(f"❌ Database error: {e}")
            return {}


# Example usage
if __name__ == "__main__":
    db = DatabaseHelper()
    
    # Fetch all programs
    programs = db.get_programs(limit=5)
    print(f"Found {len(programs)} programs:")
    for p in programs:
        print(f"  - {p['name']} ({p['university']})")
