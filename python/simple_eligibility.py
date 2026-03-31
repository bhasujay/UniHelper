#!/usr/bin/env python3
"""
Eligibility Calculator - Uses real cutoff data from CSV
Determines eligible degree programs based on user's Z-Score, stream, and district
"""

import sys
import json
import os

# Import the generated programs data
sys.path.insert(0, os.path.dirname(__file__))
from data.processed.programs_data import PROGRAMS_DATA

def main():
    # Get arguments from PHP
    user_zscore = sys.argv[1]
    user_stream = sys.argv[2] 
    user_district = sys.argv[3]
    
    # Debug output to stderr (shows in PHP error log)
    print("🐍 Python script started!", file=sys.stderr)
    print(f"🐍 Received: Z-Score={user_zscore}, Stream={user_stream}, District={user_district}", file=sys.stderr)
    
    # Use real data from CSV
    all_programs = PROGRAMS_DATA
    
    # Convert user Z-Score to float for comparison
    user_zscore_float = float(user_zscore)
    
    # Filter programs
    eligible_programs = []
    
    for program in all_programs:
        # Check if user meets the minimum cutoff for this program
        if user_zscore_float >= program['cutoff_zscore']:
            # Filter by stream if specified
            if user_stream and user_stream != 'Any':
                if program['stream'] != user_stream:
                    continue
            
            # Check district-specific cutoff if available
            district_cutoffs = program.get('district_cutoffs', {})
            if user_district in district_cutoffs:
                district_cutoff = district_cutoffs[user_district]
                if user_zscore_float < district_cutoff:
                    continue  # User doesn't meet district-specific cutoff
                
                # Add program with district-specific info
                eligible_programs.append({
                    'name': program['name'],
                    'university': program['university'],
                    'unicode': program['unicode'],
                    'cutoff_zscore': district_cutoff,
                    'stream': program['stream'],
                    'district_specific': True
                })
            else:
                # Add program with general cutoff
                eligible_programs.append({
                    'name': program['name'],
                    'university': program['university'],
                    'unicode': program['unicode'],
                    'cutoff_zscore': program['cutoff_zscore'],
                    'stream': program['stream'],
                    'district_specific': False
                })
    
    # Sort by cutoff Z-Score (highest first)
    eligible_programs.sort(key=lambda x: x['cutoff_zscore'], reverse=True)
    
    # Create response
    result = {
        'user_zscore': user_zscore_float,
        'user_stream': user_stream,
        'user_district': user_district,
        'eligible_programs': eligible_programs,
        'total_eligible': len(eligible_programs),
        'message': f'Found {len(eligible_programs)} eligible degree program(s) for Z-Score {user_zscore_float}'
    }
    
    # Print JSON for PHP to read
    print(json.dumps(result))

if __name__ == "__main__":
    main()
