"""
Program Matcher
Matches database programs with Excel data and extracts Z-scores
"""

from config import DatabaseConfig
from extractors import ZScoreExtractor


class ProgramMatcher:
    """Match database programs with Excel programs and extract data"""
    
    def __init__(self, zscore_extractor):
        """
        Initialize matcher with Z-score extractor
        
        Args:
            zscore_extractor: ZScoreExtractor instance with loaded Excel data
        """
        self.extractor = zscore_extractor
        self.db_programs = []
        self.matched_programs = []
        self.not_matched_programs = []
    
    def fetch_programs_from_database(self):
        """
        Fetch all degree programs from database
        
        Returns:
            list: List of program dictionaries with program_id, program_name, university_id, university_name
        """
        print("="*70)
        print("Fetching Programs from Database")
        print("="*70)
        
        try:
            conn, cursor = DatabaseConfig.get_cursor(dictionary=True)
            
            query = """
                SELECT 
                    dp.program_id,
                    dp.name as program_name,
                    dp.university_id,
                    u.name as university_name
                FROM degree_program dp
                INNER JOIN universities u ON dp.university_id = u.id
                ORDER BY dp.program_id
            """
            
            cursor.execute(query)
            programs = cursor.fetchall()
            
            DatabaseConfig.close_connection(conn)
            
            self.db_programs = programs
            
            print(f"\nFound {len(programs)} programs in database")
            
            # Show first 5
            print(f"\nFirst 5 programs:")
            for i, prog in enumerate(programs[:5], 1):
                print(f"  {i}. [{prog['program_id']}] {prog['program_name']} ({prog['university_name']})")
            
            if len(programs) > 5:
                print(f"  ... and {len(programs) - 5} more")
            
            return programs
            
        except Exception as e:
            print(f"Database error: {e}")
            print("Make sure MySQL is running and database exists")
            return []
    
    def normalize_university_name(self, uni_name):
        """
        Normalize university names for matching
        
        Args:
            uni_name: University name from database
            
        Returns:
            str: Normalized university name
        """
        replacements = {
            'University Of': 'University of',
            'Jaffana': 'Jaffna',
            'University of Eastern': 'Eastern University, Sri Lanka',
            'University of Rajarata': 'Rajarata University of Sri Lanka',
            'University Of Sabaragamuwa': 'Sabaragamuwa University of Sri Lanka',
            'University of Wayamba': 'Wayamba University of Sri Lanka',
            'University of Uva Wellassa': 'Uva Wellassa University of Sri Lanka',
            'University of South Eastern': 'South Eastern University of Sri Lanka',
        }
        
        name = uni_name.strip()
        for old, new in replacements.items():
            name = name.replace(old, new)
        
        return name
    
    def build_excel_program_name(self, db_program):
        """
        Build Excel-style program name from database record
        
        Args:
            db_program: Database program dictionary
            
        Returns:
            str: Excel format program name "PROGRAM_NAME\n(University Name)"
        """
        uni_name = self.normalize_university_name(db_program['university_name'])
        return f"{db_program['program_name']}\n({uni_name})"
    
    def match_programs_with_excel(self):
        """
        Match database programs with Excel programs
        
        Returns:
            dict: {'matched': [...], 'not_matched': [...]}
        """
        if not self.db_programs:
            print("\nNo database programs loaded. Run fetch_programs_from_database() first")
            return None
        
        if not self.extractor.excel_data:
            print("\nNo Excel data loaded in extractor")
            return None
        
        print("\n" + "="*70)
        print("Matching Database Programs with Excel Data")
        print("="*70)
        
        # Get all Excel programs
        excel_programs = set()
        for year_data in self.extractor.excel_data.values():
            for sheet_data in year_data['sheets'].values():
                excel_programs.update(sheet_data['programs'])
        
        matched = []
        not_matched = []
        
        for db_prog in self.db_programs:
            excel_name = self.build_excel_program_name(db_prog)
            
            if excel_name in excel_programs:
                matched.append({
                    'program_id': db_prog['program_id'],
                    'db_name': f"{db_prog['program_name']} ({db_prog['university_name']})",
                    'excel_name': excel_name,
                    'program_name': db_prog['program_name'],
                    'university_name': self.normalize_university_name(db_prog['university_name'])
                })
            else:
                not_matched.append({
                    'program_id': db_prog['program_id'],
                    'db_name': f"{db_prog['program_name']} ({db_prog['university_name']})",
                    'excel_name': excel_name
                })
        
        self.matched_programs = matched
        self.not_matched_programs = not_matched
        
        print(f"\nMatched: {len(matched)}/{len(self.db_programs)} programs")
        print(f"Not matched: {len(not_matched)} programs")
        
        # Show matched programs
        if matched:
            print(f"\nMatched programs (first 10):")
            for i, prog in enumerate(matched[:10], 1):
                print(f"  {i}. [{prog['program_id']}] {prog['db_name']}")
            if len(matched) > 10:
                print(f"  ... and {len(matched) - 10} more")
        
        # Show not matched
        if not_matched:
            print(f"\nNot matched (first 10):")
            for i, prog in enumerate(not_matched[:10], 1):
                print(f"  {i}. [{prog['program_id']}] {prog['db_name']}")
            if len(not_matched) > 10:
                print(f"  ... and {len(not_matched) - 10} more")
        
        return {
            'matched': matched,
            'not_matched': not_matched
        }
    
    def extract_matched_program_data(self):
        """
        Extract Z-score data for all matched programs
        
        Returns:
            dict: {program_id: {program_id, program_name, university, historical_data}}
        """
        if not self.matched_programs:
            print("\nNo matched programs. Run match_programs_with_excel() first")
            return {}
        
        print("\n" + "="*70)
        print("Extracting Z-Scores for Matched Programs")
        print("="*70)
        
        results = {}
        
        for prog in self.matched_programs:
            program_id = prog['program_id']
            excel_name = prog['excel_name']
            
            # Extract historical data across all years
            historical = self.extractor.extract_historical_data(excel_name)
            
            if historical:
                results[program_id] = {
                    'program_id': program_id,
                    'program_name': prog['program_name'],
                    'university': prog['university_name'],
                    'historical_data': historical
                }
        
        print(f"\nExtracted data for {len(results)} programs")
        print(f"Years covered: {sorted(self.extractor.years)}")
        
        return results


