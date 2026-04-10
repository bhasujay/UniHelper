"""
Ensemble Predictor
Combines Linear Regression, WMA, and Exponential Smoothing predictions
Also handles NOC (No Qualified Candidates) pattern analysis
"""

from .linear_predictor import LinearPredictor
from .wma_predictor import WMAPredictor
from .exponential_predictor import ExponentialPredictor


class EnsemblePredictor:
    """
    Ensemble predictor that combines multiple prediction methods.
    Handles NOC detection and confidence-weighted averaging.
    """
    
    def __init__(self):
        """Initialize all three predictors."""
        self.linear = LinearPredictor()
        self.wma = WMAPredictor()
        self.exponential = ExponentialPredictor(alpha=0.6)
        self.noc_threshold = 0.75  # 75% NOC means likely NOC in 2026
    
    def analyze_noc_pattern(self, historical_records):
        """
        Analyze if program/district has NOC pattern.
        
        NOC is predicted if:
        - 75% or more of historical data is NOC, OR
        - Last 2 years are NOC AND 50%+ of history is NOC
        
        Args:
            historical_records: List of tuples (year, z_score) where z_score can be None for NOC
            
        Returns:
            Dictionary with NOC analysis:
            {
                'should_predict_noc': bool,
                'noc_ratio': float,
                'recent_noc_trend': bool,
                'reason': str
            }
        """
        if not historical_records:
            return {
                'should_predict_noc': False,
                'noc_ratio': 0.0,
                'recent_noc_trend': False,
                'reason': 'No historical data'
            }
        
        # Count NOC entries (None values)
        total_records = len(historical_records)
        noc_count = sum(1 for _, z_score in historical_records if z_score is None)
        noc_ratio = noc_count / total_records if total_records > 0 else 0.0
        
        # Check if last 2 years are NOC
        recent_noc = False
        if len(historical_records) >= 2:
            last_two = historical_records[-2:]
            recent_noc = all(z_score is None for _, z_score in last_two)
        
        # Decision logic
        should_predict_noc = False
        reason = ""
        
        if noc_ratio >= self.noc_threshold:
            should_predict_noc = True
            reason = f"High NOC ratio: {noc_ratio:.1%} (>= {self.noc_threshold:.0%})"
        elif recent_noc and noc_ratio >= 0.5:
            should_predict_noc = True
            reason = f"Last 2 years NOC + {noc_ratio:.1%} historical NOC"
        else:
            reason = f"NOC ratio {noc_ratio:.1%}, recent trend normal"
        
        return {
            'should_predict_noc': should_predict_noc,
            'noc_ratio': round(noc_ratio, 2),
            'recent_noc_trend': recent_noc,
            'reason': reason
        }
    
    def predict_for_program_district(self, historical_records):
        """
        Predict Z-score for a program/district combination using ensemble method.
        
        Args:
            historical_records: List of tuples (year, z_score)
                               z_score can be None for NOC
        
        Returns:
            Dictionary with prediction:
            {
                'prediction': float or None (None = NOC predicted),
                'confidence': float (0-1),
                'noc_analysis': dict,
                'methods_used': list of method names,
                'details': {
                    'linear': dict,
                    'wma': dict,
                    'exponential': dict
                }
            }
        """
        # First, analyze NOC pattern
        noc_analysis = self.analyze_noc_pattern(historical_records)
        
        if noc_analysis['should_predict_noc']:
            return {
                'prediction': None,  # None indicates NOC
                'confidence': 0.9,  # High confidence in NOC prediction
                'noc_analysis': noc_analysis,
                'methods_used': ['noc_pattern_analysis'],
                'details': {}
            }
        
        # Filter out NOC records for prediction
        valid_records = [(year, z_score) for year, z_score in historical_records 
                        if z_score is not None]
        
        if len(valid_records) < 2:
            return {
                'prediction': None,
                'confidence': 0.0,
                'noc_analysis': noc_analysis,
                'methods_used': [],
                'details': {},
                'error': 'Insufficient valid data points (need at least 2)'
            }
        
        # Extract years and scores
        years = [year for year, _ in valid_records]
        scores = [z_score for _, z_score in valid_records]
        
        # Get predictions from all three methods
        linear_result = self.linear.predict(years, scores, remove_outliers=True)
        wma_result = self.wma.predict(scores, adaptive_weights=True)
        exponential_result = self.exponential.predict(scores, auto_tune_alpha=True)
        
        # Collect successful predictions
        predictions = []
        if linear_result['prediction'] is not None:
            predictions.append(('linear', linear_result['prediction'], linear_result['confidence']))
        if wma_result['prediction'] is not None:
            predictions.append(('wma', wma_result['prediction'], wma_result['confidence']))
        if exponential_result['prediction'] is not None:
            predictions.append(('exponential', exponential_result['prediction'], 
                              exponential_result['confidence']))
        
        if not predictions:
            return {
                'prediction': None,
                'confidence': 0.0,
                'noc_analysis': noc_analysis,
                'methods_used': [],
                'details': {
                    'linear': linear_result,
                    'wma': wma_result,
                    'exponential': exponential_result
                },
                'error': 'All prediction methods failed'
            }
        
        # Calculate confidence-weighted ensemble
        total_confidence = sum(conf for _, _, conf in predictions)
        
        if total_confidence == 0:
            # Fallback to simple average
            final_prediction = sum(pred for _, pred, _ in predictions) / len(predictions)
            final_confidence = 0.5
        else:
            # Weighted average based on confidence
            weighted_sum = sum(pred * conf for _, pred, conf in predictions)
            final_prediction = weighted_sum / total_confidence
            
            # Final confidence is average of all confidences
            final_confidence = total_confidence / len(predictions)
        
        # Ensure bounds
        final_prediction = max(0.0, min(3.0, final_prediction))
        
        return {
            'prediction': round(final_prediction, 4),
            'confidence': round(final_confidence, 2),
            'noc_analysis': noc_analysis,
            'methods_used': [method for method, _, _ in predictions],
            'details': {
                'linear': linear_result,
                'wma': wma_result,
                'exponential': exponential_result,
                'weights': {
                    method: round(conf / total_confidence, 3) if total_confidence > 0 else 0
                    for method, _, conf in predictions
                }
            }
        }


