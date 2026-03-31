"""
Weighted Moving Average (WMA) Predictor
Uses manually assigned weights that increase for more recent data
"""

class WMAPredictor:
    """
    Weighted Moving Average predictor for Z-score forecasting.
    Assigns higher weights to more recent years.
    """
    
    def __init__(self, weights=None):
        """
        Initialize WMA predictor.
        
        Args:
            weights: List of weights (must sum to 1.0). If None, uses default [0.10, 0.20, 0.30, 0.40]
        """
        self.default_weights = [0.10, 0.20, 0.30, 0.40]  # For 4 years
        self.custom_weights = weights
        self.min_points = 2
    
    def calculate_stability(self, scores):
        """
        Calculate data stability using coefficient of variation.
        Lower CV = more stable = higher confidence.
        
        Args:
            scores: List of Z-scores
            
        Returns:
            Stability score (0.0 to 1.0)
        """
        if len(scores) < 2:
            return 0.5
        
        mean = sum(scores) / len(scores)
        if mean == 0:
            return 0.5
        
        variance = sum((x - mean) ** 2 for x in scores) / len(scores)
        std_dev = variance ** 0.5
        
        # Coefficient of Variation
        cv = std_dev / mean if mean != 0 else 1.0
        
        # Convert to stability score (lower CV = higher stability)
        stability = max(0.0, 1.0 - cv)
        return stability
    
    def adjust_weights_for_trend(self, scores):
        """
        Adjust weights based on trend consistency.
        If recent trend is strong, boost most recent year's weight.
        
        Args:
            scores: List of Z-scores
            
        Returns:
            List of adjusted weights
        """
        if len(scores) < 3:
            return self.default_weights[:len(scores)]
        
        # Calculate year-to-year changes
        changes = [scores[i+1] - scores[i] for i in range(len(scores)-1)]
        
        # Check if trend is consistent
        if len(changes) >= 2:
            # Check if all changes are same direction
            all_increasing = all(c >= 0 for c in changes)
            all_decreasing = all(c <= 0 for c in changes)
            
            if all_increasing or all_decreasing:
                # Consistent trend - boost recent weight
                if len(scores) == 4:
                    return [0.05, 0.15, 0.30, 0.50]  # Boost most recent
                elif len(scores) == 3:
                    return [0.15, 0.30, 0.55]
                elif len(scores) == 2:
                    return [0.30, 0.70]
        
        # No consistent trend - use default weights
        return self.default_weights[:len(scores)]
    
    def normalize_weights(self, weights):
        """
        Ensure weights sum to 1.0.
        
        Args:
            weights: List of weights
            
        Returns:
            Normalized weights
        """
        total = sum(weights)
        if total == 0:
            # Equal weights if all zero
            return [1.0 / len(weights)] * len(weights)
        return [w / total for w in weights]
    
    def predict(self, scores, adaptive_weights=True):
        """
        Predict next Z-score using weighted moving average.
        
        Args:
            scores: List of Z-scores (chronological order)
            adaptive_weights: If True, adjust weights based on trend
            
        Returns:
            Dictionary with prediction results:
            {
                'prediction': float,
                'confidence': float (0-1),
                'method': 'wma',
                'weights_used': list
            }
        """
        if not scores or len(scores) < self.min_points:
            return {
                'prediction': None,
                'confidence': 0.0,
                'method': 'wma',
                'weights_used': []
            }
        
        # Determine weights to use
        if self.custom_weights:
            weights = self.custom_weights[:len(scores)]
            weights = self.normalize_weights(weights)
        elif adaptive_weights:
            weights = self.adjust_weights_for_trend(scores)
        else:
            weights = self.default_weights[:len(scores)]
        
        # Ensure we have correct number of weights
        if len(weights) != len(scores):
            weights = self.default_weights[:len(scores)]
        
        weights = self.normalize_weights(weights)
        
        # Calculate weighted average
        prediction = sum(score * weight for score, weight in zip(scores, weights))
        
        # Ensure reasonable bounds (0.0 to 3.0 for Z-scores)
        prediction = max(0.0, min(3.0, prediction))
        
        # Calculate confidence based on data stability
        stability = self.calculate_stability(scores)
        
        # Additional confidence factor for number of data points
        point_confidence = min(1.0, len(scores) / 4.0)  # Full confidence at 4+ points
        
        # Combined confidence
        confidence = (stability * 0.7) + (point_confidence * 0.3)
        confidence = round(confidence, 2)
        
        return {
            'prediction': round(prediction, 4),
            'confidence': confidence,
            'method': 'wma',
            'weights_used': [round(w, 2) for w in weights]
        }


if __name__ == "__main__":
    predictor = WMAPredictor()
    
    # Test 1: Normal stable trend
    print("Test 1: Stable upward trend")
    scores = [1.80, 1.82, 1.85, 1.87]
    result = predictor.predict(scores, adaptive_weights=True)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Weights used: {result['weights_used']}")
    
    # Test 2: Consistent trend (should boost recent weight)
    print("\nTest 2: Strong consistent trend")
    scores = [1.50, 1.60, 1.70, 1.80]
    result = predictor.predict(scores, adaptive_weights=True)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Weights used: {result['weights_used']}")
    
    # Test 3: Volatile data
    print("\nTest 3: Volatile data")
    scores = [1.50, 1.90, 1.60, 1.88]
    result = predictor.predict(scores, adaptive_weights=True)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Weights used: {result['weights_used']}")
