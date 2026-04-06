"""
Main Entry Point for Z-Score Prediction System
Orchestrates: Fetch → Predict → Save workflow
"""

import sys
from pathlib import Path

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent))

from utils import DataFetcher, PredictionRunner, PredictionSaver


def print_header():
    """Print welcome header"""
    print("\n" + "=" * 70)
    print(" " * 15 + "🎓 Z-Score Prediction System 2026 🎓")
    print("=" * 70)


def print_section(title):
    """Print section header"""
    print("\n" + "-" * 70)
    print(f"  {title}")
    print("-" * 70)


def main():
    """
    Main workflow:
    1. Fetch historical data (2022-2025) from database
    2. Run predictions using ensemble model
    3. Save predictions to database
    4. Verify results
    """
    
    print_header()
    
    try:
        # ============================================================
        # STEP 1: FETCH HISTORICAL DATA
        # ============================================================
        print_section("📊 STEP 1: Fetching Historical Data")
        
        fetcher = DataFetcher()
        historical_data = fetcher.fetch_all_historical_data()
        
        if not historical_data:
            print("❌ Error: No historical data found in database")
            print("   Please ensure z_score_data table has data with status='actual'")
            return
        
        print(f"✅ Successfully loaded data for {len(historical_data)} program/district combinations")
        
        # Count total records
        total_records = sum(len(records) for records in historical_data.values())
        print(f"   Total historical records: {total_records}")
        
        
        # ============================================================
        # STEP 2: GENERATE PREDICTIONS
        # ============================================================
        print_section("🤖 STEP 2: Generating Predictions")
        
        runner = PredictionRunner()
        predictions = runner.predict_all(historical_data)
        
        if not predictions:
            print("❌ Error: No predictions generated")
            return
        
        # Get and display summary
        summary = runner.get_prediction_summary(predictions)
        
        print(f"\n✅ Prediction Summary:")
        print(f"   Total predictions: {summary['total']}")
        print(f"   Valid Z-score predictions: {summary['valid_count']}")
        print(f"   NOC predictions: {summary['noc_count']}")
        print(f"   Average confidence: {summary['avg_confidence']:.2%}")
        
        if summary['valid_count'] > 0:
            print(f"\n   Z-Score Statistics:")
            print(f"   - Minimum: {summary['min_z_score']}")
            print(f"   - Maximum: {summary['max_z_score']}")
            print(f"   - Average: {summary['avg_z_score']}")
        
        # Display sample predictions with program names
        print(f"\n   Fetching program names...")
        program_info = fetcher.get_program_info()
        runner.display_predictions_with_names(predictions, program_info, limit=20)
        
        
        # ============================================================
        # STEP 3: SAVE TO DATABASE
        # ============================================================
        print_section("💾 STEP 3: Saving Predictions to Database")
        
        saver = PredictionSaver()
        
        # Optional: Clear existing predictions for 2026
        print("Checking for existing 2026 predictions...")
        cleared = saver.clear_existing_predictions(2026)
        if cleared > 0:
            print(f"   Cleared {cleared} existing predictions")
        
        # Save predictions
        print("Saving new predictions...")
        save_result = saver.save_predictions(predictions)
        
        if save_result['errors']:
            print(f"⚠️  Encountered {len(save_result['errors'])} errors:")
            for error in save_result['errors'][:5]:  # Show first 5 errors
                print(f"   - {error}")
        
        print(f"\n✅ Save Results:")
        print(f"   Total processed: {save_result['total_processed']}")
        print(f"   Database records affected: {save_result['inserted']}")


        # ============================================================
        # STEP 3.5: POPULATE cutoff_summary
        # Reads from z_score_data (actual + predicted rows) and writes
        # min/max/avg/predicted into the cutoff_summary lookup table.
        # ============================================================
        print_section("📊 STEP 3.5: Populating cutoff_summary Table")

        summary_rows = saver.populate_cutoff_summary(year=2026)
        print(f"   Rows written to cutoff_summary: {summary_rows}")


        # ============================================================
        print_section("✔️  STEP 4: Verifying Results")
        
        verification = saver.verify_predictions(2026)
        
        print(f"Database verification for year {verification['year']}:")
        print(f"   Total records: {verification['total']}")
        print(f"   Valid Z-scores: {verification['valid_count']}")
        print(f"   NOC entries: {verification['noc_count']}")
        
        if verification['valid_count'] > 0:
            print(f"\n   Stored Z-Score Statistics:")
            print(f"   - Minimum: {verification['min_z_score']:.4f}")
            print(f"   - Maximum: {verification['max_z_score']:.4f}")
            print(f"   - Average: {verification['avg_z_score']:.4f}")
        
        
        # ============================================================
        # SUCCESS
        # ============================================================
        print("\n" + "=" * 70)
        print(" " * 20 + "🎉 PREDICTION COMPLETE! 🎉")
        print("=" * 70)
        print("\nNext steps:")
        print("  1. Review predictions in database (z_score_data table)")
        print("  2. Update your web application to display 2026 predictions")
        print("  3. Consider running validation/backtest to check accuracy")
        print("\n")
        
    except KeyboardInterrupt:
        print("\n\n⚠️  Process interrupted by user")
        sys.exit(1)
    
    except Exception as e:
        print(f"\n\n❌ ERROR: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    main()
