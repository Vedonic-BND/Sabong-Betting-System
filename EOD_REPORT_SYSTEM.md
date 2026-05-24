# End-of-Day (EOD) Report System

## 📊 What's Generated

Every day at **11:59 PM**, the system automatically generates and saves 4 files:

### 1. **Audit Logs Report** (`audit-logs-eod-YYYY-MM-DD.csv`)
- Every action performed that day
- Who did it, when, from which IP
- What changed (payload details)
- Complete audit trail for compliance

### 2. **Fights Report** (`fights-eod-YYYY-MM-DD.csv`)
- All fights created/completed that day
- Meron total + Wala total (bets collected)
- Commission calculated and deducted
- Net pool available
- Who created each fight

### 3. **Runner Transactions Report** (`transactions-eod-YYYY-MM-DD.csv`)
- All cash provided to tellers (Cash In)
- All cash collected from tellers (Cash Out)
- Runner and Teller names
- Exact amounts and timestamps

### 4. **Summary Report** (`summary-eod-YYYY-MM-DD.txt`)
- Quick overview of the day
- Total fights, bets, commissions
- Cash movement totals
- Number of users active
- All transactions count

## 📁 File Location

All EOD reports are saved in:
```
storage/eod-reports/
```

## 🔄 How It Works

1. **Automatic Scheduling**: Laravel scheduler runs `php artisan eod:report` daily at 23:59
2. **Data Collection**: Gathers all data from that day only
3. **CSV Export**: Creates Excel-compatible CSV files
4. **Summary**: Generates human-readable summary in TXT format
5. **Logging**: Records the action in application logs

## 📋 What Owner Can Do

Download these reports daily to:
- ✅ Verify all money movements
- ✅ Audit all actions taken
- ✅ Cross-reference fights with transactions
- ✅ Detect discrepancies or fraud
- ✅ Maintain compliance records

## 🔍 Money Reconciliation

Using EOD reports to verify the day:

```
MONEY IN:
  + Total Bets Collected (from Fights Report)
  + Total Cash Provided to Tellers (from Transactions Report)

MONEY OUT:
  + Commission Amount (from Fights Report)
  + Payouts Paid (from Fights Report)
  + Cash Collected from Tellers (from Transactions Report)

Money IN - Money OUT should balance to 0 ✓
```

## ⏰ Schedule

- **Time**: 11:59 PM daily (23:59 in 24-hour format)
- **Frequency**: Daily, automatic
- **Overlapping**: Prevented (only one report per day)
- **Logging**: Recorded in `storage/logs/eod-report.log`

## 🛠️ Manual Generation

To generate EOD report manually:
```bash
php artisan eod:report
```

## 📝 Example Output

```
═══════════════════════════════════════════════════════════════
                    END-OF-DAY REPORT
                    2026-05-24
═══════════════════════════════════════════════════════════════

FIGHTS SUMMARY
Total Fights: 11
Total Bets Collected: ₱51,000.00
Total Commission: ₱2,550.00
Net Pool: ₱48,450.00

RUNNER TRANSACTIONS SUMMARY
Total Cash Provided to Tellers: ₱20,018.00
Total Cash Collected from Tellers: ₱20,018.00
Net Cash Movement: ₱0.00
Total Transactions: 37

AUDIT LOGS SUMMARY
Total Actions Recorded: 127
Users Active: 2
```

---

**This system ensures complete transparency and accountability for the entire day's operations!** 🔒
