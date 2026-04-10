import pandas as pd
from pathlib import Path

class ExcelReader: 
    def __init__(self, data_dir='../data/raw'):
        """Initialize ExcelReader with directory containing Excel files"""
        self.data_dir = Path(data_dir)
        self.years = [2022, 2023, 2024, 2025]
        self.all_data = {}

    def read_all_files(self):
        """Read all Excel files for all years"""
        for year in self.years:
            file_path = self.data_dir / f"{year}.xlsx"
            if not file_path.exists():
                print(f"Warning: {year}.xlsx not found")
                continue
            
            year_data = self.read_single_file(file_path, year)
            if year_data:
                self.all_data[year] = year_data
        
        return self.all_data

    def read_single_file(self, file_path, year):
        """Read all sheets from a single Excel file"""
        try:
            excel_file = pd.ExcelFile(file_path)
            sheet_names = excel_file.sheet_names

            sheets_data = {}
            all_programs = []

            for sheet_name in sheet_names:
                df = pd.read_excel(file_path, sheet_name=sheet_name)
                
                # Check if first row contains header (DISTRICT in first cell)
                if len(df) > 0 and df.iloc[0, 0] and 'DISTRICT' in str(df.iloc[0, 0]).upper():
                    df.columns = df.iloc[0]
                    df = df.iloc[1:]
                    df = df.reset_index(drop=True)
                
                # Skip empty sheets
                if len(df) == 0:
                    continue

                # Find district column
                district_col = None
                for col in df.columns:
                    if 'DISTRICT' in str(col).upper():
                        district_col = col
                        break
                
                if district_col is None:
                    district_col = df.columns[0]
                    first_value = str(df[district_col].iloc[0]).strip().upper()
                    if not any(d in first_value for d in ['DISTRICT', 'GAMPAHA', 'COLOMBO', 'KALUTHARA']):
                        continue
                
                # Clean data
                df = df[df[district_col].notna()]
                df = df.dropna(how='all')
                df = df.drop_duplicates(subset=[district_col], keep='first')
                df = df[df[district_col] != district_col]
                
                # Filter out footer/note rows (over 50 characters or starts with *, #, Note)
                df = df[~df[district_col].astype(str).str.startswith(('*', '#', 'Note', 'NQC', '-'))]
                df = df[df[district_col].astype(str).str.len() <= 50]

                # Get program columns
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
            print(f"Error reading {file_path}: {e}")
            return None

    def get_all_programs(self):
        """Get all unique programs across all years"""
        all_programs = set()

        for year, data in self.all_data.items():
            for sheet_name, sheet_data in data['sheets'].items():
                all_programs.update(sheet_data['programs'])

        return sorted(list(all_programs), key=lambda x: str(x))
    

if __name__ == "__main__":
    reader = ExcelReader(data_dir='../../data/raw')
    
    print("="*70)
    print("Testing Excel Reader")
    print("="*70)
    
    all_data = reader.read_all_files()
    
    if all_data:
        for year in sorted(all_data.keys()):
            year_data = all_data[year]
            print(f"\nYear {year}:")
            print(f"  Sheets: {len(year_data['sheets'])}")
            print(f"  Programs: {year_data['total_programs']}")
            print(f"  Unique: {year_data['unique_programs']}")
        
        all_programs = reader.get_all_programs()
        print(f"\n{'='*70}")
        print(f"Total unique programs: {len(all_programs)}")
        print("="*70)
    else:
        print("\nNo data loaded")
