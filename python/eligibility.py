import sys
import json
import mysql.connector
from pathlib import Path

# Add the zscore_prediction folder to Python's search path
sys.path.insert(0, str(Path(__file__).parent / "zscore_prediction"))
from config import DatabaseConfig

# ── Read arguments from PHP ───────────────────────────────────────
if len(sys.argv) < 7:
    print(json.dumps({"error": "Not enough arguments provided"}))
    sys.exit(1)

user_zscore = float(sys.argv[1])     # e.g. 1.85
user_stream = sys.argv[2]            # e.g. physical-science
user_district = sys.argv[3]          # e.g. Colombo
user_subjects = [
    sys.argv[4].strip(),             # subject1
    sys.argv[5].strip(),             # subject2
    sys.argv[6].strip(),             # subject3
]

# ── Database Connection ───────────────────────────────────────────
def get_db_connection():
    conn, cursor = DatabaseConfig.get_cursor(dictionary=True)
    return conn, cursor

# ── Step 3: Find Eligible Programs by Subjects ────────────────────
def get_eligible_programs(cursor, stream, subjects):
    # placeholders creates something like "%s, %s, %s" for SQL
    placeholders = ', '.join(['%s'] * len(subjects))
    
    query = f"""
        SELECT DISTINCT dp.program_id, dp.name, dp.unicode, dp.major_id, u.name as university_name
        FROM degree_program dp
        JOIN universities u ON dp.university_id = u.id
        LEFT JOIN program_entry_paths pep ON dp.program_id = pep.program_id
        WHERE dp.stream = %s
        AND (
            # Condition A: The program has no strict paths defined, so just stream matters
            pep.path_id IS NULL
            
            OR 
            
            # Condition B: The user has all required subjects for at least one path
            pep.path_id IN (
                SELECT sub_ps.path_id 
                FROM path_subjects sub_ps
                WHERE sub_ps.subject_name IN ({placeholders})
                GROUP BY sub_ps.path_id
                HAVING COUNT(sub_ps.subject_name) = (
                    SELECT COUNT(*) 
                    FROM path_subjects 
                    WHERE path_id = sub_ps.path_id
                )
            )
        )
    """
    
    # We combine the stream and the 3 subjects into one list for the query parameters
    params = [stream] + subjects
    cursor.execute(query, params)
    
    return cursor.fetchall()

# ── Step 4: Calculate Probability Percentage ───────────────────────
def calculate_probability(user_z, min_c, max_c, predicted):
    if min_c is None or max_c is None or predicted is None:
        return None # Not enough data
        
    # Prevent division by zero
    if max_c == predicted: max_c += 0.01
    if predicted == min_c: min_c -= 0.01

    if user_z >= max_c:
        overage = min(user_z - max_c, 0.2) / 0.2
        return round(90.0 + (overage * 9.0), 1)
    elif user_z >= predicted:
        progress = (user_z - predicted) / (max_c - predicted)
        return round(75.0 + (progress * 14.9), 1)
    elif user_z >= min_c:
        progress = (user_z - min_c) / (predicted - min_c)
        return round(40.0 + (progress * 34.9), 1)
    else:
        shortfall = min(min_c - user_z, 0.2) / 0.2
        prob = 39.0 - (shortfall * 38.0)
        return round(max(1.0, prob), 1)

# ── Step 5: Categorize and Output JSON ───────────────────────────
def process_and_output():
    conn, cursor = get_db_connection()
    
    try:
        # 1. Get programs they are eligible for based on subjects
        programs = get_eligible_programs(cursor, user_stream, user_subjects)
        
        results = []
        
        # 2. For each program, check the z-score cutoffs
        for prog in programs:
            program_id = prog['program_id']
            
            cursor.execute("""
                SELECT min_cutoff, max_cutoff, predicted, is_noc 
                FROM cutoff_summary 
                WHERE program_id = %s AND district = %s
            """, (program_id, user_district))
            
            cutoff = cursor.fetchone()
            
            # 3. Calculate Eligibility Level Probability
            has_cutoff_values = bool(
                cutoff and (
                    cutoff.get('min_cutoff') is not None or
                    cutoff.get('max_cutoff') is not None or
                    cutoff.get('predicted') is not None
                )
            )
            no_cutoff_history = (
                not cutoff or
                bool(cutoff.get('is_noc')) or
                not has_cutoff_values
            )
            warning_message = None

            if no_cutoff_history:
                eligibility_level = "noc"
                prob_percent = None
                warning_message = (
                    "No previous cutoff marks are available for this program and district. "
                    "Eligibility may change when official cutoff data is published."
                )
            else:
                if cutoff['max_cutoff'] and user_zscore >= float(cutoff['max_cutoff']):
                    eligibility_level = "very_likely"
                elif cutoff['predicted'] and user_zscore >= float(cutoff['predicted']):
                    eligibility_level = "likely"
                elif cutoff['min_cutoff'] and user_zscore >= float(cutoff['min_cutoff']):
                    eligibility_level = "possible"
                else:
                    eligibility_level = "unlikely"

                prob_percent = calculate_probability(
                    user_zscore,
                    float(cutoff['min_cutoff']) if cutoff['min_cutoff'] else None,
                    float(cutoff['max_cutoff']) if cutoff['max_cutoff'] else None,
                    float(cutoff['predicted']) if cutoff['predicted'] else None
                )
                
            results.append({
                "program_id": program_id,
                "name": prog['name'],
                "university": prog['university_name'],
                "university_name": prog['university_name'],
                "unicode": prog['unicode'],
                "major_id": int(prog['major_id']) if prog['major_id'] is not None else None,
                "eligibility": eligibility_level,
                "probability_percent": prob_percent,
                "predicted": float(cutoff['predicted']) if (cutoff and cutoff['predicted']) else None,
                "min_cutoff": float(cutoff['min_cutoff']) if (cutoff and cutoff['min_cutoff']) else None,
                "max_cutoff": float(cutoff['max_cutoff']) if (cutoff and cutoff['max_cutoff']) else None,
                "no_cutoff_history": no_cutoff_history,
                "warning_message": warning_message
            })
            
        # 4. Print the final JSON for PHP!
        print(json.dumps(results))
        
    except Exception as e:
         # If an error happens, print it as JSON so PHP doesn't crash
        print(json.dumps({"error": str(e)}))
        
    finally:
        DatabaseConfig.close_connection(conn)

# Finally, actually run the process when the script is executed
if __name__ == "__main__":
    process_and_output()
