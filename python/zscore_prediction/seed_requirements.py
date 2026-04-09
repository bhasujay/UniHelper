import sys
from pathlib import Path
import json

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent))
from config import DatabaseConfig

def seed_requirements():
    print("🚀 Starting Requirements Setup...")
    db = DatabaseConfig()
    conn, cursor = db.get_cursor()

    try:
        # ==========================================
        # STEP 1: CREATE THE TABLES
        # ==========================================
        print("\n📦 Creating tables if they don't exist...")
        
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS program_entry_paths (
                path_id INT PRIMARY KEY AUTO_INCREMENT,
                program_id INT NOT NULL,
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (program_id) REFERENCES degree_program(program_id) ON DELETE CASCADE
            )
        """)

        cursor.execute("""
            CREATE TABLE IF NOT EXISTS path_subjects (
                id INT PRIMARY KEY AUTO_INCREMENT,
                path_id INT NOT NULL,
                subject_name VARCHAR(100) NOT NULL,
                min_grade VARCHAR(2) DEFAULT 'S',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (path_id) REFERENCES program_entry_paths(path_id) ON DELETE CASCADE
            )
        """)

        # Clear existing rules to prevent duplicates if running multiple times
        cursor.execute("DELETE FROM program_entry_paths")
        print("✅ Tables ready.")

        # ==========================================
        # STEP 2: LOAD DEGREE PROGRAMS
        # ==========================================
        print("\n🔍 Loading Degree Programs...")
        cursor.execute("SELECT program_id, name, stream, unicode FROM degree_program")
        programs = cursor.fetchall()
        print(f"   Found {len(programs)} degree programs in the database.")

        # ==========================================
        # STEP 3: APPLY UGC RULES
        # ==========================================
        print("\n⚙️  Applying Subject Rules...")
        
        paths_added = 0
        subjects_added = 0

        for program in programs:
            p_id = program['program_id']
            name = str(program['name']).lower()
            stream = str(program['stream']).lower()

            combos = []

            # ------------ PHYSICAL SCIENCE STREAM ------------
            if 'physical-science' in stream:
                if 'computer science' in name:
                    # Path 1: Standard
                    combos.append({
                        "desc": "Standard Physical Science",
                        "subjects": ["Combined Mathematics", "Physics", "Chemistry"]
                    })
                    # Path 2: With ICT
                    combos.append({
                        "desc": "With ICT",
                        "subjects": ["Combined Mathematics", "Physics", "Information & Communication Technology"]
                    })
                elif 'engineering' in name or 'engineering' in name:
                    combos.append({
                        "desc": "Engineering Entry",
                        "subjects": ["Combined Mathematics", "Physics", "Chemistry"]
                    })
                else:
                    # Default physical science fallback
                    combos.append({
                        "desc": "Physical Science Entry",
                        "subjects": ["Combined Mathematics", "Physics", "Chemistry"]
                    })
                    
            # ------------ BIOLOGICAL SCIENCE STREAM ------------
            elif 'biological-science' in stream:
                if 'medicine' in name or 'dental' in name or 'veterinary' in name:
                    combos.append({
                        "desc": "Bio Entry",
                        "subjects": ["Biology", "Chemistry", "Physics"]
                    })
                elif 'agriculture' in name:
                    combos.append({
                        "desc": "Agriculture Path",
                        "subjects": ["Biology", "Chemistry", "Agricultural Science"]
                    })
                else:
                    # Default bio fallback
                    combos.append({
                        "desc": "Standard Bio Entry",
                        "subjects": ["Biology", "Chemistry", "Physics"]
                    })

            # ------------ TECHNOLOGY STREAM ------------
            elif 'technology' in stream:
                if 'engineering technology' in name or 'bst' in name:
                    combos.append({
                        "desc": "Engineering Technology",
                        "subjects": ["Science for Technology", "Engineering Technology"]
                    })
                elif 'biosystems technology' in name:
                    combos.append({
                        "desc": "Biosystems Technology",
                        "subjects": ["Science for Technology", "Biosystems Technology"]
                    })

            # ------------ COMMERCE STREAM ------------
            elif 'commerce' in stream:
                combos.append({
                    "desc": "Commerce Path",
                    "subjects": ["Accounting", "Business Studies", "Economics"]
                })
                
            # ------------ ARTS STREAM ------------
            elif 'arts' in stream:
                # Arts is so flexible, we often don't enforce strict 3-subject combos here,
                # but we'll add a generic one for now.
                pass 

            # Insert combos into DB
            for combo in combos:
                # 1. Insert Path
                cursor.execute(
                    "INSERT INTO program_entry_paths (program_id, description) VALUES (%s, %s)",
                    (p_id, combo['desc'])
                )
                path_id = cursor.lastrowid
                paths_added += 1

                # 2. Insert Subjects for this Path
                for sub in combo['subjects']:
                    cursor.execute(
                        "INSERT INTO path_subjects (path_id, subject_name, min_grade) VALUES (%s, %s, %s)",
                        (path_id, sub, 'S')
                    )
                    subjects_added += 1

        conn.commit()
        print(f"✅ Successfully added {paths_added} paths and {subjects_added} subject requirements!")

    except Exception as e:
        conn.rollback()
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
    
    finally:
        db.close_connection(conn)
        print("\nDone.")

if __name__ == "__main__":
    seed_requirements()