if __name__ == "__main__":
    # Test with ExcelReader and ZScoreExtractor
    from extractors import ExcelReader
    
    print("="*70)
    print("Testing Program Matcher")
    print("="*70)
    
    # Load Excel data
    print("\nStep 1: Loading Excel data...")
    reader = ExcelReader(data_dir='../../data/raw')
    excel_data = reader.read_all_files()
    
    # Create extractor
    print("\nStep 2: Creating Z-Score extractor...")
    extractor = ZScoreExtractor(excel_data)
    
    # Create matcher
    print("\nStep 3: Creating program matcher...")
    matcher = ProgramMatcher(extractor)
    
    # Fetch database programs
    print("\n" + "="*70)
    db_programs = matcher.fetch_programs_from_database()
    
    if db_programs:
        # Match programs
        matching_result = matcher.match_programs_with_excel()
        
        if matching_result and matching_result['matched']:
            # Extract data
            matched_data = matcher.extract_matched_program_data()
            
            # Show sample
            if matched_data:
                print("\n" + "="*70)
                print("Sample of Extracted Data")
                print("="*70)
                
                first_id = list(matched_data.keys())[0]
                first_prog = matched_data[first_id]
                
                print(f"\nProgram: {first_prog['program_name']} ({first_prog['university']})")
                print(f"Program ID: {first_prog['program_id']}")
                print(f"\nHistorical data:")
                
                for year, sheets_data in sorted(first_prog['historical_data'].items()):
                    print(f"\n  {year}:")
                    for sheet_name, districts in list(sheets_data.items())[:2]:
                        district_list = list(districts.items())[:3]
                        district_str = ", ".join([f"{d}: {z}" for d, z in district_list if z])
                        print(f"    {sheet_name}: {district_str}")
                
                print("\n" + "="*70)
                print(f"Test Complete - {len(matched_data)} programs with historical data")
                print("="*70)
    else:
        print("\nNo database programs found")
