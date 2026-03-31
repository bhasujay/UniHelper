"""
Prediction Saver
Saves predictions to the database
"""

import sys
from pathlib import Path

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent.parent))

from config import DatabaseConfig
from datetime import date


class PredictionSaver:
    """
    Saves prediction results to z_score_data table
    """
    
    def __init__(self):
        """Initialize database configuration"""
        self.db_config = DatabaseConfig()
    
    def save_predictions(self, predictions, batch_size=1000):
        """
        Save predictions to database
        
        Args:
            predictions: List of prediction dictionaries from PredictionRunner
            batch_size: Number of records to insert at once (default: 1000)
        
        Returns:
            dict: {
                'inserted': int,
                'updated': int,
                'total_processed': int,
                'errors': list
            }
        """
        if not predictions:
            print("Warning: No predictions to save")
            return {
                'inserted': 0,
                'updated': 0,
                'total_processed': 0,
                'errors': []
            }
        
        conn, cursor = self.db_config.get_cursor()
        
        try:
            # SQL query with ON DUPLICATE KEY UPDATE
            # This handles both insert and update cases
            insert_query = """
                INSERT INTO z_score_data 
                (program_id, year, district, predicted_z_score, status, prediction_date)
                VALUES (%s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    predicted_z_score = VALUES(predicted_z_score),
                    prediction_date = VALUES(prediction_date),
                    status = VALUES(status)
            """
            
            # Prepare batch records
            records = []
            errors = []
            
            for pred in predictions:
                try:
                    record = (
                        pred['program_id'],
                        pred['year'],
                        pred['district'],
                        pred['predicted_z_score'],  # Can be None for NOC
                        'predicted',
                        date.today()
                    )
                    records.append(record)
                except KeyError as e:
                    errors.append(f"Missing key in prediction: {e}")
                except Exception as e:
                    errors.append(f"Error preparing record: {e}")
            
            if not records:
                return {
                    'inserted': 0,
                    'updated': 0,
                    'total_processed': 0,
                    'errors': errors
                }
            
            # Insert in batches
            total_processed = 0
            total_affected = 0
            
            for i in range(0, len(records), batch_size):
                batch = records[i:i + batch_size]
                cursor.executemany(insert_query, batch)
                conn.commit()
                
                total_processed += len(batch)
                total_affected += cursor.rowcount
                
                print(f"  Saved batch: {total_processed}/{len(records)} records")
            
            # Note: MySQL's rowcount for INSERT ... ON DUPLICATE KEY UPDATE:
            # - 1 = new row inserted
            # - 2 = existing row updated
            # - 0 = row unchanged
            # So we can't easily distinguish inserts from updates
            
            return {
                'inserted': total_affected,  # This is approximate
                'updated': 0,  # Can't determine exact count
                'total_processed': total_processed,
                'errors': errors
            }
            
        except Exception as e:
            conn.rollback()
            print(f"Error saving predictions: {e}")
            return {
                'inserted': 0,
                'updated': 0,
                'total_processed': 0,
                'errors': [str(e)]
            }
        
        finally:
            self.db_config.close_connection(conn)
    
    def clear_existing_predictions(self, year):
        """
        Clear existing predictions for a specific year
        Useful before re-running predictions
        
        Args:
            year: Year to clear predictions for (e.g., 2026)
        
        Returns:
            int: Number of records deleted
        """
        conn, cursor = self.db_config.get_cursor()
        
        try:
            delete_query = """
                DELETE FROM z_score_data 
                WHERE year = %s AND status = 'predicted'
            """
            
            cursor.execute(delete_query, (year,))
            conn.commit()
            
            deleted_count = cursor.rowcount
            print(f"Cleared {deleted_count} existing predictions for year {year}")
            
            return deleted_count
            
        except Exception as e:
            conn.rollback()
            print(f"Error clearing predictions: {e}")
            return 0
        
        finally:
            self.db_config.close_connection(conn)
    
    def verify_predictions(self, year):
        """
        Verify predictions were saved correctly
        
        Args:
            year: Year to verify (e.g., 2026)
        
        Returns:
            dict: Statistics about saved predictions
        """
        conn, cursor = self.db_config.get_cursor()
        
        try:
            # Count total predictions
            cursor.execute("""
                SELECT COUNT(*) as total
                FROM z_score_data
                WHERE year = %s AND status = 'predicted'
            """, (year,))
            total = cursor.fetchone()['total']
            
            # Count NOC predictions (NULL z_score)
            cursor.execute("""
                SELECT COUNT(*) as noc_count
                FROM z_score_data
                WHERE year = %s AND status = 'predicted' AND predicted_z_score IS NULL
            """, (year,))
            noc_count = cursor.fetchone()['noc_count']
            
            # Count valid predictions (non-NULL z_score)
            valid_count = total - noc_count
            
            # Get Z-score statistics
            cursor.execute("""
                SELECT 
                    MIN(predicted_z_score) as min_score,
                    MAX(predicted_z_score) as max_score,
                    AVG(predicted_z_score) as avg_score
                FROM z_score_data
                WHERE year = %s AND status = 'predicted' AND predicted_z_score IS NOT NULL
            """, (year,))
            stats = cursor.fetchone()
            
            return {
                'year': year,
                'total': total,
                'valid_count': valid_count,
                'noc_count': noc_count,
                'min_z_score': float(stats['min_score']) if stats['min_score'] else 0.0,
                'max_z_score': float(stats['max_score']) if stats['max_score'] else 0.0,
                'avg_z_score': float(stats['avg_score']) if stats['avg_score'] else 0.0
            }
            
        except Exception as e:
            print(f"Error verifying predictions: {e}")
            return {
                'year': year,
                'total': 0,
                'valid_count': 0,
                'noc_count': 0,
                'error': str(e)
            }
        
        finally:
            self.db_config.close_connection(conn)


# Test the prediction saver
if __name__ == "__main__":
    print("Testing PredictionSaver...")
    
    # Create sample predictions
    sample_predictions = [
        {
            'program_id': 1,
            'district': 'Colombo',
            'year': 2026,
            'predicted_z_score': 1.8500,
            'confidence': 0.92,
            'is_noc': False
        },
        {
            'program_id': 1,
            'district': 'Gampaha',
            'year': 2026,
            'predicted_z_score': 1.6500,
            'confidence': 0.88,
            'is_noc': False
        },
        {
            'program_id': 2,
            'district': 'Colombo',
            'year': 2026,
            'predicted_z_score': None,  # NOC
            'confidence': 0.95,
            'is_noc': True
        }
    ]
    
    saver = PredictionSaver()
    
    # Test saving
    print("\nSaving predictions...")
    result = saver.save_predictions(sample_predictions)
    print(f"Result: {result}")
    
    # Test verification
    print("\nVerifying predictions...")
    verification = saver.verify_predictions(2026)
    print(f"Verification:")
    print(f"  Total: {verification['total']}")
    print(f"  Valid: {verification['valid_count']}")
    print(f"  NOC: {verification['noc_count']}")
    if verification['valid_count'] > 0:
        print(f"  Z-score range: {verification['min_z_score']} - {verification['max_z_score']}")
