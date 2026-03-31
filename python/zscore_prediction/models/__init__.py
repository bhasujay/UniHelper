"""
Machine Learning Prediction Models for Z-Score Forecasting

This package contains three prediction methods and an ensemble combiner:
- LinearPredictor: Uses linear regression for trend-based predictions
- WMAPredictor: Uses weighted moving average with configurable weights
- ExponentialPredictor: Uses exponential smoothing with automatic weight calculation
- EnsemblePredictor: Combines all three methods with confidence weighting
"""

from .linear_predictor import LinearPredictor
from .wma_predictor import WMAPredictor
from .exponential_predictor import ExponentialPredictor
from .ensemble_predictor import EnsemblePredictor

__all__ = [
    'LinearPredictor',
    'WMAPredictor', 
    'ExponentialPredictor',
    'EnsemblePredictor'
]
