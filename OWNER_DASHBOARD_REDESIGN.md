# Owner Dashboard Redesign - Complete Guide
## Sabong Betting System

**Date:** May 24, 2026
**Status:** Design Specification Ready for Implementation

---

## 🎯 Current State Issues

### What's Missing
The current dashboard only shows:
- ✅ User counts (Admins, Tellers)
- ✅ Fight counts
- ✅ Total bets amount
- ✅ Commission earnings
- ❌ **Real-time fight metrics** (money in play, odds, current totals)
- ❌ **Performance indicators** (ROI, win rate, avg commission)
- ❌ **Daily/weekly trends** (revenue, bet volume)
- ❌ **Risk management** (max exposure, largest bets)
- ❌ **User activity** (active fights, tellers in use)
- ❌ **Quick actions** (rapid access to key functions)

---

## 📊 Proposed Dashboard Structure

### **Section 1: Executive Summary** (Top Priority)
The owner needs a quick glance at the business health.

#### 1A. Key Performance Indicators (KPIs) - 4 Cards
```
┌─────────────────────────────────────────────────────────┐
│ Today's Revenue      │ Total Handle      │ Avg Commission  │
│ ₱X,XXX.XX          │ ₱XX,XXX.XX       │ X.XX%          │
│ +5% vs yesterday     │ +12% vs avg      │ On target      │
│ Green indicator      │ Neutral          │ Green          │
└─────────────────────────────────────────────────────────┘
```

**Metrics:**
- **Today's Revenue**: Commission earned today (updates in real-time via WebSocket)
- **Total Handle**: Sum of all active bets across all fights
- **Average Commission Rate**: Weighted average commission from fights
- **Active Fights**: Number of currently open/betting fights

**Logic:**
```php
// Laravel Controller
$todayRevenue = Bet::whereDate('created_at', today())
    ->where('status', 'paid')
    ->sum(DB::raw('amount * (commission_rate / 100)'));

$totalHandle = Bet::where('status', 'open')
    ->orWhere('status', 'pending')
    ->sum('amount');

$avgCommission = Fight::whereIn('status', ['open', 'closed'])
    ->avg('commission_rate');

$activeFights = Fight::whereIn('status', ['open', 'closed'])->count();
```

#### 1B. Real-Time Fight Status Widget
```
┌────────────────────────────────────────────────┐
│ 🔴 ACTIVE: Fight #47                          │
│ Status: CLOSED                                 │
│ Meron: ₱45,000.00 | Wala: ₱52,500.00         │
│ Total Pool: ₱97,500.00 | Commission: ₱4,875  │
│ Updated: 30 seconds ago                        │
│ [View Details]                                 │
└────────────────────────────────────────────────┘
```

**Updated via WebSocket** when:
- `fight.updated` - status changes
- `bet.placed` - totals update
- `bet.deleted` - totals decrease

---

### **Section 2: Financial Analytics** (Second Priority)
Deep dive into money flow and profitability.

#### 2A. Revenue Breakdown Chart
```
Daily Revenue Trend (Last 7 Days)
───────────────────────────────────
₱5000 │     ╱╲
₱4000 │    ╱  ╲    ╱╲
₱3000 │   ╱    ╲  ╱  ╲
₱2000 │  ╱      ╲╱    ╲
₱1000 │ ╱              
    0 │──────────────────
     Mon Tue Wed Thu Fri Sat Sun

Total: ₱28,500 | Avg: ₱4,071/day
Best Day: Thursday (₱5,200)
```

#### 2B. Commission Source Breakdown
```
Commission by Source (Today)
─────────────────────────────
Fight #47   45%  ████████░
Fight #46   30%  ██████░░░
Fight #45   15%  ███░░░░░░
Fight #44   10%  ██░░░░░░░

Total: ₱4,875 | Per fight: ₱1,219 avg
```

---

### **Section 3: Betting Intelligence** (Third Priority)
Risk management and betting patterns.

#### 3A. Current Exposure
```
┌─────────────────────────────────────┐
│ FIGHT #47 - Money In Play           │
├─────────────────────────────────────┤
│ Meron: ₱45,000 | Wala: ₱52,500     │
│ Imbalance: Wala +₱7,500 (9%)       │
│ Win Scenarios:                      │
│  • If Meron wins: ₱8,625 payout    │
│  • If Wala wins: ₱9,975 payout    │
│  • Commission: ₱4,875              │
│ Your Exposure: ±₱5,100             │
└─────────────────────────────────────┘
```

#### 3B. Largest Active Bets
```
Top 5 Bets by Amount (Active)
──────────────────────────────
Teller: Juan    ₱25,000  Fight #47  Meron
Teller: Maria   ₱18,000  Fight #47  Wala
Teller: Pedro   ₱12,500  Fight #46  Meron
Teller: Rosa    ₱10,000  Fight #47  Meron
Teller: Carlos  ₱8,500   Fight #45  Wala
Total: ₱74,000
```

