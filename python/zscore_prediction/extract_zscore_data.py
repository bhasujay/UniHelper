#!/usr/bin/env python3
"""
Complete Excel Data Reader and Z-Score Extractor
Reads all Excel files (2022-2025), all sheets (10 tabs), and extracts Z-scores
"""

import pandas as pd
from pathlib import Path
import json
import mysql.connector

class ZScoreDataExtractor:
    """Extract Z-score data from Excel files"""
    
    def __init__(self, data_dir='../data/raw', db_config=None):
        self.data_dir = Path(data_dir)
        self.years = [2022, 2023, 2024, 2025]
        self.all_data = {}
        self.db_config = db_config or {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'UniHelper'
        }
        self.db_programs = []
        
    def fetch_programs_from_database(self):
        """Fetch all degree programs from database"""
        
        print("\n" + "="*70)
        print("📋 Fetching Programs from Database")
        print("="*70)
        
        try:
            conn = mysql.connector.connect(**self.db_config)
            cursor = conn.cursor(dictionary=True)
            
            # Join degree_program with universities table
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
            
            cursor.close()
            conn.close()
            
            self.db_programs = programs
            
            print(f"\n✅ Found {len(programs)} programs in database")
            
            # Show first 5
            print(f"\nFirst 5 programs:")
            for i, prog in enumerate(programs[:5], 1):
                print(f"   {i}. [{prog['program_id']}] {prog['program_name']} ({prog['university_name']})")
            
            if len(programs) > 5:
                print(f"   ... and {len(programs) - 5} more")
            
            return programs
            
        except mysql.connector.Error as e:
            print(f"❌ Database error: {e}")
            print(f"   Make sure MySQL is running and database exists")
            return []
    
    def normalize_university_name(self, uni_name):
        """Normalize university names for matching"""
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
        """Build Excel-style program name from database record"""
        # Excel format: "PROGRAM_NAME\n(University Name)"
        uni_name = self.normalize_university_name(db_program['university_name'])
        return f"{db_program['program_name']}\n({uni_name})"
    
    def match_programs_with_excel(self):
        """Match database programs with Excel programs"""
        
        if not self.db_programs:
            print("\n⚠️  No database programs loaded. Run fetch_programs_from_database() first")
            return None
        
        if not self.all_data:
            print("\n⚠️  No Excel data loaded. Run read_all_excel_files() first")
            return None
        
        print("\n" + "="*70)
        print("🔍 Matching Database Programs with Excel Data")
        print("="*70)
        
        excel_programs = self.get_all_programs()
        matched = []
        not_matched = []
        
        for db_prog in self.db_programs:
            excel_name = self.build_excel_program_name(db_prog)
            
            # Check if this program exists in Excel
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
        
        print(f"\n✅ Matched: {len(matched)}/{len(self.db_programs)} programs")
        print(f"❌ Not matched: {len(not_matched)} programs")
        
        # Show matched programs
        print(f"\n📋 Matched programs (first 10):")
        for i, prog in enumerate(matched[:10], 1):
            print(f"   {i}. [{prog['program_id']}] {prog['db_name']}")
        if len(matched) > 10:
            print(f"   ... and {len(matched) - 10} more")
        
        # Show not matched
        if not_matched:
            print(f"\n⚠️  Not matched (first 10):")
            for i, prog in enumerate(not_matched[:10], 1):
                print(f"   {i}. [{prog['program_id']}] {prog['db_name']}")
            if len(not_matched) > 10:
                print(f"   ... and {len(not_matched) - 10} more")
        
        return {
            'matched': matched,
            'not_matched': not_matched
        }
    
    def extract_matched_program_data(self, matched_programs):
        """Extract Z-score data for all matched programs"""
        
        print("\n" + "="*70)
        print("📊 Extracting Z-Scores for Matched Programs")
        print("="*70)
        
        results = {}
        
        for prog in matched_programs:
            program_id = prog['program_id']
            excel_name = prog['excel_name']
            
            # Extract historical data across all years
            historical = self.extract_historical_data(excel_name)
            
            if historical:
                results[program_id] = {
                    'program_id': program_id,
                    'program_name': prog['program_name'],
                    'university': prog['university_name'],
                    'historical_data': historical
                }
        
        print(f"\n✅ Extracted data for {len(results)} programs")
        print(f"   Years covered: {sorted([y for y in self.years if y in self.all_data])}")
        
        return results
        
    def read_all_excel_files(self):
        """Read all Excel files for all years"""
        
        print("="*70)
        print("📚 Reading Excel Files (2022-2025)")
        print("="*70)
        
        for year in self.years:
            file_path = self.data_dir / f"{year}.xlsx"
            
            if not file_path.exists():
                print(f"\n❌ {year}.xlsx not found")
                continue
            
            print(f"\n📊 Reading {year}.xlsx...")
            year_data = self.read_single_file(file_path, year)
            
            if year_data:
                self.all_data[year] = year_data
                print(f"   ✅ {len(year_data['sheets'])} sheets, {year_data['total_programs']} programs")
        
        print(f"\n{'='*70}")
        print(f"✅ Successfully read {len(self.all_data)} files")
        print(f"{'='*70}")
        
        return self.all_data
    
    def read_single_file(self, file_path, year):
        """Read all sheets from a single Excel file"""
        
        try:
            # Get all sheet names
            excel_file = pd.ExcelFile(file_path)
            sheet_names = excel_file.sheet_names
            
            sheets_data = {}
            all_programs = []
            
            # Read each sheet
            for sheet_name in sheet_names:
                df = pd.read_excel(file_path, sheet_name=sheet_name)
                
                # Find district column
                # Try 1: Look for "DISTRICT" in column name
                district_col = None
                for col in df.columns:
                    if 'DISTRICT' in str(col).upper():
                        district_col = col
                        break
                
                # Try 2: If not found, assume first column is district
                if district_col is None:
                    district_col = df.columns[0]
                    # Verify it looks like districts by checking first value
                    first_val = str(df[district_col].iloc[0]).upper()
                    if not any(d in first_val for d in ['COLOMBO', 'GAMPAHA', 'DISTRICT']):
                        continue  # Not a district column, skip this sheet
                
                # Clean data
                df = df[df[district_col].notna()]
                df = df.dropna(how='all')
                df = df.drop_duplicates(subset=[district_col], keep='first')
                df = df[df[district_col] != district_col]
                
                # Get program columns (exclude district and unnamed)
                program_cols = []
                for col in df.columns:
                    if col != district_col and not str(col).startswith('Unnamed'):
                        program_cols.append(col)
                
                sheets_data[sheet_name] = {
                    'dataframe': df,
                    'district_col': district_col,
                    'programs': program_cols,
                    'num_districts': len(df)
                }
                
                all_programs.extend(program_cols)
            
            return {
                'file_path': str(file_path),
                'sheets': sheets_data,
                'total_programs': len(all_programs),
                'unique_programs': len(set(all_programs))
            }
            
        except Exception as e:
            print(f"   ❌ Error: {e}")
            return None
    
    def get_all_programs(self):
        """Get all unique programs across all years"""
        
        all_programs = set()
        
        for year, data in self.all_data.items():
            for sheet_name, sheet_data in data['sheets'].items():
                all_programs.update(sheet_data['programs'])
        
        return sorted(list(all_programs), key=lambda x: str(x))
    
    def extract_program_zscore(self, program_name, year, sheet_name):
        """Extract Z-scores for a specific program"""
        
        if year not in self.all_data:
            return None
        
        if sheet_name not in self.all_data[year]['sheets']:
            return None
        
        sheet_data = self.all_data[year]['sheets'][sheet_name]
        df = sheet_data['dataframe']
        district_col = sheet_data['district_col']
        
        if program_name not in df.columns:
            return None
        
        # Extract Z-scores by district
        result = {}
        for idx, row in df.iterrows():
            district = row[district_col]
            zscore = row[program_name]
            
            if pd.isna(zscore) or str(zscore).upper() in ['NOC', 'NQC', 'N/A']:
                result[district] = None
            else:
                try:
                    result[district] = float(zscore)
                except:
                    result[district] = None
        
        return result
    
    def extract_historical_data(self, program_name):
        """Extract 4 years of historical Z-scores for a program"""
        
        historical = {}
        
        # Search all sheets in all years
        for year in self.years:
            if year not in self.all_data:
                continue
            
            year_sheets = {}
            for sheet_name in self.all_data[year]['sheets'].keys():
                data = self.extract_program_zscore(program_name, year, sheet_name)
                
                if data:
                    year_sheets[sheet_name] = data
            
            if year_sheets:
                historical[year] = year_sheets
        
        return historical
    
    def show_summary(self):
        """Show summary of loaded data"""
        
        print("\n" + "="*70)
        print("📊 DATA SUMMARY")
        print("="*70)
        
        total_sheets = 0
        total_programs = 0
        
        for year, data in sorted(self.all_data.items()):
            num_sheets = len(data['sheets'])
            num_programs = data['total_programs']
            total_sheets += num_sheets
            total_programs += num_programs
            
            print(f"\n{year}:")
            print(f"   Sheets: {num_sheets}")
            print(f"   Total programs: {num_programs}")
            print(f"   Unique programs: {data['unique_programs']}")
        
        all_programs = self.get_all_programs()
        
        print(f"\n{'='*70}")
        print(f"ACROSS ALL YEARS:")
        print(f"   Total files: {len(self.all_data)}")
        print(f"   Total sheets: {total_sheets}")
        print(f"   Unique programs: {len(all_programs)}")
        print(f"{'='*70}")
    
    def save_programs_list(self, output_file='output/all_programs_consolidated.txt'):
        """Save all programs to a text file"""
        
        all_programs = self.get_all_programs()
        
        output_path = Path(output_file)
        output_path.parent.mkdir(exist_ok=True)
        
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write("ALL UNIQUE PROGRAMS (2022-2025)\n")
            f.write("="*70 + "\n\n")
            
            for i, prog in enumerate(all_programs, 1):
                f.write(f"{i}. {prog}\n")
            
            f.write(f"\nTotal: {len(all_programs)} programs\n")
        
        print(f"\n💾 Programs list saved to: {output_path}")
    
    def show_sample_data(self):
        """Show sample Z-score data"""
        
        print("\n" + "="*70)
        print("📈 SAMPLE Z-SCORE DATA")
        print("="*70)
        
        # Get first year with data
        if not self.all_data:
            print("No data loaded")
            return
        
        year = list(self.all_data.keys())[0]
        first_sheet = list(self.all_data[year]['sheets'].keys())[0]
        sheet_data = self.all_data[year]['sheets'][first_sheet]
        
        if not sheet_data['programs']:
            return
        
        # Show first program
        program = sheet_data['programs'][0]
        data = self.extract_program_zscore(program, year, first_sheet)
        
        print(f"\nYear: {year}")
        print(f"Sheet: {first_sheet}")
        print(f"Program: {program.replace(chr(10), ' ')}")
        print(f"\nZ-Scores by District (first 10):")
        print("─"*70)
        
        for i, (district, zscore) in enumerate(list(data.items())[:10], 1):
            zscore_str = f"{zscore:.4f}" if zscore else "NOC"
            print(f"   {i:2d}. {district:20s} → {zscore_str}")


