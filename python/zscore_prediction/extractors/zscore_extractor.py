"""
Z-Score Extractor
Extracts Z-scores from Excel data loaded by ExcelReader
"""

import pandas as pd


class ZScoreExtractor:
    """Extract Z-scores from loaded Excel data"""
    
    def __init__(self, excel_data):
        """
        Initialize extractor with data from ExcelReader
        
        Args:
            excel_data: Dictionary of Excel data from ExcelReader.read_all_files()
        """
        self.excel_data = excel_data
        self.years = list(excel_data.keys())
    
    def extract_program_zscore(self, program_name, year, sheet_name):
        """
        Extract Z-scores for a specific program in a specific year/sheet
        
        Args:
            program_name: Full program name (e.g., "MEDICINE\\n(University of Colombo)")
            year: Year (2022-2025)
            sheet_name: Sheet name (e.g., "Table 1")
            
        Returns:
            dict: {district: zscore} or None if not found
        """
        if year not in self.excel_data:
            return None
        
        if sheet_name not in self.excel_data[year]['sheets']:
            return None
        
        sheet_data = self.excel_data[year]['sheets'][sheet_name]
        df = sheet_data['dataframe']
        district_col = sheet_data['district_col']
        
        if program_name not in df.columns:
            return None
        
        # Extract Z-scores by district
        result = {}
        for idx, row in df.iterrows():
            district = row[district_col]
            zscore = row[program_name]
            
            # Handle NOC, NQC, N/A, and NaN values
            if pd.isna(zscore) or str(zscore).upper() in ['NOC', 'NQC', 'N/A']:
                result[district] = None
            else:
                try:
                    result[district] = float(zscore)
                except:
                    result[district] = None
        
        return result
    
    def extract_historical_data(self, program_name):
        """
        Extract historical Z-scores across all years for a program
        
        Args:
            program_name: Full program name
            
        Returns:
            dict: {year: {sheet_name: {district: zscore}}}
        """
        historical = {}
        
        for year in self.years:
            if year not in self.excel_data:
                continue
            
            year_sheets = {}
            for sheet_name in self.excel_data[year]['sheets'].keys():
                data = self.extract_program_zscore(program_name, year, sheet_name)
                
                if data:
                    year_sheets[sheet_name] = data
            
            if year_sheets:
                historical[year] = year_sheets
        
        return historical
    
    def extract_district_data(self, program_name, district):
        """
        Extract Z-scores for a specific program and district across all years
        
        Args:
            program_name: Full program name
            district: District name (e.g., "COLOMBO")
            
        Returns:
            dict: {year: zscore} or empty dict if not found
        """
        district_data = {}
        
        for year in self.years:
            if year not in self.excel_data:
                continue
            
            for sheet_name in self.excel_data[year]['sheets'].keys():
                scores = self.extract_program_zscore(program_name, year, sheet_name)
                
                if scores and district in scores:
                    district_data[year] = scores[district]
                    break
        
        return district_data


if __name__ == "__main__":
    # Test with ExcelReader
    from excel_reader import ExcelReader
    
    print("="*70)
    print("Testing Z-Score Extractor")
    print("="*70)
    
    # Load data
    reader = ExcelReader(data_dir='../../data/raw')
    excel_data = reader.read_all_files()
    
    # Create extractor
    extractor = ZScoreExtractor(excel_data)
    
    # Get a test program
    all_programs = reader.get_all_programs()
    if all_programs:
        test_program = all_programs[0]
        
        print(f"\nTest program: {test_program.replace(chr(10), ' ')}")
        
        # Extract historical data
        historical = extractor.extract_historical_data(test_program)
        
        print(f"\nHistorical data found for {len(historical)} years")
        
        for year, sheets in sorted(historical.items()):
            print(f"\n  Year {year}:")
            for sheet_name, districts in sheets.items():
                # Show first 3 districts
                district_samples = list(districts.items())[:3]
                print(f"    {sheet_name}: ", end="")
                for district, score in district_samples:
                    score_str = f"{score:.4f}" if score else "NOC"
                    print(f"{district}={score_str}, ", end="")
                print()
        
        # Test district-specific extraction
        print("\n" + "-"*70)
        print("District-specific extraction (COLOMBO):")
        print("-"*70)
        
        district_data = extractor.extract_district_data(test_program, "COLOMBO")
        if district_data:
            for year, score in sorted(district_data.items()):
                score_str = f"{score:.4f}" if score else "NOC"
                print(f"  {year}: {score_str}")
        else:
            print("  No data found for COLOMBO")
        
        print("\n" + "="*70)
        print("Test Complete")
        print("="*70)
    else:
        print("\nNo programs found")