**Use Cases:**
- Spot betting patterns
- Identify largest risks
- Monitor teller activity

---

### **Section 4: Teller Performance** (Fourth Priority)
Who's bringing in money and activity.

#### 4A. Top Tellers by Commission
```
Teller Leaderboard (Today)
───────────────────────────
1. Juan      ₱2,100   127 bets   ✓ Active
2. Maria     ₱1,890   145 bets   ✓ Active
3. Pedro     ₱1,575   98 bets    ○ Inactive
4. Rosa      ₱1,200   75 bets    ✓ Active
5. Carlos    ₱950     62 bets    ✗ Offline

Your top performer: Juan (₱2,100 today)
```

#### 4B. Teller Cash Status
```
Teller Cash On-Hand
─────────────────────
Juan      ₱15,000  Last Updated: 2 min ago
Maria     ₱8,500   Last Updated: 5 min ago
Pedro     ₱0       Offline - last seen 1h ago
Rosa      ₱22,000  Last Updated: Now
Carlos    ✗        Offline

Total Cash Distributed: ₱45,500
```

---

### **Section 5: Session Management** (Fifth Priority)
Quick access to session controls.

#### 5A. Current Session Info
```
Session: Running
├─ Started: May 24, 09:00 AM
├─ Duration: 4 hours 30 minutes
├─ Fights Created: 47
├─ Total Bets Placed: 1,247
├─ Total Revenue: ₱28,500
└─ Status: Active ✓

[End Session] [Reset Counter] [Session History]
```

---

## 🎨 New Dashboard Layout

### Mobile Layout (Full Width Stack)
```
┌─ Header ───────────────────────┐
│ Owner Dashboard | WS: Connected│
└────────────────────────────────┘
┌─ KPIs (4 Cards, 2x2 Grid) ─────┐
│ Revenue│Handle  │ Avg Rate│Fights
│ ₱X,XXX│₱XX,XXX│X.XX%   │X
└────────────────────────────────┘
┌─ Active Fight Widget ──────────┐
│ Fight #47 | Closed | Pool: ₱97K│
│ Meron: ₱45K | Wala: ₱52K       │
└────────────────────────────────┘
┌─ Revenue Chart ────────────────┐
│ [7-Day Trend Graph]            │
└────────────────────────────────┘
┌─ Top Tellers (Scrollable) ─────┐
│ Juan    ₱2,100  145 bets       │
│ Maria   ₱1,890  128 bets       │
│ Pedro   ₱1,575  98 bets        │
└────────────────────────────────┘
┌─ Current Exposure ─────────────┐
│ Meron: ₱45K | Wala: ₱52K      │
│ Imbalance: +₱7.5K (9%)         │
└────────────────────────────────┘
┌─ Live Feed ────────────────────┐
│ [Real-time events]             │
│ Bet placed, Fight status, etc. │
└────────────────────────────────┘
```

### Tablet Layout (2-Column)
```
┌─────────────────────────┬─────────────────────────┐
│ KPIs Grid (4 cards)     │ Active Fight Widget     │
├─────────────────────────┤                         │
│ Revenue Chart (7 days)  │ Current Exposure       │
├─────────────────────────┤                         │
│ Commission Breakdown    │ Top 5 Bets             │
├─────────────────────────┴─────────────────────────┤
│ Teller Leaderboard (Horizontal Scrollable)       │
├─────────────────────────┬─────────────────────────┤
│ Teller Cash Status      │ Live Feed               │
└─────────────────────────┴─────────────────────────┘
```

### Desktop Layout (3-Column)
```
┌──────────────────┬──────────────────┬──────────────────┐
│ KPIs (4 cards)   │ Active Fight     │ Quick Actions    │
│                  │ Widget           │ [Links/Buttons]  │
├──────────────────┼──────────────────┴──────────────────┤
│ Revenue Chart    │ Current Exposure + Top Bets         │
│ (7 days)         │ (Side by side)                      │
├──────────────────┴──────────────────┬──────────────────┤
│ Commission Breakdown Chart          │ Teller Status    │
├─────────────────────────────────────┼──────────────────┤
│ Teller Leaderboard                  │ Session Info     │
├─────────────────────────────────────┴──────────────────┤
│ Live Feed (Auto-updating via WebSocket)                │
└──────────────────────────────────────────────────────┘
```

---

## 🔄 Real-Time Updates via WebSocket

### Event Listeners
```javascript
// Listen to key events and update UI
Echo.channel('fights')
  .listen('.fight.updated', (data) => {
    // Update: Active Fight Widget, KPIs
    updateFightWidget(data);
    updateKPIs();
  })
  .listen('.bet.placed', (data) => {
    // Update: KPIs, Current Exposure, Top Bets, Revenue
    updateKPIs();
    updateExposure();
    updateTopBets();
    updateRevenue();
  })
  .listen('.winner.declared', (data) => {
    // Update: KPIs, Revenue Chart, Teller Leaderboard
    updateKPIs();
    updateRevenueChart();
    updateTellerLeaderboard();
  });
```

