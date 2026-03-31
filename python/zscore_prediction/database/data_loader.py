"""
Historical Data Loader
Loads historical Z-score data into database
"""

import sys
from pathlib import Path

# Add parent directory to path so we can import config, extractors, database
sys.path.insert(0, str(Path(__file__).parent.parent))

from config import DatabaseConfig


class HistoricalDataLoader:
    """Load historical Z-score data into database"""
    
    def __init__(self):
        """Initialize loader"""
        self.conn = None
        self.cursor = None
    
    def check_table_exists(self):
        """
        Check if z_score_data table exists
        
        Returns:
            bool: True if table exists
        """
        try:
            self.conn, self.cursor = DatabaseConfig.get_cursor(dictionary=True)
            
            query = """
                SELECT COUNT(*) as count
                FROM information_schema.tables 
                WHERE table_schema = 'UniHelper' 
                AND table_name = 'z_score_data'
            """
            
            self.cursor.execute(query)
            result = self.cursor.fetchone()
            
            exists = result['count'] > 0
            
            DatabaseConfig.close_connection(self.conn)
            
            return exists
            
        except Exception as e:
            print(f"Error checking table: {e}")
            if self.conn:
                DatabaseConfig.close_connection(self.conn)
            return False
    
    def clear_historical_data(self):
        """
        Clear existing historical (actual) data
        Keeps predicted data, removes only actual historical data
        
        Returns:
            int: Number of records deleted
        """
        try:
            self.conn, self.cursor = DatabaseConfig.get_cursor()
            
            delete_query = "DELETE FROM z_score_data WHERE status = 'actual'"
            
            self.cursor.execute(delete_query)
            deleted_count = self.cursor.rowcount
            self.conn.commit()
            
            print(f"Deleted {deleted_count} historical records")
            
            DatabaseConfig.close_connection(self.conn)
            return deleted_count
            
        except Exception as e:
            print(f"Error clearing data: {e}")
            if self.conn:
                self.conn.rollback()
                DatabaseConfig.close_connection(self.conn)
            return 0
    
    def insert_historical_data(self, matched_data):
        """
        Insert historical Z-score data for all matched programs
        Includes NOC/NQC records (stored as NULL) for better ML predictions
        
        Args:
            matched_data: Dictionary from ProgramMatcher.extract_matched_program_data()
                         {program_id: {program_name, university, historical_data}}
        
        Returns:
            dict: {success_count, noc_count, error_count}
        """
        print("\n" + "="*70)
        print("Inserting Historical Data into Database")
        print("="*70)
        
        success_count = 0
        noc_count = 0
        error_count = 0
        
        try:
            self.conn, self.cursor = DatabaseConfig.get_cursor()
            
            # Prepare batch insert
            insert_query = """
                INSERT INTO z_score_data 
                (program_id, year, district, actual_z_score, status, actual_data_date)
                VALUES (%s, %s, %s, %s, 'actual', %s)
                ON DUPLICATE KEY UPDATE 
                    actual_z_score = VALUES(actual_z_score),
                    actual_data_date = VALUES(actual_data_date)
            """
            
            batch_data = []
            
            # Loop through all matched programs
            for program_id, prog_data in matched_data.items():
                historical = prog_data['historical_data']
                
                # Loop through each year
                for year, sheets_data in historical.items():
                    
                    # Loop through each sheet
                    for sheet_name, districts in sheets_data.items():
                        
                        # Loop through each district
                        for district, z_score in districts.items():
                            
                            # Track NOC/NQC records (NULL values)
                            if z_score is None:
                                noc_count += 1
                            
                            # Add to batch (including NULL for NOC/NQC)
                            # Convert year to date format (YYYY-01-01)
                            actual_date = f"{year}-01-01"
                            
                            batch_data.append((
                                program_id,
                                year,
                                district,
                                z_score,  # NULL for NOC/NQC, actual score otherwise
                                actual_date  # actual_data_date as '2022-01-01'
                            ))
                            
                            # Insert in batches of 1000
                            if len(batch_data) >= 1000:
                                self.cursor.executemany(insert_query, batch_data)
                                self.conn.commit()
                                success_count += len(batch_data)
                                print(f"  Inserted {success_count} records...")
                                batch_data = []
            
            # Insert remaining records
            if batch_data:
                self.cursor.executemany(insert_query, batch_data)
                self.conn.commit()
                success_count += len(batch_data)
            
            DatabaseConfig.close_connection(self.conn)
            
            print(f"\n{'='*70}")
            print(f"Insertion Complete:")
            print(f"  Total records: {success_count}")
            print(f"  With Z-scores: {success_count - noc_count}")
            print(f"  NOC/NQC (NULL): {noc_count}")
            print(f"  Errors: {error_count}")
            print(f"{'='*70}")
            
            return {
                'success': success_count,
                'noc_count': noc_count,
                'errors': error_count
            }
            
        except Exception as e:
            print(f"\nError inserting data: {e}")
            if self.conn:
                self.conn.rollback()
                DatabaseConfig.close_connection(self.conn)
            
            return {
                'success': success_count,
                'noc_count': noc_count,
                'errors': error_count + 1
            }
    
    def verify_insertion(self):
        """
        Verify inserted data
        
        Returns:
            dict: Statistics about inserted data
        """
        try:
            self.conn, self.cursor = DatabaseConfig.get_cursor(dictionary=True)
            
            # Total records
            self.cursor.execute("SELECT COUNT(*) as count FROM z_score_data WHERE status='actual'")
            total = self.cursor.fetchone()['count']
            
            # Records with actual scores vs NOC
            self.cursor.execute("SELECT COUNT(*) as count FROM z_score_data WHERE status='actual' AND actual_z_score IS NOT NULL")
            with_scores = self.cursor.fetchone()['count']
            
            self.cursor.execute("SELECT COUNT(*) as count FROM z_score_data WHERE status='actual' AND actual_z_score IS NULL")
            noc_records = self.cursor.fetchone()['count']
            
            # Records by year
            self.cursor.execute("""
                SELECT year, COUNT(*) as count 
                FROM z_score_data 
                WHERE status='actual'
                GROUP BY year 
                ORDER BY year
            """)
            by_year = self.cursor.fetchall()
            
            # Unique programs
            self.cursor.execute("""
                SELECT COUNT(DISTINCT program_id) as count 
                FROM z_score_data 
                WHERE status='actual'
            """)
            programs = self.cursor.fetchone()['count']
            
            # Unique districts
            self.cursor.execute("""
                SELECT COUNT(DISTINCT district) as count 
                FROM z_score_data 
                WHERE status='actual'
            """)
            districts = self.cursor.fetchone()['count']
            
            DatabaseConfig.close_connection(self.conn)
            
            print("\n" + "="*70)
            print("Data Verification")
            print("="*70)
            print(f"\nTotal historical records: {total}")
            print(f"  With Z-scores: {with_scores}")
            print(f"  NOC/NQC (NULL): {noc_records}")
            print(f"Unique programs: {programs}")
            print(f"Unique districts: {districts}")
            print(f"\nRecords by year:")
            for row in by_year:
                print(f"  {row['year']}: {row['count']} records")
            print("="*70)
            
            return {
                'total': total,
                'with_scores': with_scores,
                'noc_records': noc_records,
                'programs': programs,
                'districts': districts,
                'by_year': by_year
            }
            
        except Exception as e:
            print(f"Error verifying data: {e}")
            if self.conn:
                DatabaseConfig.close_connection(self.conn)
            return None


