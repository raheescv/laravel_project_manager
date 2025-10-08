# Nifty 50 Real Trading System

## Overview

This system provides comprehensive real-time trading functionality for Nifty 50 stocks with actual market data integration. It includes automated stock selection, risk management, and real order execution capabilities.

## Features

### 🚀 Core Functionality
- **Real-time Stock Selection**: Automatically identifies best performing Nifty 50 stocks
- **Actual Order Execution**: Places real orders with live market data
- **Risk Management**: Comprehensive risk controls and validation
- **Multiple Order Types**: Market, Limit, and Bracket orders
- **Dry Run Mode**: Test trading logic without placing actual orders

### 📊 Stock Analysis
- **Top Gainers Integration**: Uses FlatTrade API to get real-time top gainers
- **Suitability Scoring**: Advanced algorithm to score stocks for trading
- **Volume Analysis**: Filters stocks based on trading volume
- **Price Range Validation**: Ensures stocks are within tradeable price ranges

### 🛡️ Risk Management
- **Daily Limits**: Configurable daily investment and position limits
- **Market Hours Validation**: Prevents trading outside market hours
- **Portfolio Risk Checks**: Monitors overall portfolio exposure
- **User Risk Profiles**: Conservative, Moderate, and Aggressive profiles

## Installation & Setup

### 1. Command Line Interface

```bash
# Register the command in app/Console/Kernel.php
protected $commands = [
    Commands\Nifty50RealTradingCommand::class,
];

# Run dry run test
php artisan trade:nifty50-real --dry-run --max-stocks=3 --quantity=1

# Execute real trading (CAUTION: Uses real money)
php artisan trade:nifty50-real --max-stocks=5 --quantity=1 --order-type=bracket
```

### 2. Web Interface

Access the web interface at: `/flat_trade/nifty50/`

### 3. API Endpoints

```bash
# Get best performing stocks
GET /flat_trade/nifty50/best-stocks?max_stocks=5&min_change_percent=1.0

# Execute trading orders
POST /flat_trade/nifty50/execute-trading

# Get market status
GET /flat_trade/nifty50/market-status

# Get user positions
GET /flat_trade/nifty50/positions
```

## Configuration Options

### Command Line Options

| Option | Description | Default | Range |
|--------|-------------|---------|-------|
| `--quantity` | Quantity per stock | 1 | 1-1000 |
| `--max-stocks` | Maximum stocks to trade | 5 | 1-15 |
| `--min-profit` | Minimum profit % | 2.0 | 0.1-50 |
| `--max-loss` | Maximum loss % | 3.0 | 0.1-50 |
| `--dry-run` | Test mode (no real orders) | false | - |
| `--order-type` | Order type | market | market/limit/bracket |
| `--product` | Product type | C | C/H/B |

### Risk Management Settings

```php
// Conservative Profile
'max_daily_investment' => 50000,
'max_position_size' => 25000,
'max_stocks_per_day' => 5,
'max_loss_per_trade' => 3.0,

// Moderate Profile (Default)
'max_daily_investment' => 100000,
'max_position_size' => 50000,
'max_stocks_per_day' => 10,
'max_loss_per_trade' => 5.0,

// Aggressive Profile
'max_daily_investment' => 200000,
'max_position_size' => 100000,
'max_stocks_per_day' => 15,
'max_loss_per_trade' => 8.0,
```

## Usage Examples

### 1. Command Line Usage

```bash
# Test with 3 stocks, dry run
php artisan trade:nifty50-real --dry-run --max-stocks=3 --quantity=1

# Real trading with bracket orders
php artisan trade:nifty50-real --max-stocks=5 --quantity=2 --order-type=bracket --min-profit=3.0 --max-loss=2.0

# Conservative trading
php artisan trade:nifty50-real --max-stocks=3 --quantity=1 --order-type=limit --min-profit=1.5 --max-loss=2.5
```

### 2. Web Interface Usage

1. **Access Dashboard**: Navigate to `/flat_trade/nifty50/`
2. **Configure Settings**: Set quantity, order type, profit/loss targets
3. **Find Best Stocks**: Click "Find Best Stocks" to load current top performers
4. **Select Stocks**: Check boxes for stocks you want to trade
5. **Test First**: Use "Dry Run" to test without real money
6. **Execute**: Use "Execute Real Trading" for actual orders

### 3. API Integration

```javascript
// Get best stocks
const response = await fetch('/flat_trade/nifty50/best-stocks?max_stocks=5');
const stocks = await response.json();

// Execute trading
const tradingData = {
    stocks: [
        { symbol: 'RELIANCE', quantity: 1 },
        { symbol: 'TCS', quantity: 1 }
    ],
    order_type: 'bracket',
    product: 'C',
    min_profit_percent: 2.0,
    max_loss_percent: 3.0,
    confirm_trading: true
};

const result = await fetch('/flat_trade/nifty50/execute-trading', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(tradingData)
});
```

