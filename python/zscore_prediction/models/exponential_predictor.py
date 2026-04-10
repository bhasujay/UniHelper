"""
Exponential Smoothing Predictor
Uses exponentially decreasing weights - more recent data has exponentially higher weight
"""

class ExponentialPredictor:
    """
    Exponential Smoothing predictor for Z-score forecasting.
    Automatically calculates weights using exponential decay function.
    """
    
    def __init__(self, alpha=0.6):
        """
        Initialize Exponential Smoothing predictor.
        
        Args:
            alpha: Smoothing factor (0-1). Higher alpha = more weight on recent data
                  - 0.3: Conservative (smooth, less reactive)
                  - 0.6: Balanced (default)
                  - 0.9: Aggressive (highly reactive to recent changes)
        """
        self.alpha = max(0.0, min(1.0, alpha))  # Ensure between 0 and 1
        self.min_points = 2
    
    def calculate_exponential_weights(self, n):
        """
        Calculate exponential weights for n data points.
        
        Weight for position i (from oldest=0 to newest=n-1):
        w[i] = alpha * (1-alpha)^(n-1-i)
        
        Then normalize so they sum to 1.0
        
        Args:
            n: Number of data points
            
        Returns:
            List of normalized weights (oldest to newest)
        """
        if n == 0:
            return []
        
        if n == 1:
            return [1.0]
        
        weights = []
        for i in range(n):
            # Position from end: most recent = 0, oldest = n-1
            position_from_end = n - 1 - i
            weight = self.alpha * ((1 - self.alpha) ** position_from_end)
            weights.append(weight)
        
        # Normalize to sum to 1.0
        total = sum(weights)
        if total > 0:
            weights = [w / total for w in weights]
        else:
            # Fallback to equal weights
            weights = [1.0 / n] * n
        
        return weights
    
    def calculate_smoothness(self, scores):
        """
        Calculate how smooth (stable) the data is.
        Uses coefficient of variation of the differences.
        
        Args:
            scores: List of Z-scores
            
        Returns:
            Smoothness score (0.0 to 1.0)
        """
        if len(scores) < 2:
            return 0.5
        
        # Calculate changes between consecutive points
        changes = [abs(scores[i+1] - scores[i]) for i in range(len(scores)-1)]
        
        if not changes:
            return 1.0
        
        mean_change = sum(changes) / len(changes)
        
        if mean_change == 0:
            return 1.0  # Perfectly smooth (no changes)
        
        # Variance of changes
        variance = sum((c - mean_change) ** 2 for c in changes) / len(changes)
        std_dev = variance ** 0.5
        
        # Coefficient of variation
        cv = std_dev / mean_change if mean_change != 0 else 0
        
        # Convert to smoothness (lower CV = smoother)
        smoothness = max(0.0, 1.0 - min(cv, 1.0))
        return smoothness
    
    def calculate_recency_fit(self, scores, weights):
        """
        Calculate how well exponential smoothing fits this data.
        Checks if recent data is more representative than older data.
        
        Args:
            scores: List of Z-scores
            weights: Exponential weights
            
        Returns:
            Recency fit score (0.0 to 1.0)
        """
        if len(scores) < 3:
            return 0.7  # Default moderate fit
        
        # Compare recent trend vs overall trend
        recent_scores = scores[-2:]  # Last 2 years
        recent_change = recent_scores[-1] - recent_scores[0]
        
        # Overall trend
        overall_change = scores[-1] - scores[0]
        
        if overall_change == 0:
            return 0.7
        
        # If recent trend aligns with overall trend, exponential is good
        alignment = 1.0 - abs(recent_change - overall_change / len(scores)) / (abs(overall_change) + 0.1)
        alignment = max(0.0, min(1.0, alignment))
        
        return alignment
    
    def predict(self, scores, auto_tune_alpha=False):
        """
        Predict next Z-score using exponential smoothing.
        
        Args:
            scores: List of Z-scores (chronological order)
            auto_tune_alpha: If True, adjusts alpha based on data volatility
            
        Returns:
            Dictionary with prediction results:
            {
                'prediction': float,
                'confidence': float (0-1),
                'method': 'exponential_smoothing',
                'alpha_used': float,
                'weights_used': list
            }
        """
        if not scores or len(scores) < self.min_points:
            return {
                'prediction': None,
                'confidence': 0.0,
                'method': 'exponential_smoothing',
                'alpha_used': self.alpha,
                'weights_used': []
            }
        
        # Auto-tune alpha based on data characteristics
        alpha = self.alpha
        if auto_tune_alpha:
            # Calculate volatility
            if len(scores) >= 3:
                changes = [abs(scores[i+1] - scores[i]) for i in range(len(scores)-1)]
                avg_change = sum(changes) / len(changes)
                
                # If volatile (large changes), use higher alpha (more reactive)
                # If stable (small changes), use lower alpha (smoother)
                if avg_change > 0.1:  # Volatile
                    alpha = min(0.8, self.alpha + 0.2)
                elif avg_change < 0.05:  # Very stable
                    alpha = max(0.4, self.alpha - 0.1)
        
        # Calculate exponential weights
        weights = self.calculate_exponential_weights(len(scores))
        
        # Calculate weighted prediction
        prediction = sum(score * weight for score, weight in zip(scores, weights))
        
        # Ensure reasonable bounds (0.0 to 3.0 for Z-scores)
        prediction = max(0.0, min(3.0, prediction))
        
        # Calculate confidence
        smoothness = self.calculate_smoothness(scores)
        recency_fit = self.calculate_recency_fit(scores, weights)
        
        # Point confidence (more points = higher confidence)
        point_confidence = min(1.0, len(scores) / 4.0)
        
        # Combined confidence (weighted average)
        confidence = (smoothness * 0.4 + 
                     recency_fit * 0.4 + 
                     point_confidence * 0.2)
        confidence = round(confidence, 2)
        
        return {
            'prediction': round(prediction, 4),
            'confidence': confidence,
            'method': 'exponential_smoothing',
            'alpha_used': round(alpha, 2),
            'weights_used': [round(w, 3) for w in weights]
        }


