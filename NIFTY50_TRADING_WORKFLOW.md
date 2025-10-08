# Nifty 50 Trading & Selling Workflow

## Complete Workflow: Buy → Monitor → Sell

### Step 1: Create Nifty 50 Positions (Buy)
```bash
# Test with dry run first
php artisan trade:nifty50-real --dry-run --max-stocks=3 --quantity=1

# Execute real trading (creates positions)
php artisan trade:nifty50-real --max-stocks=5 --quantity=1 --order-type=bracket
```

### Step 2: Monitor Your Positions
```bash
# Check what positions you have
php artisan trade:sell-nifty50 --dry-run --all

# This will show all your Nifty 50 positions with P&L
```

### Step 3: Sell Positions Based on Strategy

#### Strategy 1: Profit Taking
```bash
# Sell positions with 3% or more profit
php artisan trade:sell-nifty50 --profit-threshold=3.0 --dry-run

# Execute real selling
php artisan trade:sell-nifty50 --profit-threshold=3.0
```

#### Strategy 2: Loss Cutting
```bash
# Sell positions with 2% or more loss
php artisan trade:sell-nifty50 --loss-threshold=2.0 --dry-run

# Execute real selling
php artisan trade:sell-nifty50 --loss-threshold=2.0
```

#### Strategy 3: Balanced Approach
```bash
# Sell positions with 2% profit OR 3% loss
php artisan trade:sell-nifty50 --profit-threshold=2.0 --loss-threshold=3.0 --dry-run

# Execute real selling
php artisan trade:sell-nifty50 --profit-threshold=2.0 --loss-threshold=3.0
```

#### Strategy 4: Sell Everything (Emergency)
```bash
# Sell all Nifty 50 positions regardless of P&L
php artisan trade:sell-nifty50 --all --dry-run

# Execute real selling
php artisan trade:sell-nifty50 --all
```

## Quick Commands Reference

### Buying Commands
```bash
# Test buying (dry run)
php artisan trade:nifty50-real --dry-run --max-stocks=3

# Real buying
php artisan trade:nifty50-real --max-stocks=5 --quantity=1
```

### Selling Commands
```bash
# Check positions
php artisan trade:sell-nifty50 --dry-run --all

# Sell with profit
php artisan trade:sell-nifty50 --profit-threshold=3.0

# Sell with loss protection
php artisan trade:sell-nifty50 --loss-threshold=2.0

# Sell everything
php artisan trade:sell-nifty50 --all
```

## Example Complete Session

```bash
# 1. First, test the buying process
php artisan trade:nifty50-real --dry-run --max-stocks=3 --quantity=1

# 2. If satisfied, execute real buying
php artisan trade:nifty50-real --max-stocks=3 --quantity=1 --order-type=bracket

# 3. Check your positions
php artisan trade:sell-nifty50 --dry-run --all

# 4. Sell positions with profit (test first)
php artisan trade:sell-nifty50 --profit-threshold=2.0 --dry-run

# 5. Execute real selling
php artisan trade:sell-nifty50 --profit-threshold=2.0
```

## Safety Tips

1. **Always test with `--dry-run` first**
2. **Start with small quantities**
3. **Set appropriate profit/loss thresholds**
4. **Monitor your positions regularly**
5. **Use `--all` only in emergencies**

## Command Options

### Buying Options (`trade:nifty50-real`)
- `--max-stocks`: Number of stocks to buy (default: 5)
- `--quantity`: Quantity per stock (default: 1)
- `--min-profit`: Minimum profit % required (default: 2.0)
- `--max-loss`: Maximum loss % allowed (default: 3.0)
- `--dry-run`: Test mode without real orders
- `--order-type`: market, limit, bracket (default: market)
- `--product`: C, H, B (default: C)

### Selling Options (`trade:sell-nifty50`)
- `--profit-threshold`: Minimum profit % to sell (default: 2.0)
- `--loss-threshold`: Maximum loss % to sell (default: 3.0)
- `--dry-run`: Test mode without real orders
- `--order-type`: market, limit (default: market)
- `--all`: Sell all positions regardless of P&L

## Web Interface

You can also use the web interface at:
- **Trading Dashboard**: `/flat_trade/nifty50/`
- **View Positions**: `/flat_trade/nifty50/positions`

## Troubleshooting

### No positions found?
- Make sure you've run the buying command first
- Check if market is open
- Verify FlatTrade API connection

### Selling failed?
- Check if you have sufficient quantity
- Verify market hours
- Check account permissions

### Need help?
- Always use `--dry-run` to test
- Check logs in `storage/logs/laravel.log`
- Start with small quantities
