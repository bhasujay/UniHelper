"""
View Predictions for Specific Degree Programs
Simple tool to query and display 2026 predictions
"""

import sys
from pathlib import Path

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent))

from config import DatabaseConfig


def list_all_programs():
    """List all available degree programs"""
    db = DatabaseConfig()
    conn, cursor = db.get_cursor(dictionary=True)
    
    try:
        query = """
            SELECT dp.id, dp.name, u.name as university
            FROM degree_program dp
            JOIN universities u ON dp.university_id = u.id
            ORDER BY dp.name
        """
        cursor.execute(query)
        programs = cursor.fetchall()
        
        print("\n" + "=" * 80)
        print(" " * 25 + "📚 AVAILABLE DEGREE PROGRAMS")
        print("=" * 80)
        
        for prog in programs:
            print(f"  {prog['id']:3d}. {prog['name'][:50]:<50} ({prog['university']})")
        
        print("=" * 80)
        print(f"\nTotal: {len(programs)} programs\n")
        
        return programs
        
    finally:
        db.close_connection(conn)


def view_program_predictions(program_id):
    """View all 2026 predictions for a specific program"""
    db = DatabaseConfig()
    conn, cursor = db.get_cursor(dictionary=True)
    
    try:
        # Get program info
        cursor.execute("""
            SELECT dp.name, u.name as university
            FROM degree_program dp
            JOIN universities u ON dp.university_id = u.id
            WHERE dp.id = %s
        """, (program_id,))
        
        program_info = cursor.fetchone()
        
        if not program_info:
            print(f"\n❌ Error: Program ID {program_id} not found")
            return
        
        # Get predictions
        cursor.execute("""
            SELECT 
                district,
                predicted_z_score,
                prediction_date
            FROM z_score_data
            WHERE program_id = %s 
              AND year = 2026 
              AND status = 'predicted'
            ORDER BY 
                CASE WHEN predicted_z_score IS NULL THEN 1 ELSE 0 END,
                predicted_z_score DESC
        """, (program_id,))
        
        predictions = cursor.fetchall()
        
        if not predictions:
            print(f"\n⚠️  No predictions found for program ID {program_id}")
            print("   Run main.py first to generate predictions")
            return
        
        # Display results
        print("\n" + "=" * 80)
        print(f"  {program_info['name']}")
        print(f"  {program_info['university']}")
        print("=" * 80)
        print(f"  Predicted Z-Scores for 2026 by District")
        print("-" * 80)
        
        valid_count = 0
        noc_count = 0
        
        for pred in predictions:
            district = pred['district']
            z_score = pred['predicted_z_score']
            
            if z_score is not None:
                print(f"  {district:<20} │ Z-Score: {float(z_score):.4f}")
                valid_count += 1
            else:
                print(f"  {district:<20} │ NOC (No Cutoff)")
                noc_count += 1
        
        print("-" * 80)
        print(f"  Total: {len(predictions)} districts  │  Valid: {valid_count}  │  NOC: {noc_count}")
        print("=" * 80 + "\n")
        
    finally:
        db.close_connection(conn)


def search_programs_by_name(search_term):
    """Search for programs by name"""
    db = DatabaseConfig()
    conn, cursor = db.get_cursor(dictionary=True)
    
    try:
        query = """
            SELECT dp.id, dp.name, u.name as university
            FROM degree_program dp
            JOIN universities u ON dp.university_id = u.id
            WHERE dp.name LIKE %s OR u.name LIKE %s
            ORDER BY dp.name
        """
        search_pattern = f"%{search_term}%"
        cursor.execute(query, (search_pattern, search_pattern))
        programs = cursor.fetchall()
        
        if not programs:
            print(f"\n❌ No programs found matching '{search_term}'")
            return []
        
        print(f"\n🔍 Found {len(programs)} program(s) matching '{search_term}':")
        print("-" * 80)
        for prog in programs:
            print(f"  {prog['id']:3d}. {prog['name'][:50]:<50} ({prog['university']})")
        print("-" * 80 + "\n")
        
        return programs
        
    finally:
        db.close_connection(conn)


def main():
    """Main interactive menu"""
    print("\n" + "=" * 80)
    print(" " * 20 + "🎓 Z-Score Prediction Viewer 🎓")
    print("=" * 80)
    
    while True:
        print("\nOptions:")
        print("  1. List all degree programs")
        print("  2. View predictions for a specific program (by ID)")
        print("  3. Search programs by name")
        print("  4. Quick view (enter program ID directly)")
        print("  0. Exit")
        
        choice = input("\nEnter your choice: ").strip()
        
        if choice == '0':
            print("\n👋 Goodbye!\n")
            break
        
        elif choice == '1':
            list_all_programs()
        
        elif choice == '2':
            try:
                program_id = int(input("Enter program ID: ").strip())
                view_program_predictions(program_id)
            except ValueError:
                print("❌ Invalid program ID. Please enter a number.")
        
        elif choice == '3':
            search_term = input("Enter search term (program or university name): ").strip()
            if search_term:
                results = search_programs_by_name(search_term)
                if results and len(results) == 1:
                    view_choice = input(f"\nView predictions for this program? (y/n): ").strip().lower()
                    if view_choice == 'y':
                        view_program_predictions(results[0]['id'])
                elif results:
                    try:
                        program_id = int(input("\nEnter program ID to view predictions: ").strip())
                        view_program_predictions(program_id)
                    except ValueError:
                        print("❌ Invalid program ID.")
        
        elif choice == '4':
            try:
                program_id = int(input("Program ID: ").strip())
                view_program_predictions(program_id)
            except ValueError:
                print("❌ Invalid program ID. Please enter a number.")
        
        else:
            print("❌ Invalid choice. Please try again.")


if __name__ == "__main__":
    # If program ID provided as command line argument
    if len(sys.argv) > 1:
        try:
            program_id = int(sys.argv[1])
            view_program_predictions(program_id)
        except ValueError:
            print("❌ Invalid program ID. Usage: python view_predictions.py <program_id>")
    else:
        main()