if __name__ == "__main__":
    # Test with different alpha values
    print("=" * 60)
    print("Exponential Smoothing Predictor Tests")
    print("=" * 60)
    
    scores = [1.50, 1.60, 1.70, 1.80]
    
    # Test 1: Conservative alpha (0.3)
    print("\nTest 1: Conservative smoothing (alpha=0.3)")
    predictor = ExponentialPredictor(alpha=0.3)
    result = predictor.predict(scores)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Weights: {result['weights_used']}")
    print(f"  (More balanced weights)")
    
    # Test 2: Balanced alpha (0.6)
    print("\nTest 2: Balanced smoothing (alpha=0.6) [DEFAULT]")
    predictor = ExponentialPredictor(alpha=0.6)
    result = predictor.predict(scores)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Weights: {result['weights_used']}")
    print(f"  (Recent data weighted more)")
    
    # Test 3: Aggressive alpha (0.9)
    print("\nTest 3: Aggressive smoothing (alpha=0.9)")
    predictor = ExponentialPredictor(alpha=0.9)
    result = predictor.predict(scores)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Weights: {result['weights_used']}")
    print(f"  (Heavily weighted to most recent)")
    
    # Test 4: Volatile data with auto-tune
    print("\nTest 4: Volatile data with auto-tuning")
    volatile_scores = [1.50, 1.90, 1.60, 1.88]
    predictor = ExponentialPredictor(alpha=0.6)
    result = predictor.predict(volatile_scores, auto_tune_alpha=True)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Alpha adjusted to: {result['alpha_used']}")
    print(f"  Weights: {result['weights_used']}")
