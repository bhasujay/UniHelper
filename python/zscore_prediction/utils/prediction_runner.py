"""
Prediction Runner
Orchestrates the prediction process using ensemble models
"""

import sys
from pathlib import Path

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent.parent))

from models import EnsemblePredictor


class PredictionRunner:
    """
    Runs predictions for all program/district combinations
    using the ensemble predictor
    """
    
    def __init__(self):
        """Initialize prediction runner with ensemble model"""
        self.ensemble = EnsemblePredictor()
        self.target_year = 2026
    
    def predict_all(self, historical_data):
        """
        Generate predictions for all program/district combinations
        
        Args:
            historical_data: Dictionary from DataFetcher.fetch_all_historical_data()
                           Format: {(program_id, district): [(year, z_score), ...], ...}
        
        Returns:
            list: List of prediction dictionaries:
                [
                    {
                        'program_id': int,
                        'district': str,
                        'year': int,
                        'predicted_z_score': float or None (None = NOC),
                        'confidence': float (0-1),
                        'is_noc': bool,
                        'method_details': dict (optional - for debugging/display)
                    },
                    ...
                ]
        """
        if not historical_data:
            print("Warning: No historical data provided")
            return []
        
        predictions = []
        total_combinations = len(historical_data)
        processed = 0
        
        print(f"Predicting for {total_combinations} program/district combinations...")
        
        for (program_id, district), records in historical_data.items():
            processed += 1
            
            # Show progress every 100 predictions
            if processed % 100 == 0 or processed == total_combinations:
                print(f"  Progress: {processed}/{total_combinations} ({(processed/total_combinations)*100:.1f}%)")
            
            # Use ensemble predictor to get prediction
            result = self.ensemble.predict_for_program_district(records)
            
            # Build prediction record
            prediction = {
                'program_id': program_id,
                'district': district,
                'year': self.target_year,
                'predicted_z_score': result['prediction'],  # None if NOC
                'confidence': result['confidence'],
                'is_noc': result['prediction'] is None,
                'method_details': {
                    'noc_analysis': result['noc_analysis'],
                    'methods_used': result['methods_used'],
                    'individual_predictions': result.get('details', {})
                }
            }
            
            predictions.append(prediction)
        
        return predictions
    
    def get_prediction_summary(self, predictions):
        """
        Generate summary statistics for predictions
        
        Args:
            predictions: List of predictions from predict_all()
        
        Returns:
            dict: Summary statistics
        """
        if not predictions:
            return {
                'total': 0,
                'noc_count': 0,
                'valid_count': 0,
                'avg_confidence': 0.0,
                'min_z_score': 0.0,
                'max_z_score': 0.0,
                'avg_z_score': 0.0
            }
        
        # Count NOC vs valid predictions
        noc_predictions = [p for p in predictions if p['is_noc']]
        valid_predictions = [p for p in predictions if not p['is_noc']]
        
        # Calculate statistics for valid predictions
        if valid_predictions:
            z_scores = [p['predicted_z_score'] for p in valid_predictions]
            avg_z_score = sum(z_scores) / len(z_scores)
            min_z_score = min(z_scores)
            max_z_score = max(z_scores)
        else:
            avg_z_score = 0.0
            min_z_score = 0.0
            max_z_score = 0.0
        
        # Average confidence across all predictions
        confidences = [p['confidence'] for p in predictions]
        avg_confidence = sum(confidences) / len(confidences) if confidences else 0.0
        
        return {
            'total': len(predictions),
            'noc_count': len(noc_predictions),
            'valid_count': len(valid_predictions),
            'avg_confidence': round(avg_confidence, 4),
            'min_z_score': round(min_z_score, 4),
            'max_z_score': round(max_z_score, 4),
            'avg_z_score': round(avg_z_score, 4)
        }
    
    def display_predictions_with_names(self, predictions, program_info, limit=10):
        """
        Display predictions with actual program names
        
        Args:
            predictions: List of predictions
            program_info: Dict of {program_id: program_name} from DataFetcher.get_program_info()
            limit: Maximum number of predictions to display (default: 10)
        """
        print(f"\n📋 Predictions (showing first {min(limit, len(predictions))}):")
        print("-" * 80)
        
        for i, pred in enumerate(predictions[:limit]):
            program_name = program_info.get(pred['program_id'], f"Program {pred['program_id']}")
            status = "NOC" if pred['is_noc'] else f"Z={pred['predicted_z_score']:.4f}"
            
            print(f"{i+1:3d}. {program_name[:50]:<50} | {pred['district']:<15} | {status:<12} | Conf: {pred['confidence']:.2%}")
        
        if len(predictions) > limit:
            print(f"\n... and {len(predictions) - limit} more predictions")


# Test the prediction runner
if __name__ == "__main__":
    # Test with sample data
    print("Testing PredictionRunner...")
    
    # Fetch actual program names from database
    from .data_fetcher import DataFetcher
    fetcher = DataFetcher()
    program_info = fetcher.get_program_info()
    
    # Create sample historical data with real program IDs
    sample_data = {
        (1, 'Colombo'): [(2022, 1.5), (2023, 1.6), (2024, 1.7), (2025, 1.8)],
        (1, 'Gampaha'): [(2022, 1.3), (2023, 1.4), (2024, 1.5), (2025, 1.6)],
        (2, 'Colombo'): [(2022, None), (2023, None), (2024, None), (2025, None)],  # NOC
    }
    
    runner = PredictionRunner()
    predictions = runner.predict_all(sample_data)
    
    # Display predictions with program names
    runner.display_predictions_with_names(predictions, program_info, limit=10)
    
    # Get summary
    summary = runner.get_prediction_summary(predictions)
    print(f"\n📊 Summary:")
    print(f"  Total: {summary['total']}")
    print(f"  Valid predictions: {summary['valid_count']}")
    print(f"  NOC predictions: {summary['noc_count']}")
    print(f"  Average confidence: {summary['avg_confidence']:.2%}")
    if summary['valid_count'] > 0:
        print(f"  Z-score range: {summary['min_z_score']} - {summary['max_z_score']}")
        print(f"  Average Z-score: {summary['avg_z_score']}")