if __name__ == "__main__":
    # Test the data loader
    from extractors import ExcelReader, ZScoreExtractor
    from database import ProgramMatcher
    
    print("="*70)
    print("Testing Historical Data Loader")
    print("="*70)
    
    # Step 1: Load Excel data
    print("\nStep 1: Loading Excel data...")
    reader = ExcelReader(data_dir='../../data/raw')
    excel_data = reader.read_all_files()
    
    # Step 2: Create extractor
    print("\nStep 2: Creating extractor...")
    extractor = ZScoreExtractor(excel_data)
    
    # Step 3: Match programs
    print("\nStep 3: Matching programs...")
    matcher = ProgramMatcher(extractor)
    matcher.fetch_programs_from_database()
    matcher.match_programs_with_excel()
    matched_data = matcher.extract_matched_program_data()
    
    # Step 4: Load data
    print("\nStep 4: Loading data into database...")
    loader = HistoricalDataLoader()
    
    # Check if table exists
    if not loader.check_table_exists():
        print("\nWarning: z_score_data table doesn't exist!")
        print("Please create the table manually first.")
        exit(1)
    
    # Clear old data
    print("\nClearing old historical data...")
    loader.clear_historical_data()
    
    # Insert new data
    result = loader.insert_historical_data(matched_data)
    
    # Verify
    loader.verify_insertion()
    
    print("\nTest Complete!")



