"""
Data Fetcher
Fetches historical Z-score data from database for prediction
"""

import sys
from pathlib import Path

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent.parent))

from config import DatabaseConfig


class DataFetcher:
    """
    Fetches historical Z-score data from database
    for use in prediction models
    """
    
    def __init__(self):
        """Initialize database configuration"""
        self.db_config = DatabaseConfig()
    
    def fetch_all_historical_data(self):
        """
        Fetch all historical Z-score data from database
        
        Queries z_score_data table for records with status='actual'
        Groups data by (program_id, district) combination
        
        Returns:
            dict: Historical data grouped by program/district
            Format:
            {
                (program_id, district): [(year, z_score), (year, z_score), ...],
                (1, 'Colombo'): [(2022, 1.5), (2023, 1.6), (2024, 1.7), (2025, 1.8)],
                (1, 'Gampaha'): [(2022, 1.3), (2023, None), (2024, 1.5), (2025, 1.6)],
                ...
            }
            
            Note: z_score is None for NOC (No Cutoff) entries
        """
        conn, cursor = self.db_config.get_cursor(dictionary=True)
        
        try:
            # Query to get all historical data
            query = """
                SELECT 
                    program_id,
                    year,
                    district,
                    actual_z_score
                FROM z_score_data
                WHERE status = 'actual'
                ORDER BY program_id, district, year
            """
            
            cursor.execute(query)
            records = cursor.fetchall()
            
            if not records:
                print("Warning: No historical data found in database")
                print("Make sure z_score_data table has records with status='actual'")
                return {}
            
            # Group data by (program_id, district)
            grouped_data = {}
            
            for record in records:
                program_id = record['program_id']
                district = record['district']
                year = record['year']
                z_score = float(record['actual_z_score']) if record['actual_z_score'] is not None else None
                
                # Create key (program_id, district)
                key = (program_id, district)
                
                # Initialize list if first time seeing this combination
                if key not in grouped_data:
                    grouped_data[key] = []
                
                # Add (year, z_score) tuple
                grouped_data[key].append((year, z_score))
            
            print(f"Fetched {len(records)} historical records")
            print(f"Found {len(grouped_data)} unique program/district combinations")
            
            return grouped_data
            
        except Exception as e:
            print(f"Error fetching historical data: {e}")
            import traceback
            traceback.print_exc()
            return {}
        
        finally:
            self.db_config.close_connection(conn)
    
    def get_program_info(self):
        """
        Get basic information about degree programs
        
        Returns:
            dict: {program_id: program_name}
        """
        conn, cursor = self.db_config.get_cursor(dictionary=True)
        
        try:
            query = """
                SELECT id, name
                FROM degree_program
                ORDER BY id
            """
            
            cursor.execute(query)
            programs = cursor.fetchall()
            
            program_dict = {p['id']: p['name'] for p in programs}
            
            return program_dict
            
        except Exception as e:
            print(f"Error fetching program info: {e}")
            return {}
        
        finally:
            self.db_config.close_connection(conn)
    
    def get_data_statistics(self):
        """
        Get statistics about historical data in database
        
        Returns:
            dict: Statistics about the data
        """
        conn, cursor = self.db_config.get_cursor(dictionary=True)
        
        try:
            stats = {}
            
            # Total records
            cursor.execute("""
                SELECT COUNT(*) as total
                FROM z_score_data
                WHERE status = 'actual'
            """)
            stats['total_records'] = cursor.fetchone()['total']
            
            # Unique programs
            cursor.execute("""
                SELECT COUNT(DISTINCT program_id) as programs
                FROM z_score_data
                WHERE status = 'actual'
            """)
            stats['unique_programs'] = cursor.fetchone()['programs']
            
            # Unique districts
            cursor.execute("""
                SELECT COUNT(DISTINCT district) as districts
                FROM z_score_data
                WHERE status = 'actual'
            """)
            stats['unique_districts'] = cursor.fetchone()['districts']
            
            # Year range
            cursor.execute("""
                SELECT MIN(year) as min_year, MAX(year) as max_year
                FROM z_score_data
                WHERE status = 'actual'
            """)
            year_range = cursor.fetchone()
            stats['min_year'] = year_range['min_year']
            stats['max_year'] = year_range['max_year']
            
            # NOC count
            cursor.execute("""
                SELECT COUNT(*) as noc_count
                FROM z_score_data
                WHERE status = 'actual' AND actual_z_score IS NULL
            """)
            stats['noc_count'] = cursor.fetchone()['noc_count']
            
            # Valid Z-score count
            stats['valid_count'] = stats['total_records'] - stats['noc_count']
            
            return stats
            
        except Exception as e:
            print(f"Error getting statistics: {e}")
            return {}
        
        finally:
            self.db_config.close_connection(conn)


# Test the data fetcher
if __name__ == "__main__":
    print("Testing DataFetcher...")
    print("=" * 60)
    
    fetcher = DataFetcher()
    
    # Get statistics
    print("\n📊 Database Statistics:")
    stats = fetcher.get_data_statistics()
    if stats:
        print(f"   Total records: {stats['total_records']}")
        print(f"   Unique programs: {stats['unique_programs']}")
        print(f"   Unique districts: {stats['unique_districts']}")
        print(f"   Year range: {stats['min_year']} - {stats['max_year']}")
        print(f"   Valid Z-scores: {stats['valid_count']}")
        print(f"   NOC entries: {stats['noc_count']}")
    
    # Fetch historical data
    print("\n📥 Fetching Historical Data:")
    historical_data = fetcher.fetch_all_historical_data()
    
    if historical_data:
        # Show first 5 program/district combinations
        print(f"\n✅ Sample data (first 5 combinations):")
        for i, ((prog_id, district), records) in enumerate(list(historical_data.items())[:5]):
            print(f"\n   Program {prog_id}, {district}:")
            for year, z_score in sorted(records):
                z_display = f"{z_score:.4f}" if z_score is not None else "NOC"
                print(f"      {year}: {z_display}")
            
            if i >= 4:
                break
        
        print(f"\n... and {len(historical_data) - 5} more combinations")
    
    print("\n" + "=" * 60)