### Data Refresh Strategy
- **KPIs**: Update on every `bet.placed` and `winner.declared`
- **Charts**: Refresh every 5 seconds (batch updates)
- **Live Feed**: Show latest 10 events (prepend new ones)
- **Teller Status**: Update on `teller.cash_updated` events

---

## 📐 Technical Implementation

### Backend Endpoints Needed

```php
// GET /owner/stats - Main dashboard data
Response: {
  "today_revenue": 4875.00,
  "total_handle": 97500.00,
  "active_fights": 1,
  "avg_commission": 5.00,
  "current_fight": { Fight object },
  "top_tellers": [ { teller_id, name, revenue, bets_count } ],
  "daily_revenue_7days": [ amount1, amount2, ... ],
  "commission_by_fight": [ { fight_number, amount, percentage } ],
  "largest_bets": [ { id, teller_name, amount, side, fight_number } ],
  "teller_cash_status": [ { teller_id, name, on_hand, last_updated } ]
}

// GET /owner/fight/{id}/exposure - Risk analysis
Response: {
  "fight_number": 47,
  "meron_total": 45000,
  "wala_total": 52500,
  "pool_total": 97500,
  "imbalance": { "side": "wala", "amount": 7500, "percentage": 7.7 },
  "scenarios": {
    "meron_wins": { "payout": 8625, "status": "gain" },
    "wala_wins": { "payout": 9975, "status": "gain" },
    "your_exposure": { "min": 5100, "max": 9975 }
  }
}

// GET /owner/revenue/daily - Revenue trend
Response: [
  { "date": "2026-05-17", "revenue": 3200 },
  { "date": "2026-05-18", "revenue": 4100 },
  ...
  { "date": "2026-05-24", "revenue": 4875 }
]
```

### Frontend Updates

**Blade Template Changes:**
1. Add Alpine.js for reactive component updates
2. Add Chart.js for revenue and commission charts
3. Implement real-time card updates on WebSocket events
4. Create reusable dashboard card components

**JavaScript Enhancements:**
- Cache stats in local state (refetch every 30 seconds if no events)
- Animate number changes (from → to with counters)
- Show delta indicators (up/down from previous period)
- Debounce chart updates (max 1 per 2 seconds)

---

## ✨ Key Features to Implement

### 1. **Real-Time Counter Animation**
```javascript
// When revenue updates, animate the number change
animateNumber(oldValue, newValue, element, duration = 600) {
  const increment = (newValue - oldValue) / 30;
  let current = oldValue;
  const timer = setInterval(() => {
    current += increment;
    if (current >= newValue) {
      current = newValue;
      clearInterval(timer);
    }
    element.textContent = formatCurrency(current);
  }, duration / 30);
}
```

### 2. **Status Indicators**
- 🟢 Green: Up from yesterday or on target
- 🟡 Yellow: Neutral or slight decline
- 🔴 Red: Significant decline or needs attention

### 3. **Responsive Grid System**
```
Mobile (< 640px):     1 column (cards stack)
Tablet (640-1024px):  2 columns
Desktop (> 1024px):   3-4 columns (optimized layout)
```

### 4. **Quick Action Buttons**
- [New Fight] - Create fight
- [View Fights] - See all fights
- [Teller Management] - Manage tellers
- [Reports] - Download reports
- [Settings] - Configure system

---

## 🎯 Priority Implementation Order

**Phase 1 (This Update):** 
- ✅ KPIs section (4 cards)
- ✅ Active fight widget
- ✅ Live feed (existing, keep it)

**Phase 2 (Next):**
- 📊 Revenue chart (7-day trend)
- 📈 Top tellers leaderboard
- 💰 Current exposure widget

**Phase 3 (Later):**
- 📉 Commission breakdown chart
- 🎲 Risk analysis (odds, scenarios)
- 👥 Teller cash status details
- 📋 Session management

---

## 💡 Best Practices

1. **Cache Data**: Store stats in a `$stats` variable in controller
2. **Avoid N+1 Queries**: Use `with()` for eager loading relationships
3. **Real-Time Updates**: Use WebSocket events to update without page refresh
4. **Mobile First**: Design for mobile, enhance for tablet/desktop
5. **Accessibility**: Use proper semantic HTML, ARIA labels
6. **Performance**: Debounce chart updates, cache images
7. **Error Handling**: Show fallback UI if stats API fails
8. **Loading States**: Skeleton screens while data loads

---

## 📱 Example Mobile View Priority
```
[Header - WS Status]
[KPI Cards - 2x2 Grid] ← Most important
[Active Fight Widget]  ← Real-time critical
[Revenue Chart]        ← Secondary info
[Top Tellers]          ← Performance tracking
[Live Feed]            ← Activity log
[Quick Actions]        ← Navigation
```

---

**Status:** Ready for blade template implementation
**Estimated Dev Time:** 2-3 hours for Phase 1
**Testing Requirements:** Mobile, tablet, and desktop responsiveness
