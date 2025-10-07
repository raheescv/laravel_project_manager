# Profit-Based Trading Command

This command analyzes historical trading data for a given symbol and makes buy/sell decisions based on profit potential analysis.

## Usage

```bash
php artisan trade:profit-based <symbol> [options]
```

## Arguments

- `symbol` (required): Trading symbol (e.g., INFY-EQ, RELIANCE-EQ)

## Options

- `--quantity=N` (default: 10): Quantity to trade
- `--exchange=EXCHANGE` (default: NSE): Exchange (NSE/BSE)
- `--min-profit-percent=N` (default: 5): Minimum profit percentage to trigger buy
- `--max-loss-percent=N` (default: 3): Maximum loss percentage to trigger sell
- `--lookback-days=N` (default: 30): Number of days to analyze for history
- `--dry-run`: Run without placing actual orders (recommended for testing)

## Examples

### Basic Usage
```bash
php artisan trade:profit-based INFY-EQ
```

### With Custom Parameters
```bash
php artisan trade:profit-based RELIANCE-EQ --quantity=50 --min-profit-percent=8 --max-loss-percent=5 --lookback-days=60
```

### Dry Run (Testing)
```bash
php artisan trade:profit-based TCS-EQ --dry-run
```

### BSE Exchange
```bash
php artisan trade:profit-based RELIANCE-EQ --exchange=BSE
```

## How It Works

1. **Historical Data Fetching**: Retrieves EOD (End of Day) chart data for the specified symbol over the lookback period
2. **Profit Analysis**: Calculates:
   - Average price over the period
   - Maximum and minimum prices
   - Volatility (standard deviation)
   - Profit potential percentage
   - Trend analysis (upward/downward/sideways)
   - Risk level assessment
3. **Current Price**: Fetches real-time market price
4. **Decision Making**: Compares current price with historical analysis to determine:
   - BUY: If current price is below average with upward trend and meets profit threshold
   - SELL: If current price is above average with downward trend and exceeds loss threshold
   - HOLD: If no clear profitable opportunity is found
5. **Order Execution**: Places market orders through FlatTrade API (unless in dry-run mode)

## Decision Logic

The command uses the following logic to make trading decisions:

### BUY Conditions
- Current price is below historical average
- Upward trend detected
- Potential profit meets minimum threshold
- OR low risk opportunity with decent profit potential

### SELL Conditions
- Current price is above historical average
- Downward trend detected
- Potential loss exceeds maximum threshold

### HOLD Conditions
- No clear profitable opportunity found
- Mixed signals or insufficient data

## Risk Assessment

The command categorizes risk levels based on volatility:
- **LOW**: Volatility < 2%
- **MEDIUM**: Volatility 2-5%
- **HIGH**: Volatility > 5%

## Confidence Scoring

Each trading decision includes a confidence score (0-100%) based on:
- Profit/loss potential percentage
- Risk level adjustment
- Trend strength
- Amount of historical data available

## Logging

All trading decisions and analysis results are logged to the Laravel log files for audit purposes.

## Prerequisites

- FlatTrade API credentials must be configured
- Valid FlatTrade access token must be available
- Symbol must be valid and tradeable on the specified exchange

## Safety Features

- Dry-run mode for testing without placing actual orders
- Comprehensive error handling and logging
- Risk level assessment to avoid high-risk trades
- Confidence scoring to validate decisions

## Integration with Existing System

This command integrates with:
- FlatTradeService for API calls
- Laravel's logging system
- Existing trading infrastructure
- Cache system for performance optimization