if __name__ == "__main__":
    print("=" * 70)
    print("Ensemble Predictor Tests")
    print("=" * 70)
    
    ensemble = EnsemblePredictor()
    
    # Test 1: Normal case with good data
    print("\nTest 1: Normal upward trend")
    print("-" * 70)
    records = [
        (2022, 1.80),
        (2023, 1.82),
        (2024, 1.85),
        (2025, 1.87)
    ]
    result = ensemble.predict_for_program_district(records)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Methods used: {result['methods_used']}")
    if 'details' in result and 'weights' in result['details']:
        print(f"  Method weights: {result['details']['weights']}")
    
    # Test 2: High NOC ratio (should predict NOC)
    print("\nTest 2: High NOC ratio (75%+)")
    print("-" * 70)
    records_noc = [
        (2022, None),  # NOC
        (2023, None),  # NOC
        (2024, None),  # NOC
        (2025, 1.85)
    ]
    result = ensemble.predict_for_program_district(records_noc)
    print(f"  Prediction: {result['prediction']} (None = NOC)")
    print(f"  Confidence: {result['confidence']}")
    print(f"  NOC analysis: {result['noc_analysis']['reason']}")
    
    # Test 3: Recent NOC trend
    print("\nTest 3: Recent NOC trend (last 2 years)")
    print("-" * 70)
    records_recent_noc = [
        (2022, 1.75),
        (2023, 1.80),
        (2024, None),  # NOC
        (2025, None)   # NOC
    ]
    result = ensemble.predict_for_program_district(records_recent_noc)
    print(f"  Prediction: {result['prediction']} (None = NOC)")
    print(f"  Confidence: {result['confidence']}")
    print(f"  NOC analysis: {result['noc_analysis']['reason']}")
    
    # Test 4: Mixed data (some NOC, but predictable)
    print("\nTest 4: Mixed data with some NOC")
    print("-" * 70)
    records_mixed = [
        (2022, None),  # NOC
        (2023, 1.70),
        (2024, 1.75),
        (2025, 1.78)
    ]
    result = ensemble.predict_for_program_district(records_mixed)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Methods used: {result['methods_used']}")
    print(f"  NOC ratio: {result['noc_analysis']['noc_ratio']:.1%}")
    
    # Test 5: Show individual method predictions
    print("\nTest 5: Detailed breakdown")
    print("-" * 70)
    records_detail = [
        (2022, 1.50),
        (2023, 1.65),
        (2024, 1.75),
        (2025, 1.82)
    ]
    result = ensemble.predict_for_program_district(records_detail)
    print(f"  Final Prediction: {result['prediction']}")
    print(f"  Final Confidence: {result['confidence']}")
    print(f"\n  Individual Predictions:")
    if 'details' in result:
        for method in ['linear', 'wma', 'exponential']:
            if method in result['details']:
                method_result = result['details'][method]
                print(f"    {method.upper():15s}: {method_result.get('prediction', 'N/A')} "
                      f"(confidence: {method_result.get('confidence', 0.0)})")
        if 'weights' in result['details']:
            print(f"\n  Final Weights Applied:")
            for method, weight in result['details']['weights'].items():
                print(f"    {method.upper():15s}: {weight:.1%}")
