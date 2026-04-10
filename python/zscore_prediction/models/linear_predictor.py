class LinearPredictor:

    def __init__(self):
        self.min_points = 2
    
    def detect_outliers(self, scores):
        if len(scores) < 3:
            return -1
        #calculate mean and standard deviation
        mean = sum(scores) / len(scores)
        variance = sum((x - mean) ** 2 for x in scores) / len(scores)
        std_dev = variance ** 0.5   

        # Check if any score is more that 2 std deviations away 
        for i, score in enumerate(scores):
            if abs(score - mean) > 2 * std_dev:
                return i
        return -1
    
    def calculate_confidence(self, years, scores, prediction):
        
        if len(scores) < 2:
            return 0.0
        
        # Calculate variance 
        mean = sum(scores) / len(scores)
        variance = sum((x - mean) ** 2 for x in scores) / len(scores)
        variance_confidence = max(0.0, 1.0 - variance)

        # check trend consistency 
        changes = [scores[i+1] - scores[i] for i in range(len(scores)-1)]
        if len(changes) >= 2:
            # All changes same direction = consistent
            all_positive = all(c >= 0 for c in changes)
            all_negative = all(c <= 0 for c in changes)
            trend_confidence = 1.0 if (all_positive or all_negative) else 0.5
        else:
            trend_confidence = 0.5

        min_score = min(scores)
        max_score = max(scores)
        range_buffer = (max_score - min_score) * 0.5
        
        if min_score - range_buffer <= prediction <= max_score + range_buffer:
            bounds_confidence = 1.0
        else:
            bounds_confidence = 0.5
        
                # Combined confidence
        confidence = (variance_confidence * 0.4 + 
                     trend_confidence * 0.4 + 
                     bounds_confidence * 0.2)
        
        return round(confidence, 2)
    
    def predict(self, years, scores, remove_outliers=True):
        if not years or not scores or len(years) < self.min_points:
            return {
                'prediction': None,
                'confidence': 0.0,
                'method': 'linear'
            }
        
        # Check for outliers
        working_years = list(years)
        working_scores = list(scores)
        
        if remove_outliers and len(scores) >= 3:
            outlier_idx = self.detect_outliers(scores)
            if outlier_idx != -1:
                # Remove outlier
                working_years = [y for i, y in enumerate(years) if i != outlier_idx]
                working_scores = [s for i, s in enumerate(scores) if i != outlier_idx]
        
        if len(working_scores) < self.min_points:
            return {
                'prediction': None,
                'confidence': 0.0,
                'method': 'linear'
            }
        
        # Linear regression: y = mx + b
        n = len(working_years)
        x_mean = sum(working_years) / n
        y_mean = sum(working_scores) / n

        numerator = sum((working_years[i] - x_mean) * (working_scores[i] - y_mean) 
                       for i in range(n))
        denominator = sum((working_years[i] - x_mean) ** 2 for i in range(n))
        
        if denominator == 0:
            # All years same (shouldn't happen) - return mean
            return {
                'prediction': round(y_mean, 4),
                'confidence': 0.5,
                'method': 'linear'
            }
        
        m = numerator / denominator
        b = y_mean - m * x_mean
        
        # Predict next year
        next_year = years[-1] + 1
        prediction = m * next_year + b
        
        # Ensure reasonable bounds (0.0 to 3.0 for Z-scores)
        prediction = max(0.0, min(3.0, prediction))
        
        # Calculate confidence
        confidence = self.calculate_confidence(working_years, working_scores, prediction)
        
        return {
            'prediction': round(prediction, 4),
            'confidence': confidence,
            'method': 'linear',
            'outlier_removed': len(scores) != len(working_scores)
        }

if __name__ == "__main__":
    predictor = LinearPredictor()
    
    # Test normal trend
    print("Test 1: Normal upward trend")
    years = [2022, 2023, 2024, 2025]
    scores = [1.8, 1.82, 1.85, 1.87]
    result = predictor.predict(years, scores)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    
    # Test with outlier
    print("\nTest 2: With outlier")
    scores_outlier = [1.8, 1.82, 2.5, 1.87]  # 2.5 is outlier
    result = predictor.predict(years, scores_outlier)
    print(f"  Prediction: {result['prediction']}")
    print(f"  Confidence: {result['confidence']}")
    print(f"  Outlier removed: {result.get('outlier_removed', False)}") 