## Stock Selection Algorithm

### Suitability Scoring

The system uses a multi-factor scoring algorithm:

1. **Change Percentage Score** (0-20 points)
   - 1-10% gain: Full points
   - >10% gain: Penalty for extreme volatility

2. **Volume Score** (0-10 points)
   - >1M volume: 10 points
   - >500K volume: 5 points

3. **Price Stability Score** (0-5 points)
   - <5% daily range: 5 points

### Filtering Criteria

- **Price Range**: ₹50 - ₹10,000
- **Minimum Change**: 1% gain
- **Minimum Volume**: 100,000 shares
- **Maximum Volatility**: 20% change
- **Nifty 50 Membership**: Must be in predefined list

## Risk Management Features

### 1. Pre-Trade Validation

- Market hours check
- Daily investment limits
- Position size validation
- Stock suitability verification
- Portfolio risk assessment

### 2. Real-Time Monitoring

- Order status tracking
- P&L monitoring
- Risk limit enforcement
- Automatic stop-loss execution

### 3. User Protection

- Confirmation dialogs for real trading
- Dry run mode for testing
- Comprehensive logging
- Error handling and recovery

## Order Types Supported

### 1. Market Orders
- Immediate execution at current market price
- Best for liquid stocks
- No price guarantee

### 2. Limit Orders
- Execute only at specified price or better
- Price protection
- May not execute if price moves away

### 3. Bracket Orders
- Entry + Stop Loss + Target in one order
- Automated risk management
- Best for systematic trading

## Nifty 50 Stock List

The system includes all 50 Nifty stocks:

```
RELIANCE, TCS, HDFCBANK, INFY, HINDUNILVR, ITC, SBIN, BHARTIARTL,
KOTAKBANK, LT, ASIANPAINT, AXISBANK, MARUTI, SUNPHARMA, TITAN, ULTRACEMCO,
WIPRO, NESTLEIND, ONGC, POWERGRID, NTPC, TECHM, TATAMOTORS, BAJFINANCE,
HCLTECH, BAJAJFINSV, DRREDDY, JSWSTEEL, TATASTEEL, COALINDIA, GRASIM, BRITANNIA,
EICHERMOT, HEROMOTOCO, DIVISLAB, CIPLA, APOLLOHOSP, ADANIPORTS, INDUSINDBK, TATACONSUM,
BPCL, ICICIBANK, ADANIENT, HDFCLIFE, SBILIFE, BAJAJ-AUTO, UPL, SHREECEM
```

## Security & Permissions

### Required Permissions

- `flat_trade.view`: View market data and positions
- `flat_trade.trade`: Execute trading orders

### Authentication

- User must be authenticated
- FlatTrade account must be connected
- API credentials must be valid

## Monitoring & Logging

### Log Files

All trading activities are logged to:
- `storage/logs/laravel.log`
- Custom trading logs with detailed information

### Key Metrics Tracked

- Order execution status
- Profit/Loss per trade
- Risk limit compliance
- System performance
- Error rates

## Troubleshooting

### Common Issues

1. **"Market is closed" error**
   - Check market hours (9:15 AM - 3:30 PM IST)
   - Verify system timezone settings

2. **"No suitable stocks found"**
   - Adjust minimum change percentage
   - Check if market is active
   - Verify FlatTrade API connection

3. **Order execution failures**
   - Check account balance
   - Verify stock symbols
   - Review risk limits

### Debug Mode

```bash
# Enable debug logging
php artisan trade:nifty50-real --dry-run -v

# Check API connectivity
php artisan flat_trade:test-api
```

## Best Practices

### 1. Start Small
- Begin with dry run mode
- Test with small quantities
- Verify system behavior

### 2. Risk Management
- Set appropriate stop losses
- Don't exceed daily limits
- Monitor positions regularly

### 3. Market Conditions
- Avoid trading during high volatility
- Check market status before trading
- Consider market trends

### 4. Regular Monitoring
- Review trading results
- Adjust risk parameters
- Update stock selection criteria

## Support & Maintenance

### Regular Updates
- Stock list updates (quarterly)
- Risk parameter adjustments
- Performance optimizations

### System Requirements
- PHP 8.1+
- Laravel 10+
- FlatTrade API access
- Sufficient server resources

### Contact
For technical support or feature requests, contact the development team.

---

**⚠️ IMPORTANT DISCLAIMER**

This system executes real trades with actual money. Always:
- Test thoroughly in dry run mode first
- Start with small amounts
- Understand the risks involved
- Monitor your positions actively
- Never trade more than you can afford to lose

Trading involves substantial risk and may not be suitable for all investors.