def main():
    """Main execution"""
    
    print("="*70)
    print("📚 Complete Z-Score Data Extractor")
    print("="*70)
    
    # Initialize extractor
    extractor = ZScoreDataExtractor()
    
    # Read all files
    extractor.read_all_excel_files()
    
    # Show summary
    extractor.show_summary()
    
    # Show sample data
    extractor.show_sample_data()
    
    # Save programs list
    extractor.save_programs_list()
    
    # Fetch programs from database
    db_programs = extractor.fetch_programs_from_database()
    
    if db_programs:
        # Match with Excel data
        matching_result = extractor.match_programs_with_excel()
        
        if matching_result and matching_result['matched']:
            # Extract Z-score data for matched programs
            matched_data = extractor.extract_matched_program_data(matching_result['matched'])
            
            # Show sample of extracted data
            if matched_data:
                print("\n" + "="*70)
                print("📈 Sample of Extracted Data")
                print("="*70)
                
                # Get first matched program
                first_id = list(matched_data.keys())[0]
                first_prog = matched_data[first_id]
                
                print(f"\nProgram: {first_prog['program_name']} ({first_prog['university']})")
                print(f"Program ID: {first_prog['program_id']}")
                print(f"\nHistorical data:")
                
                for year, sheets_data in sorted(first_prog['historical_data'].items()):
                    print(f"\n  {year}:")
                    for sheet_name, districts in sheets_data.items():
                        # Show first 3 districts
                        district_list = list(districts.items())[:3]
                        district_str = ", ".join([f"{d}: {z}" for d, z in district_list])
                        print(f"    {sheet_name}: {district_str}...")
                
                # Save matched data
                output_file = Path('output') / 'matched_programs_data.json'
                output_file.parent.mkdir(exist_ok=True)
                
                with open(output_file, 'w') as f:
                    json.dump(matched_data, f, indent=2)
                
                print(f"\n✅ Matched program data saved to: {output_file}")
                print(f"   Total matched programs with data: {len(matched_data)}")
    
    print("\n" + "="*70)
    print("✨ Complete! Ready for:")
    print("   1. ML model training on matched programs")
    print("   2. Predict 2026 cutoffs")
    print("="*70)
    
    return extractor


if __name__ == "__main__":
    extractor = main()
