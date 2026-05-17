# Quick Reference: Request & Notification System

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                          SABONG BETTING SYSTEM                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  WEB APP (Laravel)                  ANDROID APP (Kotlin)            │
│  ├─ Controllers                     ├─ ViewModels                   │
│  │  ├─ CashRequestController       │  ├─ RunnerViewModel          │
│  │  ├─ RunnerAssistanceController  │  ├─ CashInViewModel          │
│  │  └─ NotificationController      │  └─ ReverbViewModel          │
│  │                                 │                               │
│  ├─ Models                         ├─ Data Models                  │
│  │  ├─ CashRequest                │  ├─ RunnerNotification        │
│  │  ├─ Notification               │  ├─ TellerNotification        │
│  │  └─ User                        │  └─ TellerCashStatus          │
│  │                                 │                               │
│  └─ Events                         └─ Real-Time Connection         │
│     ├─ CashRequestCreated            ├─ ReverbManager             │
│     ├─ RunnerAccepted                ├─ WebSocket listeners       │
│     └─ ...                           └─ Callback handlers         │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                        SHARED LAYER (APIs)                          │
├─────────────────────────────────────────────────────────────────────┤
│  HTTP APIs (REST)          WebSocket (Reverb)                       │
│  ├─ POST /api/assistance   ├─ cash-requests                         │
│  ├─ GET /api/notifications └─ private-notifications-{id}           │
│  └─ POST /api/notifications                                        │
├─────────────────────────────────────────────────────────────────────┤
│                           DATABASE                                  │
├─────────────────────────────────────────────────────────────────────┤
│  ├─ cash_requests (CashRequest)                                    │
│  ├─ notifications (Notification)                                   │
│  └─ users                                                          │
└─────────────────────────────────────────────────────────────────────┘
```

## Request Lifecycle

```
TELLER              SERVER              RUNNER              OWNER
  │                   │                   │                   │
  │─ Request Runner ──┤                   │                   │
  │  (POST /assistance│                   │                   │
  │   /request)       │                   │                   │
  │                   │                   │                   │
  │                   ├─ Create           │                   │
  │                   │ CashRequest       │                   │
  │                   │                   │                   │
  │                   ├─ Broadcast        │                   │
  │                   │ Event             │                   │
  │                   │                   │                   │
  │                   ├──────────────────┬┴──────────────────┤
  │                   │ Save             │                   │
  │                   │ Notification     │ WebSocket Event   │ Notification
  │                   │ to DB            │                   │
  │                   │                  ├─ Sound/Vibration  │
  │                   │                  │ Popup Alert       │
  │                   │                  │                   │
  │                   │                  ├─ Accept Request   │
  │                   │      (POST /assistance/accept) ─────►│
  │                   │                  │                   │
  │◄─────────────────┬┴──────────────────┤                   │
│ Notification      │ Broadcast        │                   │
  │ (Runner         │ RunnerAccepted   │                   │
  │  Assigned)      │ Event            │                   │
  │                 │                  │                   │
  │                 │                  ├─────────────────►┤
  │                 │                  │ Notification     │
  │                 │                  │ (Assignment OK)  │
  │                 │                  │                  │
  └─────────────────┴──────────────────┴──────────────────┘
```

## Notification Types Reference

| Type | Trigger | Recipients | Title | Data Includes |
|------|---------|------------|-------|---------------|
| **Runner Request** | Teller requests help | Runners | "Runner Request" | teller_id, request_type |
| **Runner Assigned** | Request accepted | Teller | "Runner Assigned" | runner_id, runner_name |
| **New Assignment** | Owner assigns runner | Runner | "New Assignment" | teller_id, teller_name |
| **Assignment Success** | Manual assignment done | Owner | "Assignment Successful" | runner_id, teller_id |
| **Assistance Needed** | Any request sent | Owner/Admin | "Assistance Needed" | teller_id, request_type |

## Key Flow Summary

### Teller Perspective
```
Open App
  ↓
RequestRunnerScreen (select assistance type)
  ↓
CashInViewModel.requestRunner()
  ↓
POST /api/assistance/request
  ↓
Notification saved to DB
  ↓
WebSocket broadcast to runners
  ↓
Wait for runner to accept...
  ↓
GET /api/notifications (polling)
  ↓
Display "Runner {name} assigned"
```

### Runner Perspective
```
Open App
  ↓
ReverbManager.connect()
  ↓
Listen on "cash-requests" channel
  ↓
Receive CashRequestCreated event
  ↓
Add to notifications list
  ↓
Sound + Vibration alert
  ↓
Choose: Accept / Ignore
  ↓
POST /api/assistance/accept/{tellerId}
  ↓
Receive RunnerAccepted broadcast
  ↓
Navigate to transaction screen
```

## Database Schema Excerpt

### cash_requests
```
id: int (PK)
teller_id: int (FK → users)
runner_id: int (FK → users)
type: enum['cash_in', 'cash_out']
amount: decimal(10,2)
request_type: enum['assistance', 'need_cash', 'collect_cash', 'other']
custom_message: string
status: enum['pending', 'approved', 'completed', 'rejected']
created_at: timestamp
approved_at: timestamp (nullable)
completed_at: timestamp (nullable)
```

### notifications
```
id: int (PK)
user_id: int (FK → users)
title: string
message: text
data: json (nullable)
is_read: boolean
created_at: timestamp
updated_at: timestamp
```

## Configuration Reference

### Reverb (WebSocket)
- **Host:** 0.0.0.0
- **Port:** 8000
- **Connection URL:** ws://{host}:8000/app/{key}
- **Channels:** cash-requests, private-notifications-{id}

### API Base URL
- **Production:** https://api.sabongbetting.system
- **Android:** Configured in RetrofitClient

### Cooldown Settings
- **Teller request cooldown:** 30 seconds
- **Notification polling interval:** 2-3 seconds
- **Cache assignment expiry:** 30 seconds

## Common Issues Checklist

- [ ] WebSocket connection failing? Check Reverb server status
- [ ] Notifications not appearing? Check polling + WebSocket listeners
- [ ] Request always fails with 429? Teller in cooldown (wait 30s)
- [ ] Database notifications growing? Run cleanup job
- [ ] Runner not receiving real-time alerts? Check WebSocket connection
- [ ] Notification duplicates? Check ID merging logic

## Testing Endpoints

```bash
# Create test notification
GET /api/test/create-notification

# List notifications
GET /api/test/notifications

# Test broadcast
POST /test/broadcast-notification
```

## Important Cache Keys

- `teller_cooldown_{user_id}` - Teller request rate limit (30s)
- `teller_assigned_{teller_id}` - Current runner assignment (30s)
- `runner_online_{runner_id}` - Runner presence tracking

## Critical Methods/Functions

### Web App
- `CashRequestController::store()` - Create request
- `RunnerAssistanceController::request()` - Send assistance
- `RunnerAssistanceController::accept()` - Accept assignment
- `NotificationController::index()` - Get notifications

### Android App
- `RunnerViewModel.setupRealtimeListener()` - Connect WebSocket
- `RunnerViewModel.loadSavedNotifications()` - Poll DB
- `CashInViewModel.requestRunner()` - Send request
- `ReverbManager.connect()` - WebSocket connection
